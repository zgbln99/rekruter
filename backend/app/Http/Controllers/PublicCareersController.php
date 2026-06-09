<?php

namespace App\Http\Controllers;

use App\Enums\CandidateStatus;
use App\Enums\DocumentType;
use App\Models\Candidate;
use App\Models\Document;
use App\Models\JobPosting;
use App\Models\Tenant;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Publiczna strona kariery (SSR, indeksowana w Google).
 *
 * Aplikacja jest jednoagencyjna — branding i oferty bierzemy z pierwszego
 * tenanta i wiążemy kontekst tenanta ręcznie (brak auth na trasach publicznych).
 */
class PublicCareersController extends Controller
{
    /** Lista ofert: inteligentne wyszukiwanie + filtry. */
    public function index(Request $request): View
    {
        $tenant = $this->tenant();

        $q = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $country = trim((string) $request->query('country', ''));
        $system = trim((string) $request->query('system', ''));

        $query = JobPosting::query()->published()->with('company');

        // Inteligentne wyszukiwanie — rozbijamy frazę na słowa i każde musi
        // pasować do któregokolwiek pola (tytuł, opis, lokalizacja, firma, kategoria).
        if ($q !== '') {
            foreach (preg_split('/\s+/', $q) as $word) {
                $like = '%'.$word.'%';
                $query->where(function ($sub) use ($like) {
                    $sub->where('title', 'ilike', $like)
                        ->orWhere('public_description', 'ilike', $like)
                        ->orWhere('country', 'ilike', $like)
                        ->orWhere('region_base', 'ilike', $like)
                        ->orWhere('work_system', 'ilike', $like)
                        ->orWhereRaw('required_categories::text ilike ?', [$like])
                        ->orWhereHas('company', fn ($c) => $c->where('name', 'ilike', $like));
                });
            }
        }

        if ($country !== '') {
            $query->where('country', $country);
        }
        if ($system !== '') {
            $query->where('work_system', $system);
        }
        if ($category !== '') {
            $query->whereJsonContains('required_categories', $category);
        }

        $offers = $query->orderByDesc('published_at')->orderByDesc('created_at')
            ->paginate(12)->withQueryString();

        // Opcje filtrów z aktualnie opublikowanych ofert.
        $published = JobPosting::query()->published()->get(['country', 'work_system', 'required_categories']);
        $countries = $published->pluck('country')->filter()->unique()->sort()->values();
        $systems = $published->pluck('work_system')->filter()->unique()->sort()->values();
        $categories = $published->pluck('required_categories')->filter()
            ->flatMap(fn ($c) => is_array($c) ? $c : [])->unique()->sort()->values();

        return view('careers.index', [
            'tenant' => $tenant,
            'offers' => $offers,
            'featured' => JobPosting::query()->published()->with('company')
                ->orderByDesc('published_at')->orderByDesc('created_at')->first(),
            'countries' => $countries,
            'systems' => $systems,
            'categories' => $categories,
            'filters' => compact('q', 'category', 'country', 'system'),
            'total' => JobPosting::query()->published()->count(),
        ]);
    }

    /** Szczegóły oferty + formularz aplikacji + dane strukturalne. */
    public function show(Request $request, string $slug, string $jobPosting): View
    {
        $this->tenant();

        $offer = JobPosting::query()->published()->with('company')->find($jobPosting);
        abort_if($offer === null, 404);

        // Kanoniczny adres — przekierowanie, gdy slug w URL się nie zgadza.
        if ($slug !== $offer->publicSlug()) {
            abort(redirect()->to($offer->publicPath(), 301));
        }

        return view('careers.show', [
            'tenant' => $this->tenant(),
            'offer' => $offer,
            'jsonLd' => $this->jsonLd($offer),
            'related' => JobPosting::query()->published()->with('company')
                ->where('id', '!=', $offer->id)
                ->orderByDesc('published_at')->limit(3)->get(),
        ]);
    }

    /** Polityka prywatności (RODO). */
    public function privacy(): View
    {
        return view('careers.privacy', ['tenant' => $this->tenant()]);
    }

    /** Obsługa aplikacji: tworzy kandydata + przypięcie do ogłoszenia. */
    public function apply(Request $request, string $jobPosting): RedirectResponse
    {
        $this->tenant();

        $offer = JobPosting::query()->published()->find($jobPosting);
        abort_if($offer === null, 404);

        // Honeypot — boty wypełniają ukryte pole „company".
        if (trim((string) $request->input('company')) !== '') {
            return redirect()->to($offer->publicPath().'#aplikuj')->with('applied', true);
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:191'],
            'city' => ['nullable', 'string', 'max:120'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:10'],
            'qualifications' => ['nullable', 'array'],
            'qualifications.*' => ['string', 'in:adr,code_95,card,international'],
            'message' => ['nullable', 'string', 'max:2000'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:8192'],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => 'Wymagana jest zgoda na przetwarzanie danych.',
            'first_name.required' => 'Podaj imię.',
            'last_name.required' => 'Podaj nazwisko.',
            'phone.required' => 'Podaj numer telefonu.',
        ]);

        $candidate = $this->findOrCreateCandidate($offer, $data);
        $this->attachToOffer($candidate, $offer, $data['message'] ?? null);

        if ($request->hasFile('cv')) {
            $this->storeCv($candidate, $request->file('cv'));
        }

        return redirect()->to($offer->publicPath().'#aplikuj')->with('applied', true);
    }

    /** Pierwszy tenant + związanie kontekstu (dla scope'ów i tworzenia rekordów). */
    private function tenant(): Tenant
    {
        $tenant = Tenant::query()->withoutGlobalScopes()->first();
        abort_if($tenant === null, 404);
        app()->instance('currentTenantId', $tenant->id);

        return $tenant;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findOrCreateCandidate(JobPosting $offer, array $data): Candidate
    {
        $normalized = PhoneNumber::normalize($data['phone']);

        $candidate = $normalized
            ? Candidate::query()->where('phone_normalized', $normalized)->first()
            : null;

        if ($candidate) {
            return $candidate;
        }

        $quals = $data['qualifications'] ?? [];

        return Candidate::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'phone_normalized' => $normalized,
            'email' => $data['email'] ?? null,
            'city' => $data['city'] ?? null,
            'license_categories' => $data['categories'] ?? [],
            'has_adr' => in_array('adr', $quals, true),
            'has_code_95' => in_array('code_95', $quals, true),
            'exp_international' => in_array('international', $quals, true),
            'experience_notes' => $data['message'] ?? null,
            'status' => CandidateStatus::New->value,
            'source' => 'Strona kariery',
            'consent_rodo_at' => now(),
        ]);
    }

    private function attachToOffer(Candidate $candidate, JobPosting $offer, ?string $message): void
    {
        $exists = $candidate->applications()->where('job_posting_id', $offer->id)->exists();
        if ($exists) {
            return;
        }

        $position = (int) \App\Models\Application::query()
            ->where('job_posting_id', $offer->id)
            ->where('status', \App\Enums\ApplicationStatus::New->value)
            ->max('position');

        \App\Models\Application::create([
            'candidate_id' => $candidate->id,
            'job_posting_id' => $offer->id,
            'status' => \App\Enums\ApplicationStatus::New->value,
            'position' => $position + 1,
            'notes' => $message ? 'Aplikacja ze strony kariery: '.$message : 'Aplikacja ze strony kariery.',
        ]);
    }

    private function storeCv(Candidate $candidate, \Illuminate\Http\UploadedFile $file): void
    {
        try {
            $disk = config('rekruter.documents_disk');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');
            $path = 'kandydaci/'.$candidate->id.'/cv-'.Str::random(8).'.'.$ext;
            Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

            Document::create([
                'candidate_id' => $candidate->id,
                'type' => DocumentType::Cv->value,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize() ?: 0,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('CV z aplikacji nie zapisane: '.$e->getMessage());
        }
    }

    /**
     * Dane strukturalne schema.org/JobPosting (Google Jobs).
     *
     * @return array<string, mixed>
     */
    private function jsonLd(JobPosting $offer): array
    {
        $ld = [
            '@context' => 'https://schema.org/',
            '@type' => 'JobPosting',
            'title' => $offer->title,
            'description' => \App\Support\Html\SafeHtml::clean($offer->public_description) ?: e($offer->title),
            'datePosted' => optional($offer->published_at ?? $offer->created_at)->toDateString(),
            'employmentType' => 'FULL_TIME',
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $offer->tenant?->agencyName() ?? config('app.name'),
            ],
            'jobLocation' => [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressCountry' => $offer->country ?: 'PL',
                    'addressLocality' => $offer->region_base ?: null,
                ],
            ],
        ];

        if (preg_match('/\d[\d\s.]*/', (string) $offer->salary_amount, $m)) {
            $value = (float) str_replace([' ', '.'], '', $m[0]);
            if ($value > 0) {
                $ld['baseSalary'] = [
                    '@type' => 'MonetaryAmount',
                    'currency' => $offer->currency ?: 'EUR',
                    'value' => ['@type' => 'QuantitativeValue', 'value' => $value, 'unitText' => 'MONTH'],
                ];
            }
        }

        return $ld;
    }
}
