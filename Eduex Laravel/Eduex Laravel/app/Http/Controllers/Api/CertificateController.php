<?php
/**
 * Dynamic Certificates API controller.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    /**
     * Get all certificates for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $certificates = Enrollment::with('course.instructor')
            ->where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_COMPLETED)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'course_id' => $enrollment->course_id,
                    'title' => $enrollment->course->title,
                    'issued_date' => $enrollment->completion_date,
                    'issued_at' => $enrollment->updated_at?->toIso8601String(),
                    'certificate_id' => $enrollment->certificate_number,
                    'course_image' => $enrollment->course->thumbnail,
                    'instructor_name' => $enrollment->course->instructor->name ?? 'Instructor',
                    'download_url' => URL::temporarySignedRoute(
                        'api.certificates.download.signed',
                        now()->addDays(1),
                        ['id' => $enrollment->id]
                    ),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $certificates
        ]);
    }

    /**
     * Get single certificate details.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $enrollment = Enrollment::with('course.instructor')
            ->where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_COMPLETED)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'title' => $enrollment->course->title,
                'issued_date' => $enrollment->completion_date,
                'certificate_id' => $enrollment->certificate_number,
                'course_image' => $enrollment->course->thumbnail,
                'instructor_name' => $enrollment->course->instructor->name ?? 'Instructor',
                'download_url' => URL::temporarySignedRoute(
                    'api.certificates.download.signed',
                    now()->addDays(1),
                    ['id' => $enrollment->id]
                ),
            ]
        ]);
    }

    /**
     * Download certificate as PDF.
     */
    public function download(Request $request, $id)
    {
        $user = $request->user();

        $enrollment = Enrollment::with(['user', 'course.instructor'])
            ->where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_COMPLETED)
            ->findOrFail($id);

        $certificateData = [
            'student_name' => $enrollment->user?->name ?? 'Student',
            'course_title' => $enrollment->course?->title ?? 'Course',
            'instructor_name' => $enrollment->course?->instructor?->name ?? 'Instructor',
            'completion_date' => optional($enrollment->updated_at)->format('F d, Y'),
            'certificate_number' => $enrollment->certificate_number,
            'background_base64' => $this->certificateBackgroundBase64(),
        ];

        $pdf = Pdf::loadView('student.certificate', $certificateData)
            ->setPaper('a4', 'landscape')
            ->setOption('enable-local-file-access', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('enable_remote', true)
            ->setOption('margin-top', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0);

        $filename = 'certificate-' . Str::slug($certificateData['course_title']) . '-' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Download certificate using signed URL.
     */
    public function downloadSigned(Request $request, $id)
    {
        $enrollment = Enrollment::with(['user', 'course.instructor'])
            ->where('status', Enrollment::STATUS_COMPLETED)
            ->findOrFail($id);

        $certificateData = [
            'student_name' => $enrollment->user?->name ?? 'Student',
            'course_title' => $enrollment->course?->title ?? 'Course',
            'instructor_name' => $enrollment->course?->instructor?->name ?? 'Instructor',
            'completion_date' => optional($enrollment->updated_at)->format('F d, Y'),
            'certificate_number' => $enrollment->certificate_number,
            'background_base64' => $this->certificateBackgroundBase64(),
        ];

        $pdf = Pdf::loadView('student.certificate', $certificateData)
            ->setPaper('a4', 'landscape')
            ->setOption('enable-local-file-access', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('enable_remote', true)
            ->setOption('margin-top', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0);

        $filename = 'certificate-' . Str::slug($certificateData['course_title']) . '-' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Get the certificate background base64
     */
    private function certificateBackgroundBase64(): ?string
    {
        $path = public_path('assets/front/img/certificate.png');

        if (!file_exists($path)) {
            return null;
        }

        return base64_encode(file_get_contents($path));
    }
}
