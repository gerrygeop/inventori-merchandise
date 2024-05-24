<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $items = $this->record->items;

        foreach ($items as $item) {
            $product = Product::find($item->product_id);
            // Kembalikan stok produk ke nilai sebelum perubahan
            if ($item->original_qty !== null) {
                $product->qty = $product->qty + $item->original_qty;
            }

            // Kurangi stok produk dengan jumlah baru yang dipesan
            $product->qty = $product->qty - $item->qty;
            $product->save();

            // Update jumlah asli yang dipesan
            $item->original_qty = $item->qty;
            $item->save();
        }
    }
}
