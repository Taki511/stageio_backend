<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyProfileController extends Controller
{
    /**
     * Display the recruiter's company profile.
     */
    public function show(Request $request)
    {
        if (!$request->user()->isRecruiter()) {
            return response()->json([
                'message' => 'Forbidden. Only recruiters can view company profiles.',
            ], 403);
        }

        $profile = CompanyProfile::where('recruiter_id', $request->user()->id)
            ->with('recruiter')
            ->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Company profile not found. Please create one.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'data' => $profile,
        ]);
    }

    /**
     * Store a newly created company profile.
     */
    public function store(Request $request)
    {
        if (!$request->user()->isRecruiter()) {
            return response()->json([
                'message' => 'Forbidden. Only recruiters can create company profiles.',
            ], 403);
        }

        // Check if profile already exists
        $existingProfile = CompanyProfile::where('recruiter_id', $request->user()->id)->first();
        if ($existingProfile) {
            return response()->json([
                'message' => 'Company profile already exists. Use PUT to update.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'wilaya' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'logo' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $profile = CompanyProfile::create([
            'recruiter_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
            'wilaya' => $request->wilaya,
            'address' => $request->address,
            'logo' => $request->logo,
        ]);

        return response()->json([
            'message' => 'Company profile created successfully',
            'data' => $profile,
        ], 201);
    }

    /**
     * Update the company profile.
     */
    public function update(Request $request)
    {
        if (!$request->user()->isRecruiter()) {
            return response()->json([
                'message' => 'Forbidden. Only recruiters can update company profiles.',
            ], 403);
        }

        $profile = CompanyProfile::where('recruiter_id', $request->user()->id)->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Company profile not found. Please create one first.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'wilaya' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:500',
            'logo' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $profile->update($request->only([
            'name', 'description', 'wilaya', 'address', 'logo'
        ]));

        return response()->json([
            'message' => 'Company profile updated successfully',
            'data' => $profile,
        ]);
    }

    /**
     * Remove the company profile.
     */
    public function destroy(Request $request)
    {
        if (!$request->user()->isRecruiter()) {
            return response()->json([
                'message' => 'Forbidden. Only recruiters can delete company profiles.',
            ], 403);
        }

        $profile = CompanyProfile::where('recruiter_id', $request->user()->id)->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Company profile not found.',
            ], 404);
        }

        // Check if there are any internship offers
        if ($profile->internshipOffers()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete profile with existing internship offers.',
            ], 400);
        }

        $profile->delete();

        return response()->json([
            'message' => 'Company profile deleted successfully',
        ]);
    }
}
