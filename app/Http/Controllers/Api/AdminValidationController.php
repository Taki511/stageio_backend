<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Internship;
use App\Models\InternshipAgreement;
use App\Services\AgreementPdfService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminValidationController extends Controller
{
    protected $pdfService;
    protected $notificationService;

    public function __construct(AgreementPdfService $pdfService, NotificationService $notificationService)
    {
        $this->pdfService = $pdfService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get the university domain from admin's email.
     */
    private function getAdminUniversityDomain(Request $request): string
    {
        $admin = $request->user();
        $adminProfile = $admin->adminProfile;
        
        if (!$adminProfile || !$adminProfile->university_email) {
            return '';
        }

        // Extract domain from email (e.g., taqichennouf@univ-constantine2.com -> univ-constantine2.com)
        $email = $adminProfile->university_email;
        $parts = explode('@', $email);
        
        return count($parts) === 2 ? $parts[1] : '';
    }

    /**
     * Check if student belongs to the same university as admin.
     */
    private function isSameUniversity(Request $request, $student): bool
    {
        $adminDomain = $this->getAdminUniversityDomain($request);
        
        if (empty($adminDomain)) {
            return false;
        }

        $studentProfile = $student->studentProfile;
        
        if (!$studentProfile || !$studentProfile->university_email) {
            return false;
        }

        $studentEmail = $studentProfile->university_email;
        $studentDomain = explode('@', $studentEmail)[1] ?? '';

        return $adminDomain === $studentDomain;
    }

    /**
     * Admin validates a confirmed application and creates an internship.
     * Only for students from the same university as the admin.
     */
    public function validateInternship(Request $request, string $applicationId)
    {
        // Only admins can validate
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. Only administrators can validate internships.',
            ], 403);
        }

        $application = Application::with(['student.studentProfile', 'internshipOffer.companyProfile'])
            ->find($applicationId);

        if (!$application) {
            return response()->json([
                'message' => 'Application not found.',
            ], 404);
        }

        // Check if student belongs to the same university
        if (!$this->isSameUniversity($request, $application->student)) {
            return response()->json([
                'message' => 'Forbidden. You can only validate applications from students of your own university.',
            ], 403);
        }

        // Check if application is accepted and confirmed
        if ($application->status !== Application::STATUS_ACCEPTED || !$application->is_confirmed) {
            return response()->json([
                'message' => 'Application must be accepted by recruiter and confirmed by student before validation.',
                'current_status' => $application->status,
                'is_confirmed' => $application->is_confirmed,
            ], 400);
        }

        // Check if internship already exists
        if ($application->internship) {
            return response()->json([
                'message' => 'Internship already exists for this application.',
                'internship' => $application->internship,
            ], 400);
        }

        // Get offer details for auto-calculating dates
        $offer = $application->internshipOffer;
        
        // Use offer's start_date or default to today
        $startDate = $offer->start_date ?? now();
        
        // Calculate end date based on offer duration (in weeks)
        $endDate = (clone $startDate)->addWeeks($offer->duration);

        // Create internship with auto-calculated dates
        $internship = Internship::create([
            'application_id' => $applicationId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => Internship::STATUS_ONGOING,
        ]);

        // Update application status to validated
        $application->update(['status' => Application::STATUS_VALIDATED]);

        // Send notification to student
        $this->notificationService->notifyStudentInternshipValidated($application);

        return response()->json([
            'message' => 'Internship validated and created successfully!',
            'data' => [
                'internship' => $internship->load('application.student', 'application.internshipOffer'),
                'calculated_dates' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'duration_weeks' => $offer->duration,
                ],
            ],
        ], 201);
    }

    /**
     * Admin generates internship agreement with signature.
     * Only for students from the same university as the admin.
     */
    public function generateAgreement(Request $request, string $internshipId)
    {
        // Only admins can generate agreements
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. Only administrators can generate internship agreements.',
            ], 403);
        }

        $internship = Internship::with(['application.student.studentProfile', 'application.internshipOffer.companyProfile'])
            ->find($internshipId);

        if (!$internship) {
            return response()->json([
                'message' => 'Internship not found.',
            ], 404);
        }

        // Check if student belongs to the same university
        if (!$this->isSameUniversity($request, $internship->application->student)) {
            return response()->json([
                'message' => 'Forbidden. You can only generate agreements for students of your own university.',
            ], 403);
        }

        // Check if agreement already exists
        if ($internship->agreement) {
            // If agreement exists but no PDF, regenerate PDF
            if (!$internship->agreement->pdf_file) {
                try {
                    $pdfPath = $this->pdfService->generate($internship->agreement);
                    $internship->agreement->update([
                        'pdf_file' => $pdfPath,
                        'signature_status' => true,
                    ]);

                    return response()->json([
                        'message' => 'Agreement PDF generated and signed successfully!',
                        'data' => [
                            'agreement' => $internship->agreement->load('internship.application.student', 'internship.application.internshipOffer'),
                            'pdf_url' => $this->pdfService->getUrl($internship->agreement),
                        ],
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'PDF generation failed: ' . $e->getMessage(),
                    ], 500);
                }
            }

            return response()->json([
                'message' => 'Agreement already exists for this internship.',
                'agreement' => $internship->agreement,
                'pdf_url' => $this->pdfService->getUrl($internship->agreement),
            ], 400);
        }

        // Create agreement with signature marked as true
        $agreement = InternshipAgreement::create([
            'internship_id' => $internshipId,
            'admin_id' => $request->user()->id,
            'generated_date' => now(),
            'signature_status' => true, // Auto-mark as signed
            'pdf_file' => null,
        ]);

        // Generate PDF
        try {
            $pdfPath = $this->pdfService->generate($agreement);
            $agreement->update(['pdf_file' => $pdfPath]);

            return response()->json([
                'message' => 'Internship agreement generated and signed successfully!',
                'data' => [
                    'agreement' => $agreement->load('internship.application.student', 'internship.application.internshipOffer'),
                    'pdf_url' => $this->pdfService->getUrl($agreement),
                    'signed' => true,
                ],
            ], 201);
        } catch (\Exception $e) {
            // If PDF generation fails, still return the agreement but without PDF
            return response()->json([
                'message' => 'Agreement created and signed, but PDF generation failed: ' . $e->getMessage(),
                'data' => $agreement->load('internship.application.student', 'internship.application.internshipOffer'),
            ], 201);
        }
    }

    /**
     * Download the generated PDF agreement.
     * Only for students from the same university as the admin.
     */
    public function downloadAgreement(Request $request, string $agreementId)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. Only administrators can download agreements.',
            ], 403);
        }

        $agreement = InternshipAgreement::with('internship.application.student')->find($agreementId);

        if (!$agreement) {
            return response()->json([
                'message' => 'Agreement not found.',
            ], 404);
        }

        // Check if student belongs to the same university
        if (!$this->isSameUniversity($request, $agreement->internship->application->student)) {
            return response()->json([
                'message' => 'Forbidden. You can only download agreements for students of your own university.',
            ], 403);
        }

        // Generate PDF if not exists
        if (!$agreement->pdf_file || !Storage::disk('public')->exists($agreement->pdf_file)) {
            try {
                $pdfPath = $this->pdfService->generate($agreement);
                $agreement->update(['pdf_file' => $pdfPath]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'PDF generation failed: ' . $e->getMessage(),
                ], 500);
            }
        }

        return $this->pdfService->download($agreement);
    }

    /**
     * Regenerate PDF for an agreement.
     * Only for students from the same university as the admin.
     */
    public function regeneratePdf(Request $request, string $agreementId)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. Only administrators can regenerate PDFs.',
            ], 403);
        }

        $agreement = InternshipAgreement::with('internship.application.student')->find($agreementId);

        if (!$agreement) {
            return response()->json([
                'message' => 'Agreement not found.',
            ], 404);
        }

        // Check if student belongs to the same university
        if (!$this->isSameUniversity($request, $agreement->internship->application->student)) {
            return response()->json([
                'message' => 'Forbidden. You can only regenerate PDFs for students of your own university.',
            ], 403);
        }

        try {
            // Delete old PDF if exists
            if ($agreement->pdf_file && Storage::disk('public')->exists($agreement->pdf_file)) {
                Storage::disk('public')->delete($agreement->pdf_file);
            }

            // Generate new PDF
            $pdfPath = $this->pdfService->generate($agreement);
            $agreement->update(['pdf_file' => $pdfPath]);

            return response()->json([
                'message' => 'PDF regenerated successfully!',
                'data' => [
                    'agreement' => $agreement,
                    'pdf_url' => $this->pdfService->getUrl($agreement),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'PDF regeneration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin updates agreement signature status.
     */
    public function updateSignatureStatus(Request $request, string $agreementId)
    {
        // Only admins can update signature status
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. Only administrators can update agreement signature status.',
            ], 403);
        }

        $agreement = InternshipAgreement::find($agreementId);

        if (!$agreement) {
            return response()->json([
                'message' => 'Agreement not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'signature_status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $agreement->update([
            'signature_status' => $request->signature_status,
        ]);

        return response()->json([
            'message' => 'Agreement signature status updated successfully!',
            'data' => $agreement,
        ]);
    }

    /**
     * Admin views pending validations (confirmed applications) for students from the same university.
     */
    public function pendingValidations(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. Only administrators can view pending validations.',
            ], 403);
        }

        $adminDomain = $this->getAdminUniversityDomain($request);

        if (empty($adminDomain)) {
            return response()->json([
                'message' => 'Your university email is not configured properly.',
            ], 400);
        }

        $applications = Application::where('status', Application::STATUS_ACCEPTED)
            ->where('is_confirmed', true)
            ->whereDoesntHave('internship')
            ->whereHas('student.studentProfile', function ($query) use ($adminDomain) {
                $query->where('university_email', 'like', '%@' . $adminDomain);
            })
            ->with(['student.studentProfile', 'internshipOffer.companyProfile'])
            ->orderBy('confirmed_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'university_domain' => $adminDomain,
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
     * Admin views internships for students from the same university.
     */
    public function allInternships(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. Only administrators can view internships.',
            ], 403);
        }

        $adminDomain = $this->getAdminUniversityDomain($request);

        if (empty($adminDomain)) {
            return response()->json([
                'message' => 'Your university email is not configured properly.',
            ], 400);
        }

        $internships = Internship::with([
                'application.student.studentProfile',
                'application.internshipOffer.companyProfile',
                'agreement'
            ])
            ->whereHas('application.student.studentProfile', function ($query) use ($adminDomain) {
                $query->where('university_email', 'like', '%@' . $adminDomain);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'university_domain' => $adminDomain,
            'data' => $internships->items(),
            'meta' => [
                'current_page' => $internships->currentPage(),
                'last_page' => $internships->lastPage(),
                'per_page' => $internships->perPage(),
                'total' => $internships->total(),
            ],
        ]);
    }

    /**
     * Admin views agreements for students from the same university.
     */
    public function allAgreements(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. Only administrators can view agreements.',
            ], 403);
        }

        $adminDomain = $this->getAdminUniversityDomain($request);

        if (empty($adminDomain)) {
            return response()->json([
                'message' => 'Your university email is not configured properly.',
            ], 400);
        }

        $agreements = InternshipAgreement::with([
                'internship.application.student.studentProfile',
                'internship.application.internshipOffer.companyProfile',
                'admin'
            ])
            ->whereHas('internship.application.student.studentProfile', function ($query) use ($adminDomain) {
                $query->where('university_email', 'like', '%@' . $adminDomain);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        // Add PDF URLs to response
        $agreements->getCollection()->transform(function ($agreement) {
            $agreement->pdf_url = $this->pdfService->getUrl($agreement);
            return $agreement;
        });

        return response()->json([
            'university_domain' => $adminDomain,
            'data' => $agreements->items(),
            'meta' => [
                'current_page' => $agreements->currentPage(),
                'last_page' => $agreements->lastPage(),
                'per_page' => $agreements->perPage(),
                'total' => $agreements->total(),
            ],
        ]);
    }

    /**
     * Mark internship as completed.
     * Only for students from the same university as the admin.
     */
    public function completeInternship(Request $request, string $internshipId)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. Only administrators can complete internships.',
            ], 403);
        }

        $internship = Internship::with('application.student')->find($internshipId);

        if (!$internship) {
            return response()->json([
                'message' => 'Internship not found.',
            ], 404);
        }

        // Check if student belongs to the same university
        if (!$this->isSameUniversity($request, $internship->application->student)) {
            return response()->json([
                'message' => 'Forbidden. You can only complete internships for students of your own university.',
            ], 403);
        }

        if ($internship->status === Internship::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'Internship is already completed.',
            ], 400);
        }

        $internship->update(['status' => Internship::STATUS_COMPLETED]);

        return response()->json([
            'message' => 'Internship marked as completed!',
            'data' => $internship,
        ]);
    }

    /**
     * Admin rejects an application that was accepted by recruiter.
     * Only for students from the same university as the admin.
     */
    public function rejectApplication(Request $request, string $applicationId)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. Only administrators can reject applications.',
            ], 403);
        }

        $application = Application::with(['student.studentProfile', 'internshipOffer.companyProfile'])
            ->find($applicationId);

        if (!$application) {
            return response()->json([
                'message' => 'Application not found.',
            ], 404);
        }

        // Check if student belongs to the same university
        if (!$this->isSameUniversity($request, $application->student)) {
            return response()->json([
                'message' => 'Forbidden. You can only reject applications from students of your own university.',
            ], 403);
        }

        // Check if application is accepted (admin can only reject accepted applications)
        if ($application->status !== Application::STATUS_ACCEPTED) {
            return response()->json([
                'message' => 'Application cannot be rejected. Current status: ' . $application->status,
            ], 400);
        }

        // Check if internship already exists (cannot reject if already validated)
        if ($application->internship) {
            return response()->json([
                'message' => 'Cannot reject application. Internship has already been created.',
            ], 400);
        }

        // Reject the application (set back to refused status)
        $application->update(['status' => Application::STATUS_REFUSED]);

        // Update offer status if it was closed (reopening the spot)
        $offer = $application->internshipOffer;
        if ($offer && $offer->status === InternshipOffer::STATUS_CLOSED) {
            $offer->autoUpdateStatus();
        }

        // Send notification to student
        $this->notificationService->notifyStudentApplicationRejected($application);

        return response()->json([
            'message' => 'Application rejected successfully!',
            'data' => $application->load(['student.studentProfile', 'internshipOffer.companyProfile']),
        ]);
    }
}
