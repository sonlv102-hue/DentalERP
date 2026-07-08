<?php

namespace App\Services;

use App\Enums\PurchaseInvoiceStatus;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceService
{
    public function recalcTotals(PurchaseInvoice $invoice): void
    {
        $subtotal  = $invoice->items()->sum('amount');
        $vatAmount = $invoice->items()->selectRaw('SUM(amount * vat_rate / 100)')->value(DB::raw('SUM(amount * vat_rate / 100)')) ?? 0;
        $total     = $subtotal + $vatAmount;

        $invoice->update([
            'subtotal'   => (int) $subtotal,
            'vat_amount' => (int) round($vatAmount),
            'total'      => (int) round($total),
        ]);
    }

    public function addPayment(PurchaseInvoice $invoice, int $amount, string $method = 'cash'): void
    {
        if (!in_array($invoice->status->value, [PurchaseInvoiceStatus::Received->value, PurchaseInvoiceStatus::Draft->value])) {
            throw new \RuntimeException('Không thể thanh toán hóa đơn ở trạng thái hiện tại.');
        }

        DB::transaction(function () use ($invoice, $amount, $method) {
            $newPaid = $invoice->paid_amount + $amount;
            $status  = $newPaid >= $invoice->total
                ? PurchaseInvoiceStatus::Paid
                : PurchaseInvoiceStatus::Received;

            $invoice->update([
                'paid_amount'    => $newPaid,
                'payment_method' => $method,
                'status'         => $status,
            ]);
        });
    }

    public function receive(PurchaseInvoice $invoice): void
    {
        if ($invoice->status !== PurchaseInvoiceStatus::Draft) {
            throw new \RuntimeException('Chỉ có thể xác nhận hóa đơn ở trạng thái Nháp.');
        }
        $invoice->update(['status' => PurchaseInvoiceStatus::Received]);
    }

    public function cancel(PurchaseInvoice $invoice): void
    {
        if ($invoice->status === PurchaseInvoiceStatus::Paid) {
            throw new \RuntimeException('Không thể hủy hóa đơn đã thanh toán.');
        }
        $invoice->update(['status' => PurchaseInvoiceStatus::Cancelled]);
    }
}
