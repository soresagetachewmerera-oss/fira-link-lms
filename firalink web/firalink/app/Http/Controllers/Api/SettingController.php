<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsRepository;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    protected $repository;
    protected $paymentGatewayService;

    public function __construct(SettingsRepository $repository, PaymentGatewayService $paymentGatewayService)
    {
        $this->repository = $repository;
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Get all public settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $settings = [
            'platform' => $this->repository->get('platform.general'),
            'branding' => $this->repository->get('branding.assets'),
            'contact' => $this->repository->get('contact.details'),
            'withdrawals' => $this->repository->get('withdrawals.settings'),
        ];

        // Ensure branding assets have full URLs
        if (isset($settings['branding'])) {
            $settings['branding']['primary_logo_url'] = branding_asset('primary_logo_path');
            $settings['branding']['dark_logo_url'] = branding_asset('dark_logo_path');
            $settings['branding']['favicon_url'] = branding_asset('favicon_path');
        }

        // Format currency data for the app
        $currencyCode = $settings['platform']['default_currency'] ?? currency_code();
        $settings['currency'] = [
            'code' => $currencyCode,
            'symbol' => currency_symbol($currencyCode),
        ];

        $settings['payment_methods'] = $this->paymentGatewayService->onlineGateways()->map(function ($gateway) {
            return [
                'identifier' => $gateway->identifier,
                'name' => $gateway->name,
                'description' => $gateway->description,
                'type' => $gateway->type,
                'is_enabled' => $gateway->is_enabled,
            ];
        })->values();

        $offlineGateway = $this->paymentGatewayService->offlineGateway();
        if ($offlineGateway && $offlineGateway->is_enabled) {
            $settings['payment_methods'][] = [
                'identifier' => $offlineGateway->identifier,
                'name' => $offlineGateway->name,
                'description' => $offlineGateway->description,
                'type' => $offlineGateway->type,
                'is_enabled' => $offlineGateway->is_enabled,
                'instructions' => $offlineGateway->offline_instructions,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }
}
