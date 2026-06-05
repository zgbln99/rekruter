<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\JobPosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Globalna wyszukiwarka: kandydaci (nazwisko/telefon) + ogłoszenia (tytuł/firma).
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim($request->string('q')->toString());

        if (mb_strlen($q) < 2) {
            return response()->json(['candidates' => [], 'offers' => []]);
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
        $digits = preg_replace('/\D+/', '', $q);

        $candidates = Candidate::query()
            ->where(function ($w) use ($like, $digits) {
                $w->whereRaw('(first_name || \' \' || coalesce(last_name, \'\')) ILIKE ?', [$like])
                    ->orWhere('phone', 'ILIKE', $like);
                if ($digits !== '') {
                    $w->orWhere('phone_normalized', 'LIKE', '%'.$digits.'%');
                }
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Candidate $c) => [
                'id' => $c->id,
                'full_name' => $c->fullName(),
                'phone' => $c->phone,
                'status_label' => $c->status?->label(),
            ]);

        $offers = JobPosting::query()
            ->with('company')
            ->where(function ($w) use ($like) {
                $w->where('title', 'ILIKE', $like)
                    ->orWhereHas('company', fn ($c) => $c->where('name', 'ILIKE', $like));
            })
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (JobPosting $o) => [
                'id' => $o->id,
                'title' => $o->title,
                'company' => $o->company?->name,
            ]);

        return response()->json([
            'candidates' => $candidates,
            'offers' => $offers,
        ]);
    }
}
