<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Internship;
use App\Models\InternshipOffer;
use App\Models\StudentCV;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{
    private const DAILY_APPLICATION_LIMIT = 10;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Check if student has reached daily application limit.
     */
    private function hasReachedDailyLimit(int $studentId): bool
    {
        $today = Carbon::today();
        
        $count = Application::where('student_id', $studentId)
            ->whereDate('created_at', $today)
            ->count();
        
        return $count >= self::DAILY_APPLICATION_LIMIT;
    }

    /**
     * Get remaining applications for today.
     */
    private function getRemainingApplications(int $studentId): int
    {
        $today = Carbon::today();
        
        $count = Application::where('student_id', $studentId)
            ->whereDate('created_at', $today)
            ->count();
        
        return max(0, self::DAILY_APPLICATION_LIMIT - $count);
    }

    /**
     * Student applies to an internship offer.
     */
    public function apply(Request $request, string $offerId)
    {
        if (!$request->user()->isStudent()) {
            return response()->json([
                'message' => 'Forbidden. Only students can apply to internship offers.',
            ], 403);
        }

        $student = $request->user();

        // Check daily application limit
        if ($this->hasReachedDailyLimit($student->id)) {
            return response()->json([
                'message' => 'Daily application limit reached. You can only apply to ' . self::DAILY_APPLICATION_LIMIT . ' offers per day.',
                'limit' => self::DAILY_APPLICATION_LIMIT,
                'remaining' => 0,
                'reset_at' => Carbon::tomorrow()->format('Y-m-d H:i:s'),
            ], 429);
        }

        // Check if offer exists and is open
        $offer = InternshipOffer::find($offerId);
        if (!$offer) {
            return response()->json([
                'message' => 'Internship offer not found.',
            ], 404);
        }

        if (!$offer->isOpen()) {
            return response()->json([
                'message' => 'This internship offer is closed.',
                'reason' => $offer->hasDeadlinePassed() ? 'Deadline has passed' : 'Maximum number of students reached',
                'status' => $offer->status,
            ], 400);
        }

        // Check if student already applied to this offer
        $existingApplication = Application::where('student_id', $student->id)
            ->where('internship_offer_id', $offerId)
            ->first();

        if ($existingApplication) {
            return response()->json([
                'message' => 'You have already applied to this internship offer.',
                'application' => $existingApplication,
            ], 400);
        }

        // Check if student has an active (non-completed) confirmed application
        $activeConfirmedApplication = Application::where('student_id', $student->id)
            ->where('is_confirmed', true)
            ->whereDoesntHave('internship', function ($query) {
                $query->where('status', Internship::STATUS_COMPLETED);
            })
            ->exists();

        if ($activeConfirmedApplication) {
            return response()->json([
                'message' => 'You have an active internship. You can only apply after your current internship is completed.',
            ], 400);
        }

        // Get student's CV
        $cv = StudentCV::where('student_id', $student->id)->first();
        if (!$cv) {
            return response()->json([
                'message' => 'You must create a CV before applying. Please create your CV first.',
            ], 400);
        }

        // Create application
        $application = Application::create([
            'student_id' => $student->id,
            'internship_offer_id' => $offerId,
            'student_cv_id' => $cv->id,
            'application_date' => now(),
            'status' => Application::STATUS_PENDING,
            'is_confirmed' => false,
            'cover_letter' => null,
        ]);

        $remaining = $this->getRemainingApplications($student->id);

        return response()->json([
            'message' => 'Application submitted successfully!',
            'data' => $application->load(['internshipOffer', 'studentCv']),
            'daily_limit' => self::DAILY_APPLICATION_LIMIT,
            'remaining_today' => $remaining,
        ], 201);
    }

    /**
     * Student confirms an accepted application.
     * This auto-cancels all other accepted applications.
     */
    public function confirm(Request $request, string $id)
    {
        if (!$request->user()->isStudent()) {
            return response()->json([
                'message' => 'Forbidden. Only students can confirm applications.',
            ], 403);
        }

        $application = Application::with('internshipOffer')->find($id);

        if (!$application) {
            return response()->json([
                'message' => 'Application not found.',
            ], 404);
        }

        // Verify the application belongs to the student
        if ($application->student_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden. You can only confirm your own applications.',
            ], 403);
        }

        // Check if application is accepted
        if ($application->status !== Application::STATUS_ACCEPTED) {
            return response()->json([
                'message' => 'Application must be accepted by the recruiter before confirmation.',
                'current_status' => $application->status,
            ], 400);
        }

        // Check if already confirmed
        if ($application->is_confirmed) {
            return response()->json([
                'message' => 'Application is already confirmed.',
            ], 400);
        }

        // Confirm the application
        // The model's boot method will auto-cancel other accepted and pending applications
        $application->update([
            'is_confirmed' => true,
            'confirmed_at' => now(),
        ]);

        // Count how many other applications were just cancelled
        $cancelledCount = Application::where('student_id', $request->user()->id)
            ->where('id', '!=', $id)
            ->where('status', Application::STATUS_CANCELLED)
            ->where('updated_at', '>=', now()->subSeconds(5))
            ->count();

        return response()->json([
            'message' => 'Application confirmed successfully!',
            'data' => $application->load(['internshipOffer', 'studentCv']),
            'other_applications_cancelled' => $cancelledCount,
            'next_step' => 'Your application is now pending admin validation.',
        ]);
    }

    /**
     * Get student's daily application status.
     */
    public function dailyStatus(Request $request)
    {
        if (!$request->user()->isStudent()) {
            return response()->json([
                'message' => 'Forbidden. Only students can view application status.',
            ], 403);
        }

        $studentId = $request->user()->id;
        $today = Carbon::today();
        
        $count = Application::where('student_id', $studentId)
            ->whereDate('created_at', $today)
            ->count();
        
        $remaining = max(0, self::DAILY_APPLICATION_LIMIT - $count);

        // Check if student has an active confirmed application (not completed)
        $activeConfirmedApplication = Application::where('student_id', $studentId)
            ->where('is_confirmed', true)
            ->whereDoesntHave('internship', function ($query) {
                $query->where('status', Internship::STATUS_COMPLETED);
            })
            ->with('internshipOffer')
            ->first();

        // Check if student has a completed internship
        $completedInternship = Application::where('student_id', $studentId)
            ->where('is_confirmed', true)
            ->whereHas('internship', function ($query) {
                $query->where('status', Internship::STATUS_COMPLETED);
            })
            ->with(['internshipOffer', 'internship'])
            ->first();

        return response()->json([
            'daily_limit' => self::DAILY_APPLICATION_LIMIT,
            'applied_today' => $count,
            'remaining_today' => $remaining,
            'reset_at' => Carbon::tomorrow()->format('Y-m-d H:i:s'),
            'can_apply' => $activeConfirmedApplication === null,
            'has_active_internship' => $activeConfirmedApplication !== null,
            'active_internship' => $activeConfirmedApplication,
            'has_completed_internship' => $completedInternship !== null,
            'completed_internship' => $completedInternship,
        ]);
    }

    /**
     * Student views their applications.
     */
    public function myApplications(Request $request)
    {
        if (!$request->user()->isStudent()) {
            return response()->json([
                'message' => 'Forbidden. Only students can view their applications.',
            ], 403);
        }

        $applications = Application::where('student_id', $request->user()->id)
            ->with(['internshipOffer.companyProfile', 'studentCv'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $applications->items(),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ]);
    }

    /**
     * Student views a specific application.
     */
    public function show(Request $request, string $id)
    {
        $application = Application::with(['internshipOffer.companyProfile', 'studentCv'])
            ->find($id);

        if (!$application) {
            return response()->json([
                'message' => 'Application not found.',
            ], 404);
        }

        $user = $request->user();
        $isOwner = $application->student_id === $user->id;
        $isRecruiter = $user->isRecruiter() && 
            $application->internshipOffer->companyProfile->recruiter_id === $user->id;

        if (!$isOwner && !$isRecruiter) {
            return response()->json([
                'message' => 'Forbidden. You can only view your own applications.',
            ], 403);
        }

        return response()->json([
            'data' => $application,
        ]);
    }

    /**
     * Recruiter views applications for their internship offer.
     */
    public function offerApplications(Request $request, string $offerId)
    {
        if (!$request->user()->isRecruiter()) {
            return response()->json([
                'message' => 'Forbidden. Only recruiters can view applications.',
            ], 403);
        }

        $offer = InternshipOffer::with('companyProfile')->find($offerId);

        if (!$offer) {
            return response()->json([
                'message' => 'Internship offer not found.',
            ], 404);
        }

        if ($offer->companyProfile->recruiter_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden. You can only view applications for your own offers.',
            ], 403);
        }

        $applications = Application::where('internship_offer_id', $offerId)
            ->with(['student.studentProfile', 'studentCv'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $applications->items(),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ]);
    }

    /**
     * Recruiter accepts an application.
     */
    public function accept(Request $request, string $id)
    {
        if (!$request->user()->isRecruiter()) {
            return response()->json([
                'message' => 'Forbidden. Only recruiters can accept applications.',
            ], 403);
        }

        $application = Application::with('internshipOffer.companyProfile')->find($id);

        if (!$application) {
            return response()->json([
                'message' => 'Application not found.',
            ], 404);
        }

        if ($application->internshipOffer->companyProfile->recruiter_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden. You can only accept applications for your own offers.',
            ], 403);
        }

        if ($application->status !== Application::STATUS_PENDING) {
            return response()->json([
                'message' => 'Application cannot be accepted. Current status: ' . $application->status,
            ], 400);
        }

        $application->update([
            'status' => Application::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        // Send notification to student
        $this->notificationService->notifyStudentApplicationAccepted($application);

        return response()->json([
            'message' => 'Application accepted successfully! Waiting for student confirmation.',
            'data' => $application,
        ]);
    }

    /**
     * Recruiter refuses an application.
     */
    public function refuse(Request $request, string $id)
    {
        if (!$request->user()->isRecruiter()) {
            return response()->json([
                'message' => 'Forbidden. Only recruiters can refuse applications.',
            ], 403);
        }

        $application = Application::with('internshipOffer.companyProfile')->find($id);

        if (!$application) {
            return response()->json([
                'message' => 'Application not found.',
            ], 404);
        }

        if ($application->internshipOffer->companyProfile->recruiter_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden. You can only refuse applications for your own offers.',
            ], 403);
        }

        if ($application->status !== Application::STATUS_PENDING) {
            return response()->json([
                'message' => 'Application cannot be refused. Current status: ' . $application->status,
            ], 400);
        }

        // Cancel the application
        $application->cancel();

        // Send notification to student
        $this->notificationService->notifyStudentApplicationRefused($application);

        return response()->json([
            'message' => 'Application refused.',
            'data' => $application,
        ]);
    }

    /**
     * Student cancels their own application.
     */
    public function cancel(Request $request, string $id)
    {
        if (!$request->user()->isStudent()) {
            return response()->json([
                'message' => 'Forbidden. Only students can cancel their applications.',
            ], 403);
        }

        $application = Application::with(['internshipOffer', 'internship'])->find($id);

        if (!$application) {
            return response()->json([
                'message' => 'Application not found.',
            ], 404);
        }

        if ($application->student_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden. You can only cancel your own applications.',
            ], 403);
        }

        if ($application->internship()->exists()) {
            return response()->json([
                'message' => 'Cannot cancel application. Internship has already been created.',
            ], 400);
        }

        // Cancel the application
        $application->cancel();

        return response()->json([
            'message' => 'Application cancelled successfully!',
        ]);
    }
}
