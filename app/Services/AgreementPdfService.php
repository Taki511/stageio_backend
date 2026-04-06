<?php

namespace App\Services;

use App\Models\InternshipAgreement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class AgreementPdfService
{
    /**
     * Generate PDF for Internship Agreement.
     */
    public function generate(InternshipAgreement $agreement): string
    {
        $agreement->load([
            'internship.application.student.studentProfile',
            'internship.application.internshipOffer.companyProfile',
            'admin.adminProfile'
        ]);

        $internship = $agreement->internship;
        $application = $internship->application;
        $student = $application->student;
        $studentProfile = $student->studentProfile;
        $offer = $application->internshipOffer;
        $company = $offer->companyProfile;
        $recruiter = $company->recruiter;
        $admin = $agreement->admin;

        $data = [
            'agreement_number' => 'AGR-' . str_pad($agreement->id, 6, '0', STR_PAD_LEFT),
            'generated_date' => $agreement->generated_date->format('d/m/Y'),
            
            // Student Information
            'student_name' => $studentProfile->first_name . ' ' . $studentProfile->last_name,
            'student_email' => $student->email,
            'student_university_email' => $studentProfile->university_email,
            
            // Company Information
            'company_name' => $company->name,
            'company_address' => $company->address,
            'company_wilaya' => $company->wilaya,
            'company_description' => $company->description,
            
            // Recruiter Information
            'recruiter_name' => $recruiter->recruiterProfile->first_name . ' ' . $recruiter->recruiterProfile->last_name,
            'recruiter_email' => $recruiter->recruiterProfile->company_email,
            
            // Internship Information
            'offer_title' => $offer->title,
            'offer_description' => $offer->description,
            'internship_type' => $this->formatInternshipType($offer->internship_type),
            'duration_weeks' => $offer->duration,
            'start_date' => $internship->start_date->format('d/m/Y'),
            'end_date' => $internship->end_date->format('d/m/Y'),
            
            // Admin Information
            'admin_name' => $admin->adminProfile->first_name . ' ' . $admin->adminProfile->last_name,
            'admin_university_email' => $admin->adminProfile->university_email,
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.agreement', $data);
        
        // Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');
        
        // Generate filename
        $filename = 'agreement_' . $agreement->id . '_' . time() . '.pdf';
        $path = 'agreements/' . $filename;
        
        // Store PDF
        Storage::disk('public')->put($path, $pdf->output());
        
        return $path;
    }

    /**
     * Format internship type for display.
     */
    private function formatInternshipType(string $type): string
    {
        return match($type) {
            'full_time' => 'Temps plein (Full Time)',
            'part_time' => 'Temps partiel (Part Time)',
            'remote' => 'Télétravail (Remote)',
            default => $type,
        };
    }

    /**
     * Download the generated PDF.
     */
    public function download(InternshipAgreement $agreement)
    {
        if (!$agreement->pdf_file || !Storage::disk('public')->exists($agreement->pdf_file)) {
            // Generate if not exists
            $path = $this->generate($agreement);
            $agreement->update(['pdf_file' => $path]);
        }

        return Storage::disk('public')->download($agreement->pdf_file, 'Internship_Agreement.pdf');
    }

    /**
     * Get PDF URL.
     */
    public function getUrl(InternshipAgreement $agreement): ?string
    {
        if (!$agreement->pdf_file) {
            return null;
        }

        return Storage::disk('public')->url($agreement->pdf_file);
    }
}
