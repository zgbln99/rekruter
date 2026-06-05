<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Companies\StoreCompanyRequest;
use App\Http\Requests\Companies\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Company::query()->withCount('jobPostings')->latest();

        if ($term = trim($request->string('q')->toString())) {
            $like = '%'.$term.'%';
            $query->where(fn ($q) => $q->where('name', 'ilike', $like)
                ->orWhere('city', 'ilike', $like));
        }

        return CompanyResource::collection($query->paginate($request->integer('per_page', 25)));
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = Company::create($request->validated());

        return (new CompanyResource($company->refresh()))->response()->setStatusCode(201);
    }

    public function show(Company $company): CompanyResource
    {
        $company->load(['jobPostings' => fn ($q) => $q->withCount('applications')->latest()]);

        return new CompanyResource($company);
    }

    public function update(UpdateCompanyRequest $request, Company $company): CompanyResource
    {
        $company->update($request->validated());

        return new CompanyResource($company->refresh());
    }

    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return response()->json(['message' => 'Firma usunięta.']);
    }
}
