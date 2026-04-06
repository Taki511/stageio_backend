<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentCV;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentCVController extends Controller
{
    /**
     * Display the student's CV.
     */
    public function show(Request $request)
    {
        if (!$request->user()->isStudent()) {
            return response()->json([
                'message' => 'Forbidden. Only students can view their CV.',
            ], 403);
        }

        $cv = StudentCV::where('student_id', $request->user()->id)->first();

        if (!$cv) {
            return response()->json([
                'message' => 'CV not found. Please create one.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'data' => $cv,
        ]);
    }

    /**
     * Store a newly created CV.
     */
    public function store(Request $request)
    {
        if (!$request->user()->isStudent()) {
            return response()->json([
                'message' => 'Forbidden. Only students can create a CV.',
            ], 403);
        }

        // Check if CV already exists
        $existingCV = StudentCV::where('student_id', $request->user()->id)->first();
        if ($existingCV) {
            return response()->json([
                'message' => 'CV already exists. Use PUT to update.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'age' => 'required|integer|min:16|max:100',
            'full_address' => 'required|string|max:500',
            'phone_number' => 'required|string|max:20',
            'academic_level' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'university_email' => 'required|email|max:255',
            'github_link' => 'nullable|string|max:255',
            'linkedin_link' => 'nullable|string|max:255',
            'portfolio_link' => 'nullable|string|max:255',
            'motivation_letter' => 'nullable|string|max:5000',
            'personal_info' => 'nullable|string|max:5000',
            'personal_photo' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cv = StudentCV::create([
            'student_id' => $request->user()->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'age' => $request->age,
            'full_address' => $request->full_address,
            'phone_number' => $request->phone_number,
            'academic_level' => $request->academic_level,
            'email' => $request->email,
            'university_email' => $request->university_email,
            'github_link' => $request->github_link,
            'linkedin_link' => $request->linkedin_link,
            'portfolio_link' => $request->portfolio_link,
            'motivation_letter' => $request->motivation_letter,
            'personal_info' => $request->personal_info,
            'personal_photo' => $request->personal_photo,
        ]);

        return response()->json([
            'message' => 'CV created successfully!',
            'data' => $cv,
        ], 201);
    }

    /**
     * Update the student's CV.
     */
    public function update(Request $request)
    {
        if (!$request->user()->isStudent()) {
            return response()->json([
                'message' => 'Forbidden. Only students can update their CV.',
            ], 403);
        }

        $cv = StudentCV::where('student_id', $request->user()->id)->first();

        if (!$cv) {
            return response()->json([
                'message' => 'CV not found. Please create one first.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'age' => 'sometimes|integer|min:16|max:100',
            'full_address' => 'sometimes|string|max:500',
            'phone_number' => 'sometimes|string|max:20',
            'academic_level' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'university_email' => 'sometimes|email|max:255',
            'github_link' => 'nullable|string|max:255',
            'linkedin_link' => 'nullable|string|max:255',
            'portfolio_link' => 'nullable|string|max:255',
            'motivation_letter' => 'nullable|string|max:5000',
            'personal_info' => 'nullable|string|max:5000',
            'personal_photo' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cv->update($request->only([
            'first_name', 'last_name', 'age', 'full_address', 'phone_number',
            'academic_level', 'email', 'university_email', 'github_link',
            'linkedin_link', 'portfolio_link', 'motivation_letter',
            'personal_info', 'personal_photo'
        ]));

        return response()->json([
            'message' => 'CV updated successfully!',
            'data' => $cv,
        ]);
    }

    /**
     * Remove the student's CV.
     */
    public function destroy(Request $request)
    {
        if (!$request->user()->isStudent()) {
            return response()->json([
                'message' => 'Forbidden. Only students can delete their CV.',
            ], 403);
        }

        $cv = StudentCV::where('student_id', $request->user()->id)->first();

        if (!$cv) {
            return response()->json([
                'message' => 'CV not found.',
            ], 404);
        }

        // Check if CV is used in any applications
        if ($cv->applications()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete CV that is used in applications.',
            ], 400);
        }

        $cv->delete();

        return response()->json([
            'message' => 'CV deleted successfully.',
        ]);
    }
}
