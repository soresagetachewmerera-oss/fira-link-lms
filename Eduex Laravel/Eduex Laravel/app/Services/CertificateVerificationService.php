<?php

namespace App\Services;

use App\Models\Enrollment;

/**
 * Certificate Verification Service
 *
 * Handles verification of course completion certificates.
 */
class CertificateVerificationService
{
    /**
     * Verify a certificate code.
     *
     * @param string|null $code
     * @return array
     */
    public function verify(?string $code): array
    {
        if (empty($code)) {
            return [
                'is_valid' => false,
                'message' => __('frontend.certificate_code_required') ?? 'Certificate code is required.',
            ];
        }

        $code = strtoupper(trim($code));

        $enrollmentId = null;

        if (preg_match('/^CERT-(\d+)-([A-Z0-9]+)$/i', $code, $matches)) {
            $enrollmentId = (int) $matches[1];
        } elseif (preg_match('/^CERT-(\d+)$/i', $code, $matches)) {
            $enrollmentId = (int) $matches[1];
        } elseif (is_numeric($code)) {
            $enrollmentId = (int) $code;
        }

        if (! $enrollmentId) {
            return [
                'is_valid' => false,
                'code_entered' => $code,
                'message' => __('frontend.certificate_invalid_format') ?? 'Invalid certificate code format. Example format: CERT-000012-A1B2C3',
            ];
        }

        $enrollment = Enrollment::with(['user', 'course.instructor'])
            ->where('id', $enrollmentId)
            ->where('status', Enrollment::STATUS_COMPLETED)
            ->first();

        if (! $enrollment) {
            return [
                'is_valid' => false,
                'code_entered' => $code,
                'message' => __('frontend.certificate_not_found') ?? 'No authentic certificate record was found for this code.',
            ];
        }

        $officialCertNumber = strtoupper($enrollment->certificate_number);

        // Check exact match or valid prefix match
        if ($code !== $officialCertNumber && $code !== sprintf('CERT-%06d', $enrollmentId) && (string)$enrollmentId !== $code) {
            // Check hash mismatch
            return [
                'is_valid' => false,
                'code_entered' => $code,
                'message' => __('frontend.certificate_checksum_mismatch') ?? 'Certificate code checksum does not match our official records.',
            ];
        }

        return [
            'is_valid' => true,
            'code_entered' => $code,
            'certificate_number' => $officialCertNumber,
            'student_name' => $enrollment->user?->name ?? 'Student',
            'course_title' => $enrollment->course?->title ?? 'Course',
            'course_id' => $enrollment->course_id,
            'instructor_name' => $enrollment->course?->instructor?->name ?? 'Instructor',
            'completion_date' => optional($enrollment->updated_at)->format('F d, Y'),
            'issue_date' => optional($enrollment->updated_at)?->toIso8601String(),
            'verification_url' => route('verify-certificate', ['code' => $officialCertNumber]),
        ];
    }
}
