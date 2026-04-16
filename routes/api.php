<?php

use App\Http\Controllers\Api\AdminValidationController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AutoActionController;
use App\Http\Controllers\Api\CompanyProfileController;
use App\Http\Controllers\Api\InternshipOfferController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\StudentCVController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



// ==========================================
// PUBLIC ROUTES (No Authentication Required)
// ==========================================

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Password Reset Routes
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Public Internship Offers Routes (Guests, Students, Recruiters can view)
Route::get('/internship-offers', [InternshipOfferController::class, 'index']);
Route::get('/internship-offers/{id}', [InternshipOfferController::class, 'show']);
Route::get('/internship-offers/skills/list', [InternshipOfferController::class, 'skills']);

// Search & Filter Routes (Public)
Route::get('/internship-offers-search', [InternshipOfferController::class, 'search']); // Text search
Route::get('/internship-offers-filter', [InternshipOfferController::class, 'filter']); // Dropdown filters
Route::get('/internship-offers-filter-options', [InternshipOfferController::class, 'filterOptions']); // Filter dropdown options

// Debug route - check what headers are received
Route::get('/debug', function (Request $request) {
    $user = $request->user();
    return response()->json([
        'headers' => $request->headers->all(),
        'authorization_header' => $request->header('Authorization'),
        'bearer_token' => $request->bearerToken(),
        'user' => $user ? $user->toArray() : null,
        'authenticated' => $user !== null,
    ]);
});

// Debug POST request
Route::post('/debug-post', function (Request $request) {
    return response()->json([
        'content_type' => $request->header('Content-Type'),
        'method' => $request->method(),
        'all_input' => $request->all(),
        'personal_info' => $request->input('personal_info'),
        'has_personal_info' => $request->has('personal_info'),
        'raw_content' => $request->getContent(),
    ]);
});

// Test route with token in query (temporary for debugging)
Route::get('/test-token', function (Request $request) {
    $token = $request->query('token');
    if (!$token) {
        return response()->json(['error' => 'No token provided'], 401);
    }
    
    // Manually authenticate
    $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
    if (!$personalAccessToken) {
        return response()->json(['error' => 'Invalid token'], 401);
    }
    
    $user = $personalAccessToken->tokenable;
    
    return response()->json([
        'authenticated' => true,
        'user_id' => $user->id,
        'user_email' => $user->email,
        'user_role' => $user->role,
    ]);
});

// ==========================================
// PROTECTED ROUTES (Authentication Required)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/me', [AuthController::class, 'me']);

    // Student-only routes
    Route::middleware('role:student')->group(function () {
        Route::get('/student/dashboard', function () {
            return response()->json([
                'message' => 'Welcome to Student Dashboard',
            ]);
        });
    });

    // Recruiter-only routes
    Route::middleware('role:recruiter')->group(function () {
        Route::get('/recruiter/dashboard', function () {
            return response()->json([
                'message' => 'Welcome to Recruiter Dashboard',
            ]);
        });
    });

    // Admin-only routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return response()->json([
                'message' => 'Welcome to Admin Dashboard',
            ]);
        });
    });

    // Multi-role routes (example: student and recruiter)
    Route::middleware('role:student,recruiter')->group(function () {
        Route::get('/shared-resource', function () {
            return response()->json([
                'message' => 'This resource is accessible by students and recruiters',
            ]);
        });
    });

    // Recruiter-only routes for managing company profile and offers
    Route::middleware('role:recruiter')->group(function () {
        // Company Profile Routes
        Route::get('/company-profile', [CompanyProfileController::class, 'show']);
        Route::post('/company-profile', [CompanyProfileController::class, 'store']);
        Route::put('/company-profile', [CompanyProfileController::class, 'update']);
        Route::delete('/company-profile', [CompanyProfileController::class, 'destroy']);
        
        // Internship Offer Management Routes
        Route::post('/internship-offers', [InternshipOfferController::class, 'store']);
        Route::put('/internship-offers/{id}', [InternshipOfferController::class, 'update']);
        Route::delete('/internship-offers/{id}', [InternshipOfferController::class, 'destroy']);
        Route::get('/my-internship-offers', [InternshipOfferController::class, 'myOffers']);
        
        // Application Management Routes
        Route::get('/internship-offers/{offerId}/applications', [ApplicationController::class, 'offerApplications']);
        Route::post('/applications/{id}/accept', [ApplicationController::class, 'accept']);
        Route::post('/applications/{id}/refuse', [ApplicationController::class, 'refuse']);
    });

    // Student-only routes
    Route::middleware('role:student')->group(function () {
        // CV Routes
        Route::get('/my-cv', [StudentCVController::class, 'show']);
        Route::post('/my-cv', [StudentCVController::class, 'store']);
        Route::put('/my-cv', [StudentCVController::class, 'update']);
        Route::delete('/my-cv', [StudentCVController::class, 'destroy']);
        
        // Application Routes
        Route::post('/internship-offers/{offerId}/apply', [ApplicationController::class, 'apply']);
        Route::get('/my-applications', [ApplicationController::class, 'myApplications']);
        Route::get('/applications/{id}', [ApplicationController::class, 'show']);
        Route::post('/applications/{id}/confirm', [ApplicationController::class, 'confirm']);
        Route::delete('/applications/{id}/cancel', [ApplicationController::class, 'cancel']);
        Route::get('/applications-daily-status', [ApplicationController::class, 'dailyStatus']);
    });

    // Admin-only routes for validation and agreements
    Route::middleware('role:admin')->group(function () {
        // Validation Routes
        Route::get('/admin/pending-validations', [AdminValidationController::class, 'pendingValidations']);
        Route::get('/admin/validated-applications', [AdminValidationController::class, 'validatedApplications']);
        Route::get('/admin/rejected-applications', [AdminValidationController::class, 'rejectedApplications']);
        Route::post('/admin/applications/{applicationId}/validate', [AdminValidationController::class, 'validateInternship']);
        Route::post('/admin/applications/{applicationId}/reject', [AdminValidationController::class, 'rejectApplication']);
        
        // Internship Management
        Route::get('/admin/internships', [AdminValidationController::class, 'allInternships']);
        Route::post('/admin/internships/{internshipId}/complete', [AdminValidationController::class, 'completeInternship']);
        
        // Agreement Routes
        Route::get('/admin/agreements', [AdminValidationController::class, 'allAgreements']);
        Route::post('/admin/internships/{internshipId}/generate-agreement', [AdminValidationController::class, 'generateAgreement']);
        Route::get('/admin/agreements/{agreementId}/download', [AdminValidationController::class, 'downloadAgreement']);
        Route::post('/admin/agreements/{agreementId}/regenerate-pdf', [AdminValidationController::class, 'regeneratePdf']);
        
        // Auto-Actions
        Route::post('/admin/auto-actions/trigger', [AutoActionController::class, 'trigger']);
    });

    // Auto-Actions Status (All authenticated users)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auto-actions/status', [AutoActionController::class, 'status']);
    });
});
