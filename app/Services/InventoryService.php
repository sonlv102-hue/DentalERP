<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryItem;
use App\Models\InventoryServiceTemplate;
use App\Models\InventoryTransaction;
use App\Models\TreatmentStepExecution;
use App\Services\Hkd\HkdJournalService;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Nhập kho — thêm tồn và cập nhật giá bình quân gia quyền.
     */
    public function addStock(
        InventoryItem $item,
        float $qty,
        int $unitCost,
        string $date,
        ?int $branchId = null,
        string $sourceType = 'manual',
        ?int $sourceId = null,
        ?string $documentNo = null,
        ?string $notes = null
    ): InventoryTransaction {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Số lượng nhập phải > 0.');
        }

        return DB::transaction(function () use ($item, $qty, $unitCost, $date, $branchId, $sourceType, $sourceId, $documentNo, $notes) {
            $tx = InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'branch_id'         => $branchId ?? $item->branch_id,
                'transaction_type'  => InventoryTransactionType::Purchase->value,
                'qty'               => $qty,
                'unit_cost'         => $unitCost,
                'amount'            => (int) round($qty * $unitCost),
                'transaction_date'  => $date,
                'source_type'       => $sourceType,
                'source_id'         => $sourceId,
                'document_no'       => $documentNo,
                'notes'             => $notes,
                'created_by'        => auth()->id(),
            ]);

            // Weighted average cost
            $totalQty  = $item->current_stock_qty + $qty;
            $totalCost = ($item->current_stock_qty * $item->unit_cost) + ($qty * $unitCost);
            $newAvgCost = $totalQty > 0 ? (int) round($totalCost / $totalQty) : $unitCost;

            $item->update([
                'current_stock_qty' => $totalQty,
                'unit_cost'         => $newAvgCost,
            ]);

            return $tx;
        });
    }

    /**
     * Xuất kho cho một lần thực hiện công đoạn điều trị.
     * Idempotent: skip nếu đã có transaction cho execution này.
     */
    public function consumeForExecution(TreatmentStepExecution $execution): array
    {
        $item = $execution->planItem?->service ?? null;
        if (!$item) {
            return [];
        }

        $serviceId  = $execution->planItem->service_id;
        $stepId     = $execution->service_step_id;
        $branchId   = $execution->planItem->plan?->branch_id;

        // Check idempotent
        $alreadyConsumed = InventoryTransaction::where('source_type', 'treatment_execution')
            ->where('source_id', $execution->id)
            ->exists();

        if ($alreadyConsumed) {
            return [];
        }

        $templates = InventoryServiceTemplate::where('is_active', true)
            ->where('service_id', $serviceId)
            ->where(fn ($q) => $q->where('service_step_id', $stepId)->orWhereNull('service_step_id'))
            ->with('inventoryItem')
            ->get();

        $consumed = [];

        DB::transaction(function () use ($templates, $execution, $branchId, &$consumed) {
            foreach ($templates as $tpl) {
                $itm = $tpl->inventoryItem;
                if (!$itm || !$itm->is_active) {
                    continue;
                }

                $qty    = $tpl->qty_per_execution;
                $amount = (int) round($qty * $itm->unit_cost);

                InventoryTransaction::create([
                    'inventory_item_id' => $itm->id,
                    'branch_id'         => $branchId ?? $itm->branch_id,
                    'transaction_type'  => InventoryTransactionType::Usage->value,
                    'qty'               => -$qty,
                    'unit_cost'         => $itm->unit_cost,
                    'amount'            => $amount,
                    'transaction_date'  => today()->toDateString(),
                    'source_type'       => 'treatment_execution',
                    'source_id'         => $execution->id,
                    'notes'             => 'Xuất dùng công đoạn #' . $execution->id,
                    'created_by'        => auth()->id(),
                ]);

                $newQty = max(0, $itm->current_stock_qty - $qty);
                $itm->update(['current_stock_qty' => $newQty]);

                $consumed[] = ['item' => $itm->name, 'qty' => $qty, 'unit' => $itm->unit, 'amount' => $amount];

                // Ghi chi phí vật tư vào sổ TT152
                if ($amount > 0 && $branchId) {
                    app(HkdJournalService::class)->postMaterialUsage($execution, $itm, $amount);
                }
            }
        });

        return $consumed;
    }

    /**
     * Điều chỉnh tồn kho (có thể dương hoặc âm).
     */
    public function adjust(InventoryItem $item, float $qty, string $notes, ?int $branchId = null): InventoryTransaction
    {
        return DB::transaction(function () use ($item, $qty, $notes, $branchId) {
            $tx = InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'branch_id'         => $branchId ?? $item->branch_id,
                'transaction_type'  => InventoryTransactionType::Adjustment->value,
                'qty'               => $qty,
                'unit_cost'         => $item->unit_cost,
                'amount'            => (int) round(abs($qty) * $item->unit_cost),
                'transaction_date'  => today()->toDateString(),
                'source_type'       => 'manual',
                'notes'             => $notes,
                'created_by'        => auth()->id(),
            ]);

            $item->update([
                'current_stock_qty' => max(0, $item->current_stock_qty + $qty),
            ]);

            return $tx;
        });
    }
}
