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
            $product->qty = $product->security_stock - $item->qty;
            $product->save();
        }
    }
}
