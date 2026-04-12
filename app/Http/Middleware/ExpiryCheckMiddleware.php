<?php

namespace App\Http\Middleware;

use App\Models\Application;
use App\Models\Internship;
use App\Services\AutoActionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExpiryCheckMiddleware
{
    protected $autoActionService;

    public function __construct(AutoActionService $autoActionService)
    {
        $this->autoActionService = $autoActionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only run if user is authenticated
        if ($request->user()) {
            $this->runExpiryChecks($request);
        }

        return $next($request);
    }

    /**
     * Run expiry checks based on user role.
     */
    private function runExpiryChecks(Request $request): void
    {
        $user = $request->user();

        // Auto-complete internships that have passed end date (for all users)
        $this->autoActionService->autoCompleteInternships();

        if ($user->isStudent()) {
            $this->checkStudentApplications($user->id);
        }

        if ($user->isRecruiter()) {
            $this->checkRecruiterApplications($user->id);
        }

        if ($user->isAdmin()) {
            $this->checkAdminApplications();
        }
    }

    /**
     * Check and auto-cancel student applications.
     */
    private function checkStudentApplications(int $studentId): void
    {
        // Auto-cancel pending applications waiting for recruiter (14 days)
        $this->autoActionService->autoCancelPendingApplicationsForStudent($studentId);

        // Auto-cancel accepted applications not confirmed (14 days)
        $this->autoActionService->autoCancelUnconfirmedApplicationsForStudent($studentId);
    }

    /**
     * Check and auto-cancel recruiter applications.
     */
    private function checkRecruiterApplications(int $recruiterId): void
    {
        // Auto-cancel pending applications this recruiter hasn't responded to (14 days)
        $this->autoActionService->autoCancelPendingApplicationsForRecruiter($recruiterId);
    }

    /**
     * Check and auto-validate admin applications.
     */
    private function checkAdminApplications(): void
    {
        // Auto-validate confirmed applications not validated (7 days)
        $this->autoActionService->autoValidateConfirmedApplications();
    }
}
