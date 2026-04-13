<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Internship;
use App\Models\CompanyProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoActionService
{
    // Time limits in days
    private const RECRUITER_RESPONSE_DAYS = 14;
    private const STUDENT_CONFIRM_DAYS = 14;
    private const ADMIN_VALIDATE_DAYS = 7;

    /**
     * Auto-cancel pending applications where recruiter didn't respond within 14 days.
     */
    public function autoCancelPendingApplications(): int
    {
        $cutoffDate = Carbon::now()->subDays(self::RECRUITER_RESPONSE_DAYS);
        
        $applications = Application::where('status', Application::STATUS_PENDING)
            ->where('created_at', '<', $cutoffDate)
            ->get();

        $count = 0;
        foreach ($applications as $application) {
            $application->cancel();
            $count++;
            
            Log::info('Auto-cancelled pending application', [
                'application_id' => $application->id,
                'student_id' => $application->student_id,
                'offer_id' => $application->internship_offer_id,
                'reason' => 'Recruiter did not respond within ' . self::RECRUITER_RESPONSE_DAYS . ' days',
            ]);
        }

        return $count;
    }

    /**
     * Auto-cancel pending applications for a specific student.
     */
    public function autoCancelPendingApplicationsForStudent(int $studentId): int
    {
        $cutoffDate = Carbon::now()->subDays(self::RECRUITER_RESPONSE_DAYS);
        
        $applications = Application::where('student_id', $studentId)
            ->where('status', Application::STATUS_PENDING)
            ->where('created_at', '<', $cutoffDate)
            ->get();

        $count = 0;
        foreach ($applications as $application) {
            $application->cancel();
            $count++;
        }

        return $count;
    }

    /**
     * Auto-cancel pending applications for a specific recruiter.
     */
    public function autoCancelPendingApplicationsForRecruiter(int $recruiterId): int
    {
        $cutoffDate = Carbon::now()->subDays(self::RECRUITER_RESPONSE_DAYS);
        
        $companyProfile = CompanyProfile::where('recruiter_id', $recruiterId)->first();
        
        if (!$companyProfile) {
            return 0;
        }

        $offerIds = \App\Models\InternshipOffer::where('company_profile_id', $companyProfile->id)
            ->pluck('id');

        $applications = Application::whereIn('internship_offer_id', $offerIds)
            ->where('status', Application::STATUS_PENDING)
            ->where('created_at', '<', $cutoffDate)
            ->get();

        $count = 0;
        foreach ($applications as $application) {
            $application->cancel();
            $count++;
        }

        return $count;
    }

    /**
     * Auto-cancel accepted applications where student didn't confirm within 14 days.
     */
    public function autoCancelUnconfirmedApplications(): int
    {
        $cutoffDate = Carbon::now()->subDays(self::STUDENT_CONFIRM_DAYS);
        
        $applications = Application::where('status', Application::STATUS_ACCEPTED)
            ->where('is_confirmed', false)
            ->where('accepted_at', '<', $cutoffDate)
            ->get();

        $count = 0;
        foreach ($applications as $application) {
            $application->cancel();
            $count++;
            
            Log::info('Auto-cancelled unconfirmed application', [
                'application_id' => $application->id,
                'student_id' => $application->student_id,
                'offer_id' => $application->internship_offer_id,
                'reason' => 'Student did not confirm within ' . self::STUDENT_CONFIRM_DAYS . ' days',
            ]);
        }

        return $count;
    }

    /**
     * Auto-cancel accepted applications where student didn't confirm within 14 days (for specific student).
     */
    public function autoCancelUnconfirmedApplicationsForStudent(int $studentId): int
    {
        $cutoffDate = Carbon::now()->subDays(self::STUDENT_CONFIRM_DAYS);
        
        $applications = Application::where('student_id', $studentId)
            ->where('status', Application::STATUS_ACCEPTED)
            ->where('is_confirmed', false)
            ->where('accepted_at', '<', $cutoffDate)
            ->get();

        $count = 0;
        foreach ($applications as $application) {
            $application->cancel();
            $count++;
        }

        return $count;
    }

    /**
     * Auto-validate confirmed applications where admin didn't respond within 7 days.
     */
    public function autoValidateConfirmedApplications(): int
    {
        $cutoffDate = Carbon::now()->subDays(self::ADMIN_VALIDATE_DAYS);
        
        $applications = Application::where('status', Application::STATUS_ACCEPTED)
            ->where('is_confirmed', true)
            ->where('confirmed_at', '<', $cutoffDate)
            ->whereDoesntHave('internship')
            ->get();

        $count = 0;
        foreach ($applications as $application) {
            // Get offer details for auto-calculating dates
            $offer = $application->internshipOffer;
            
            // Use offer's start_date or default to today
            $startDate = $offer->start_date ?? now();
            
            // Calculate end date based on offer duration (in weeks)
            $endDate = (clone $startDate)->addWeeks($offer->duration);

            // Create internship
            Internship::create([
                'application_id' => $application->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => Internship::STATUS_ONGOING,
            ]);

            // Update application status to validated
            $application->update(['status' => Application::STATUS_VALIDATED]);

            $count++;
            
            Log::info('Auto-validated confirmed application', [
                'application_id' => $application->id,
                'student_id' => $application->student_id,
                'offer_id' => $application->internship_offer_id,
                'reason' => 'Admin did not validate within ' . self::ADMIN_VALIDATE_DAYS . ' days',
            ]);
        }

        return $count;
    }

    /**
     * Auto-complete internships that have passed their end date.
     */
    public function autoCompleteInternships(): int
    {
        $today = Carbon::now()->startOfDay();
        
        $internships = Internship::where('status', Internship::STATUS_ONGOING)
            ->where('end_date', '<', $today)
            ->get();

        $count = 0;
        foreach ($internships as $internship) {
            $internship->update(['status' => Internship::STATUS_COMPLETED]);
            $count++;
            
            Log::info('Auto-completed internship', [
                'internship_id' => $internship->id,
                'application_id' => $internship->application_id,
                'end_date' => $internship->end_date->toDateString(),
            ]);
        }

        return $count;
    }

    /**
     * Run all auto-actions.
     */
    public function runAll(): array
    {
        return [
            'pending_cancelled' => $this->autoCancelPendingApplications(),
            'unconfirmed_cancelled' => $this->autoCancelUnconfirmedApplications(),
            'confirmed_validated' => $this->autoValidateConfirmedApplications(),
            'internships_completed' => $this->autoCompleteInternships(),
            'timestamp' => Carbon::now()->toDateTimeString(),
        ];
    }
}
