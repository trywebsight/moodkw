<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoiceService
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    public function generatePdf(Order $order): \Barryvdh\DomPDF\PDF
    {
        $order->load(['product', 'governorate', 'area']);
        $settings = $this->settingsService->get();

        return Pdf::loadView('invoices.order', [
            'order' => $order,
            'settings' => $settings,
            'storeName' => $this->settingsService->getStoreName(),
            'currency' => $this->settingsService->getCurrency(),
        ])->setPaper('a4');
    }

    public function download(Order $order): Response
    {
        return $this->generatePdf($order)->download("invoice-{$order->order_number}.pdf");
    }

    public function stream(Order $order): Response
    {
        return $this->generatePdf($order)->stream("invoice-{$order->order_number}.pdf");
    }
}
