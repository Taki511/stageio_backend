<?php

namespace App\Services;

use App\Mail\ApplicationStatusNotification;
use App\Mail\PasswordResetNotification;
use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send notification to student when recruiter accepts their application.
     */
    public function notifyStudentApplicationAccepted(Application $application): void
    {
        try {
            $application->load(['student.studentProfile', 'internshipOffer.companyProfile']);
            
            $student = $application->student;
            $offer = $application->internshipOffer;
            
            if (!$student || !$offer) {
                Log::error('Cannot send acceptance notification: missing student or offer', [
                    'application_id' => $application->id,
                    'has_student' => !!$student,
                    'has_offer' => !!$offer,
                ]);
                return;
            }

            $studentName = $this->getStudentName($student);
            $companyName = $offer->companyProfile?->name ?? 'Unknown Company';
            
            $data = [
                'recipient_type' => 'student',
                'notification_type' => 'application_accepted',
                'student_name' => $studentName,
                'offer_title' => $offer->title,
                'company_name' => $companyName,
                'days_to_confirm' => 14,
                'confirm_deadline' => now()->addDays(14)->format('d/m/Y'),
                'action_url' => config('app.url') . '/applications',
                'action_text' => 'View Application',
            ];
            
            Mail::to($student->email)->send(new ApplicationStatusNotification($data));
            
            Log::info('Application acceptance notification sent', [
                'application_id' => $application->id,
                'student_email' => $student->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send application acceptance notification', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to student when recruiter refuses their application.
     */
    public function notifyStudentApplicationRefused(Application $application): void
    {
        try {
            $application->load(['student.studentProfile', 'internshipOffer.companyProfile']);
            
            $student = $application->student;
            $offer = $application->internshipOffer;
            
            if (!$student || !$offer) {
                Log::error('Cannot send refusal notification: missing student or offer', [
                    'application_id' => $application->id,
                ]);
                return;
            }

            $studentName = $this->getStudentName($student);
            $companyName = $offer->companyProfile?->name ?? 'Unknown Company';
            
            $data = [
                'recipient_type' => 'student',
                'notification_type' => 'application_refused',
                'student_name' => $studentName,
                'offer_title' => $offer->title,
                'company_name' => $companyName,
                'action_url' => config('app.url') . '/internship-offers',
                'action_text' => 'Browse Other Offers',
            ];
            
            Mail::to($student->email)->send(new ApplicationStatusNotification($data));
            
            Log::info('Application refusal notification sent', [
                'application_id' => $application->id,
                'student_email' => $student->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send application refusal notification', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to student when admin validates their internship.
     */
    public function notifyStudentInternshipValidated(Application $application): void
    {
        try {
            $application->load(['student.studentProfile', 'internshipOffer.companyProfile', 'internship']);
            
            $student = $application->student;
            $offer = $application->internshipOffer;
            $internship = $application->internship;
            
            if (!$student || !$offer || !$internship) {
                Log::error('Cannot send validation notification: missing data', [
                    'application_id' => $application->id,
                    'has_student' => !!$student,
                    'has_offer' => !!$offer,
                    'has_internship' => !!$internship,
                ]);
                return;
            }

            $studentName = $this->getStudentName($student);
            $companyName = $offer->companyProfile?->name ?? 'Unknown Company';
            
            $data = [
                'recipient_type' => 'student',
                'notification_type' => 'internship_validated',
                'student_name' => $studentName,
                'offer_title' => $offer->title,
                'company_name' => $companyName,
                'start_date' => $internship->start_date->format('d/m/Y'),
                'end_date' => $internship->end_date->format('d/m/Y'),
                'action_url' => config('app.url') . '/my-applications',
                'action_text' => 'View Internship Details',
            ];
            
            Mail::to($student->email)->send(new ApplicationStatusNotification($data));
            
            Log::info('Internship validation notification sent', [
                'application_id' => $application->id,
                'student_email' => $student->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send internship validation notification', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to student when admin rejects their application.
     */
    public function notifyStudentApplicationRejected(Application $application): void
    {
        try {
            $application->load(['student.studentProfile', 'internshipOffer.companyProfile']);
            
            $student = $application->student;
            $offer = $application->internshipOffer;
            
            if (!$student || !$offer) {
                Log::error('Cannot send rejection notification: missing student or offer', [
                    'application_id' => $application->id,
                ]);
                return;
            }

            $studentName = $this->getStudentName($student);
            $companyName = $offer->companyProfile?->name ?? 'Unknown Company';
            
            $data = [
                'recipient_type' => 'student',
                'notification_type' => 'application_rejected',
                'student_name' => $studentName,
                'offer_title' => $offer->title,
                'company_name' => $companyName,
                'action_url' => config('app.url') . '/internship-offers',
                'action_text' => 'Browse Other Offers',
            ];
            
            Mail::to($student->email)->send(new ApplicationStatusNotification($data));
            
            Log::info('Application rejection notification sent', [
                'application_id' => $application->id,
                'student_email' => $student->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send application rejection notification', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send password reset notification to user.
     */
    public function notifyPasswordReset(User $user, string $resetToken): void
    {
        try {
            $data = [
                'user_name' => $user->name ?? 'User',
                'reset_url' => rtrim(config('app.url'), '/') . '/api/reset-password?token=' . $resetToken . '&email=' . urlencode($user->email),
                'expires_in' => '60 minutes',
            ];
            
            Mail::to($user->email)->send(new PasswordResetNotification($data));
            
            Log::info('Password reset notification sent', [
                'user_email' => $user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send password reset notification', [
                'user_email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get student name from profile or user.
     */
    private function getStudentName(User $student): string
    {
        if ($student->studentProfile) {
            return $student->studentProfile->first_name . ' ' . $student->studentProfile->last_name;
        }
        return $student->name ?? 'Student';
    }
}
