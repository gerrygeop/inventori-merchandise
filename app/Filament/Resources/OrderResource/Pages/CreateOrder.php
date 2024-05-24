<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function afterCreate(): void
    {
        $items = $this->record->items;

        foreach ($items as $item) {
            $product = Product::find($item->product_id);
            $product->qty = $product->qty - $item->qty;
            $product->save();

            // Simpan jumlah asli yang dipesan
            $item->original_qty = $item->qty;
            $item->save();
        }
    }
}
