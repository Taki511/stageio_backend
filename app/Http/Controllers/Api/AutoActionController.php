<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\CompanyProfile;
use App\Models\InternshipOffer;
use App\Services\AutoActionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AutoActionController extends Controller
{
    private const RECRUITER_RESPONSE_DAYS = 14;
    private const STUDENT_CONFIRM_DAYS = 14;
    private const ADMIN_VALIDATE_DAYS = 7;

    /**
     * Get auto-action status for the authenticated user.
     */
    public function status(Request $request): JsonResponse
    {
        $now = Carbon::now();
        
        if ($request->user()->isStudent()) {
            return $this->getStudentStatus($request->user()->id, $now);
        }

        if ($request->user()->isRecruiter()) {
            return $this->getRecruiterStatus($request->user()->id, $now);
        }

        if ($request->user()->isAdmin()) {
            return $this->getAdminStatus($now);
        }

        return response()->json([
            'message' => 'Forbidden.',
        ], 403);
    }

    /**
     * Get student auto-action status.
     */
    private function getStudentStatus(int $studentId, Carbon $now): JsonResponse
    {
        // Pending applications (waiting for recruiter)
        $pendingApplications = Application::where('student_id', $studentId)
            ->where('status', Application::STATUS_PENDING)
            ->with('internshipOffer')
            ->get()
            ->map(function ($app) use ($now) {
                $daysLeft = self::RECRUITER_RESPONSE_DAYS - $app->created_at->diffInDays($now);
                return [
                    'application_id' => $app->id,
                    'offer_title' => $app->internshipOffer?->title ?? 'N/A',
                    'status' => $app->status,
                    'days_waiting' => $app->created_at->diffInDays($now),
                    'days_until_auto_cancel' => max(0, round($daysLeft)),
                    'applied_at' => $app->created_at->toDateTimeString(),
                ];
            });

        // Accepted applications (waiting for student confirmation)
        $acceptedApplications = Application::where('student_id', $studentId)
            ->where('status', Application::STATUS_ACCEPTED)
            ->where('is_confirmed', false)
            ->with('internshipOffer')
            ->get()
            ->map(function ($app) use ($now) {
                $daysLeft = self::STUDENT_CONFIRM_DAYS - $app->updated_at->diffInDays($now);
                return [
                    'application_id' => $app->id,
                    'offer_title' => $app->internshipOffer?->title ?? 'N/A',
                    'status' => $app->status,
                    'days_waiting' => $app->updated_at->diffInDays($now),
                    'days_until_auto_cancel' => max(0, round($daysLeft)),
                    'accepted_at' => $app->updated_at->toDateTimeString(),
                ];
            });

        // Confirmed applications (waiting for admin validation)
        $confirmedApplications = Application::where('student_id', $studentId)
            ->where('status', Application::STATUS_ACCEPTED)
            ->where('is_confirmed', true)
            ->whereDoesntHave('internship')
            ->with('internshipOffer')
            ->get()
            ->map(function ($app) use ($now) {
                $confirmedAt = $app->confirmed_at ?? $app->updated_at;
                $daysLeft = self::ADMIN_VALIDATE_DAYS - $confirmedAt->diffInDays($now);
                return [
                    'application_id' => $app->id,
                    'offer_title' => $app->internshipOffer?->title ?? 'N/A',
                    'status' => 'confirmed_pending_validation',
                    'days_waiting' => $confirmedAt->diffInDays($now),
                    'days_until_auto_validate' => max(0, round($daysLeft)),
                    'confirmed_at' => $confirmedAt->toDateTimeString(),
                ];
            });

        return response()->json([
            'timezone' => config('app.timezone'),
            'current_time' => $now->toDateTimeString(),
            'pending_applications' => $pendingApplications,
            'accepted_applications' => $acceptedApplications,
            'confirmed_applications' => $confirmedApplications,
            'rules' => [
                'recruiter_response_days' => self::RECRUITER_RESPONSE_DAYS,
                'student_confirm_days' => self::STUDENT_CONFIRM_DAYS,
                'admin_validate_days' => self::ADMIN_VALIDATE_DAYS,
            ],
        ]);
    }

    /**
     * Get recruiter auto-action status.
     */
    private function getRecruiterStatus(int $recruiterId, Carbon $now): JsonResponse
    {
        $companyProfile = CompanyProfile::where('recruiter_id', $recruiterId)->first();
        
        if (!$companyProfile) {
            return response()->json([
                'timezone' => config('app.timezone'),
                'current_time' => $now->toDateTimeString(),
                'pending_applications' => [],
                'message' => 'No company profile found.',
                'rules' => [
                    'recruiter_response_days' => self::RECRUITER_RESPONSE_DAYS,
                ],
            ]);
        }

        $offerIds = InternshipOffer::where('company_profile_id', $companyProfile->id)
            ->pluck('id');

        $pendingApplications = Application::whereIn('internship_offer_id', $offerIds)
            ->where('status', Application::STATUS_PENDING)
            ->with(['student.studentProfile', 'internshipOffer'])
            ->get()
            ->map(function ($app) use ($now) {
                $studentProfile = $app->student?->studentProfile;
                $studentName = $studentProfile 
                    ? $studentProfile->first_name . ' ' . $studentProfile->last_name
                    : ($app->student?->name ?? 'Unknown');
                
                $daysLeft = self::RECRUITER_RESPONSE_DAYS - $app->created_at->diffInDays($now);
                return [
                    'application_id' => $app->id,
                    'student_name' => $studentName,
                    'offer_title' => $app->internshipOffer?->title ?? 'N/A',
                    'days_waiting' => $app->created_at->diffInDays($now),
                    'days_until_auto_cancel' => max(0, round($daysLeft)),
                    'applied_at' => $app->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'timezone' => config('app.timezone'),
            'current_time' => $now->toDateTimeString(),
            'pending_applications' => $pendingApplications,
            'rules' => [
                'recruiter_response_days' => self::RECRUITER_RESPONSE_DAYS,
            ],
        ]);
    }

    /**
     * Get admin auto-action status.
     */
    private function getAdminStatus(Carbon $now): JsonResponse
    {
        $confirmedApplications = Application::where('status', Application::STATUS_ACCEPTED)
            ->where('is_confirmed', true)
            ->whereDoesntHave('internship')
            ->with(['student.studentProfile', 'internshipOffer'])
            ->get()
            ->map(function ($app) use ($now) {
                $studentProfile = $app->student?->studentProfile;
                $studentName = $studentProfile 
                    ? $studentProfile->first_name . ' ' . $studentProfile->last_name
                    : ($app->student?->name ?? 'Unknown');
                
                $confirmedAt = $app->confirmed_at ?? $app->updated_at;
                $daysLeft = self::ADMIN_VALIDATE_DAYS - $confirmedAt->diffInDays($now);
                return [
                    'application_id' => $app->id,
                    'student_name' => $studentName,
                    'offer_title' => $app->internshipOffer?->title ?? 'N/A',
                    'days_waiting' => $confirmedAt->diffInDays($now),
                    'days_until_auto_validate' => max(0, round($daysLeft)),
                    'confirmed_at' => $confirmedAt->toDateTimeString(),
                ];
            });

        return response()->json([
            'timezone' => config('app.timezone'),
            'current_time' => $now->toDateTimeString(),
            'confirmed_applications' => $confirmedApplications,
            'rules' => [
                'admin_validate_days' => self::ADMIN_VALIDATE_DAYS,
            ],
        ]);
    }

    /**
     * Manually trigger auto-actions (admin only).
     */
    public function trigger(Request $request, AutoActionService $service): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. Only administrators can trigger auto-actions.',
            ], 403);
        }

        $results = $service->runAll();

        return response()->json([
            'message' => 'Auto-actions completed successfully!',
            'results' => $results,
        ]);
    }
}
