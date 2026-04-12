<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\InternshipOffer;
use App\Models\Skill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InternshipOfferController extends Controller
{
    /**
     * Display a listing of internship offers (with filters).
     */
    public function index(Request $request)
    {
        $query = InternshipOffer::with(['companyProfile', 'skills']);

        // Filter by status (open/closed)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // By default, only show open offers
            $query->where('status', InternshipOffer::STATUS_OPEN);
        }

        // Filter by Wilaya
        if ($request->has('wilaya')) {
            $query->where('wilaya', 'like', '%' . $request->wilaya . '%');
        }

        // Filter by Type (full_time, part_time, remote)
        if ($request->has('type')) {
            $query->where('internship_type', $request->type);
        }

        // Filter by Tech/Skill
        if ($request->has('skill')) {
            $query->whereHas('skills', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->skill . '%');
            });
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Pagination
        $offers = $query->paginate($request->get('per_page', 10));

        // Auto-update status for each offer
        foreach ($offers as $offer) {
            $offer->autoUpdateStatus();
        }

        return response()->json([
            'data' => $offers->items(),
            'meta' => [
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ],
        ]);
    }

    /**
     * Search internship offers by text (for search bar).
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:255',
        ]);

        $searchTerm = $request->input('q');

        $query = InternshipOffer::with(['companyProfile', 'skills'])
            ->where('status', InternshipOffer::STATUS_OPEN)
            ->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('wilaya', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('companyProfile', function ($companyQuery) use ($searchTerm) {
                      $companyQuery->where('name', 'like', '%' . $searchTerm . '%');
                  })
                  ->orWhereHas('skills', function ($skillQuery) use ($searchTerm) {
                      $skillQuery->where('name', 'like', '%' . $searchTerm . '%');
                  });
            });

        $offers = $query->paginate($request->get('per_page', 10));

        // Auto-update status
        foreach ($offers as $offer) {
            $offer->autoUpdateStatus();
        }

        return response()->json([
            'search_term' => $searchTerm,
            'results_count' => $offers->total(),
            'data' => $offers->items(),
            'meta' => [
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ],
        ]);
    }

    /**
     * Filter internship offers using dropdown filters.
     */
    public function filter(Request $request)
    {
        $request->validate([
            'wilaya' => 'nullable|string|max:255',
            'type' => 'nullable|in:full_time,part_time,remote',
            'skill_id' => 'nullable|integer|exists:skills,id',
            'company_id' => 'nullable|integer|exists:company_profiles,id',
            'min_duration' => 'nullable|integer|min:1',
            'max_duration' => 'nullable|integer|min:1',
        ]);

        $query = InternshipOffer::with(['companyProfile', 'skills'])
            ->where('status', InternshipOffer::STATUS_OPEN);

        // Filter by Wilaya (dropdown)
        if ($request->filled('wilaya')) {
            $query->where('wilaya', $request->wilaya);
        }

        // Filter by Internship Type (dropdown)
        if ($request->filled('type')) {
            $query->where('internship_type', $request->type);
        }

        // Filter by Skill ID (dropdown)
        if ($request->filled('skill_id')) {
            $query->whereHas('skills', function ($q) use ($request) {
                $q->where('skills.id', $request->skill_id);
            });
        }

        // Filter by Company ID (dropdown)
        if ($request->filled('company_id')) {
            $query->where('company_profile_id', $request->company_id);
        }

        // Filter by Duration Range
        if ($request->filled('min_duration')) {
            $query->where('duration', '>=', $request->min_duration);
        }
        if ($request->filled('max_duration')) {
            $query->where('duration', '<=', $request->max_duration);
        }

        $offers = $query->paginate($request->get('per_page', 10));

        // Auto-update status
        foreach ($offers as $offer) {
            $offer->autoUpdateStatus();
        }

        return response()->json([
            'filters_applied' => $request->only(['wilaya', 'type', 'skill_id', 'company_id', 'min_duration', 'max_duration']),
            'results_count' => $offers->total(),
            'data' => $offers->items(),
            'meta' => [
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ],
        ]);
    }

    /**
     * Get filter options for dropdowns.
     */
    public function filterOptions()
    {
        $wilayas = InternshipOffer::distinct()->pluck('wilaya');
        $types = [
            ['value' => 'full_time', 'label' => 'Full Time'],
            ['value' => 'part_time', 'label' => 'Part Time'],
            ['value' => 'remote', 'label' => 'Remote'],
        ];
        $skills = \App\Models\Skill::select('id', 'name')->get();
        $companies = \App\Models\CompanyProfile::select('id', 'name')->get();

        return response()->json([
            'wilayas' => $wilayas,
            'types' => $types,
            'skills' => $skills,
            'companies' => $companies,
        ]);
    }

    /**
     * Store a newly created internship offer.
     */
    public function store(Request $request)
    {
        // Only recruiters can create offers
        if (!$request->user()->isRecruiter()) {
            return response()->json([
                'message' => 'Forbidden. Only recruiters can create internship offers.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'wilaya' => 'required|string|max:255',
            'start_date' => 'required|date|after:today',
            'internship_type' => 'required|in:full_time,part_time,remote',
            'duration' => 'required|integer|min:1',
            'max_students' => 'required|integer|min:1',
            'deadline' => 'required|date|after:today|before:start_date',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get recruiter's company profile
        $companyProfile = CompanyProfile::where('recruiter_id', $request->user()->id)->first();
        
        if (!$companyProfile) {
            return response()->json([
                'message' => 'You must create a company profile first.',
            ], 400);
        }

        // Create the offer
        $offer = InternshipOffer::create([
            'company_profile_id' => $companyProfile->id,
            'title' => $request->title,
            'description' => $request->description,
            'wilaya' => $request->wilaya,
            'start_date' => $request->start_date,
            'internship_type' => $request->internship_type,
            'duration' => $request->duration,
            'max_students' => $request->max_students,
            'deadline' => $request->deadline,
            'status' => InternshipOffer::STATUS_OPEN,
        ]);

        // Attach skills
        if ($request->has('skills')) {
            $skillIds = [];
            foreach ($request->skills as $skillName) {
                $skill = Skill::firstOrCreate(['name' => $skillName]);
                $skillIds[] = $skill->id;
            }
            $offer->skills()->attach($skillIds);
        }

        return response()->json([
            'message' => 'Internship offer created successfully',
            'data' => $offer->load('skills', 'companyProfile'),
        ], 201);
    }

    /**
     * Display the specified internship offer.
     */
    public function show(string $id)
    {
        $offer = InternshipOffer::with(['companyProfile', 'skills'])->find($id);

        if (!$offer) {
            return response()->json([
                'message' => 'Internship offer not found',
            ], 404);
        }

        // Auto-update status
        $offer->autoUpdateStatus();

        return response()->json([
            'data' => $offer,
            'accepted_students' => $offer->acceptedApplicationsCount(),
            'available_spots' => max(0, $offer->max_students - $offer->acceptedApplicationsCount()),
            'deadline_passed' => $offer->hasDeadlinePassed(),
        ]);
    }

    /**
     * Update the specified internship offer.
     */
    public function update(Request $request, string $id)
    {
        $offer = InternshipOffer::find($id);

        if (!$offer) {
            return response()->json([
                'message' => 'Internship offer not found',
            ], 404);
        }

        // Only the recruiter who owns the company can update
        $companyProfile = CompanyProfile::where('recruiter_id', $request->user()->id)->first();
        
        if (!$companyProfile || $offer->company_profile_id !== $companyProfile->id) {
            return response()->json([
                'message' => 'Forbidden. You can only update your own internship offers.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'wilaya' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date|after:today',
            'internship_type' => 'sometimes|in:full_time,part_time,remote',
            'duration' => 'sometimes|integer|min:1',
            'max_students' => 'sometimes|integer|min:1',
            'deadline' => 'sometimes|date|after:today|before:start_date',
            'status' => 'sometimes|in:open,closed',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update the offer
        $offer->update($request->only([
            'title', 'description', 'wilaya', 'start_date', 'internship_type', 
            'duration', 'max_students', 'deadline', 'status'
        ]));

        // Update skills if provided
        if ($request->has('skills')) {
            $skillIds = [];
            foreach ($request->skills as $skillName) {
                $skill = Skill::firstOrCreate(['name' => $skillName]);
                $skillIds[] = $skill->id;
            }
            $offer->skills()->sync($skillIds);
        }

        // Re-check status after update
        $offer->autoUpdateStatus();

        return response()->json([
            'message' => 'Internship offer updated successfully',
            'data' => $offer->load('skills', 'companyProfile'),
        ]);
    }

    /**
     * Remove the specified internship offer.
     */
    public function destroy(Request $request, string $id)
    {
        $offer = InternshipOffer::find($id);

        if (!$offer) {
            return response()->json([
                'message' => 'Internship offer not found',
            ], 404);
        }

        // Only the recruiter who owns the company can delete
        $companyProfile = CompanyProfile::where('recruiter_id', $request->user()->id)->first();
        
        if (!$companyProfile || $offer->company_profile_id !== $companyProfile->id) {
            return response()->json([
                'message' => 'Forbidden. You can only delete your own internship offers.',
            ], 403);
        }

        // Check if there are any applications
        if ($offer->applications()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete offer with existing applications.',
            ], 400);
        }

        $offer->skills()->detach();
        $offer->delete();

        return response()->json([
            'message' => 'Internship offer deleted successfully',
        ]);
    }

    /**
     * Get all available skills (for filter dropdown).
     */
    public function skills()
    {
        $skills = Skill::all()->pluck('name');
        return response()->json([
            'data' => $skills,
        ]);
    }

    /**
     * Get recruiter's own internship offers.
     */
    public function myOffers(Request $request)
    {
        if (!$request->user()->isRecruiter()) {
            return response()->json([
                'message' => 'Forbidden. Only recruiters can view their offers.',
            ], 403);
        }

        $companyProfile = CompanyProfile::where('recruiter_id', $request->user()->id)->first();
        
        if (!$companyProfile) {
            return response()->json([
                'data' => [],
            ]);
        }

        $offers = InternshipOffer::with(['skills', 'applications'])
            ->where('company_profile_id', $companyProfile->id)
            ->paginate($request->get('per_page', 10));

        // Auto-update status for each offer
        foreach ($offers as $offer) {
            $offer->autoUpdateStatus();
        }

        return response()->json([
            'data' => $offers->items(),
            'meta' => [
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ],
        ]);
    }
}
