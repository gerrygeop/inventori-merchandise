<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AccountWidget extends Widget
{
    protected static ?int $sort = -3;

    protected static bool $isLazy = false;
    protected int | string | array $columnSpan = [
        'sm' => 2,
    ];

    /**
     * @var view-string
     */
    protected static string $view = 'filament-panels::widgets.account-widget';
}
