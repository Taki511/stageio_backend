<?php

namespace App\Services;

use App\Mail\ApplicationStatusNotification;
use App\Mail\PasswordResetNotification;
use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send notification to student when recruiter accepts their application.
     */
    public function notifyStudentApplicationAccepted(Application $application): void
    {
        $application->load(['student', 'internshipOffer.companyProfile']);
        
        $student = $application->student;
        $offer = $application->internshipOffer;
        
        $data = [
            'recipient_type' => 'student',
            'notification_type' => 'application_accepted',
            'student_name' => $student->name,
            'offer_title' => $offer->title,
            'company_name' => $offer->companyProfile->name,
            'days_to_confirm' => 14,
            'confirm_deadline' => now()->addDays(14)->format('d/m/Y'),
            'action_url' => config('app.url') . '/applications',
            'action_text' => 'View Application',
        ];
        
        Mail::to($student->email)->send(new ApplicationStatusNotification($data));
    }

    /**
     * Send notification to student when recruiter refuses their application.
     */
    public function notifyStudentApplicationRefused(Application $application): void
    {
        $application->load(['student', 'internshipOffer.companyProfile']);
        
        $student = $application->student;
        $offer = $application->internshipOffer;
        
        $data = [
            'recipient_type' => 'student',
            'notification_type' => 'application_refused',
            'student_name' => $student->name,
            'offer_title' => $offer->title,
            'company_name' => $offer->companyProfile->name,
            'action_url' => config('app.url') . '/internship-offers',
            'action_text' => 'Browse Other Offers',
        ];
        
        Mail::to($student->email)->send(new ApplicationStatusNotification($data));
    }

    /**
     * Send notification to student when admin validates their internship.
     */
    public function notifyStudentInternshipValidated(Application $application): void
    {
        $application->load(['student', 'internshipOffer.companyProfile', 'internship']);
        
        $student = $application->student;
        $offer = $application->internshipOffer;
        $internship = $application->internship;
        
        $data = [
            'recipient_type' => 'student',
            'notification_type' => 'internship_validated',
            'student_name' => $student->name,
            'offer_title' => $offer->title,
            'company_name' => $offer->companyProfile->name,
            'start_date' => $internship->start_date->format('d/m/Y'),
            'end_date' => $internship->end_date->format('d/m/Y'),
            'action_url' => config('app.url') . '/my-applications',
            'action_text' => 'View Internship Details',
        ];
        
        Mail::to($student->email)->send(new ApplicationStatusNotification($data));
    }

    /**
     * Send notification to student when admin rejects their application.
     */
    public function notifyStudentApplicationRejected(Application $application): void
    {
        $application->load(['student', 'internshipOffer.companyProfile']);
        
        $student = $application->student;
        $offer = $application->internshipOffer;
        
        $data = [
            'recipient_type' => 'student',
            'notification_type' => 'application_rejected',
            'student_name' => $student->name,
            'offer_title' => $offer->title,
            'company_name' => $offer->companyProfile->name,
            'action_url' => config('app.url') . '/internship-offers',
            'action_text' => 'Browse Other Offers',
        ];
        
        Mail::to($student->email)->send(new ApplicationStatusNotification($data));
    }

    /**
     * Send password reset notification to user.
     */
    public function notifyPasswordReset(User $user, string $resetToken): void
    {
        $data = [
            'user_name' => $user->name,
            'reset_url' => rtrim(config('app.url'), '/') . '/api/reset-password?token=' . $resetToken . '&email=' . urlencode($user->email),
            'expires_in' => '60 minutes',
        ];
        
        Mail::to($user->email)->send(new PasswordResetNotification($data));
    }
}
