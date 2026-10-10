<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\BundleCourse;
use App\Models\BundleEnrollment;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Http\Requests\CheckoutRequest;
use App\Services\PaymentGatewayService;
use App\Services\SettingsRepository;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BundleController extends Controller
{
    protected PaymentGatewayService $paymentGatewayService;
    protected SettingsRepository $settingsRepository;
    protected FileUploadService $fileUploadService;

    public function __construct(
        PaymentGatewayService $paymentGatewayService,
        SettingsRepository $settingsRepository,
        FileUploadService $fileUploadService
    ) {
        $this->paymentGatewayService = $paymentGatewayService;
        $this->settingsRepository = $settingsRepository;
        $this->fileUploadService = $fileUploadService;
    }

    public function index()
    {
        $bundles = Bundle::with(['courses' => function ($q) {
            $q->select('courses.id', 'title', 'featured_image', 'regular_price');
        }, 'vendor'])
        ->where('status', 'active')
        ->where('approval_status', 'approved')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $bundles
        ]);
    }

    public function show($id)
    {
        $bundle = Bundle::with(['courses.instructor', 'vendor'])
            ->where('status', 'active')
            ->where('approval_status', 'approved')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $bundle
        ]);
    }



    private function createCourseEnrollments($bundle, $user, $payment, $status)
    {
        $ownedCourseIds = Enrollment::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'completed'])
                ->pluck('course_id')
                ->toArray();

        foreach ($bundle->courses as $course) {
            if (!in_array($course->id, $ownedCourseIds)) {
                Enrollment::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'payment_id' => $payment ? $payment->id : null,
                        'status' => $status,
                        'enrolled_at' => $status === 'approved' ? now() : null,
                    ]
                );
            }
        }
    }
}
