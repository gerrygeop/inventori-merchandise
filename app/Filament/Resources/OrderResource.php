<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\repeater;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\DateTimePicker::make('order_date')
                            ->default(now())
                            ->hidden()
                            ->disabled()
                            ->dehydrated(false)
                            ->timezone('Asia/Singapore')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('contact')
                                    ->label('Phone')
                                    ->maxLength(255),

                                Forms\Components\Textarea::make('address')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\RichEditor::make('description')
                                    ->fileAttachmentsDirectory('suppliers-attachments'),
                            ])
                            ->createOptionAction(function (Action $action) {
                                return $action
                                    ->modalHeading('Create supplier')
                                    ->modalWidth(\Filament\Support\Enums\MaxWidth::ThreeExtraLarge);
                            }),

                        Forms\Components\ToggleButtons::make('status')
                            ->inline()
                            ->options(OrderStatus::class)
                            ->required(),

                        Forms\Components\RichEditor::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Order item')
                    ->headerActions([
                        Action::make('reset')
                            ->modalHeading('Are you sure?')
                            ->modalDescription('All existing items will be removed from the order.')
                            ->requiresConfirmation()
                            ->color('danger')
                            ->action(fn (Set $set) => $set('items', [])),
                    ])
                    ->schema([
                        static::getRepeater(),

                        Forms\Components\Fieldset::make('Total price')
                            ->schema([
                                Forms\Components\TextInput::make('total_price')
                                    ->prefix('Rp')
                                    ->hiddenLabel()
                                    ->disabled()
                                    ->dehydrated()
                                    ->afterStateHydrated(function (Get $get, Set $set) {
                                        self::updateTotal($get, $set);
                                    }),
                            ]),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getRepeater(): Repeater
    {
        return Repeater::make('items')
            ->relationship()
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Product')
                    ->options(Product::query()->pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (Set $set) {
                        $set('qty', 0);
                        $set('unit_price', 0);
                    })
                    ->distinct()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->columnSpan([
                        'md' => 5
                    ])
                    ->searchable(),

                Forms\Components\TextInput::make('qty')
                    ->label('Quantity')
                    ->numeric()
                    ->default(0)
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        $unit_price = Product::find($get('product_id'))?->price ?? 0;
                        $price = $unit_price * $state;
                        $set('unit_price', $price);
                    })
                    ->minValue(0)
                    ->required()
                    ->columnSpan([
                        'md' => 2,
                    ]),

                Forms\Components\TextInput::make('unit_price')
                    ->label('Unit Price')
                    ->default(0)
                    ->disabled()
                    ->dehydrated()
                    ->prefix('Rp')
                    ->required()
                    ->columnSpan([
                        'md' => 3,
                    ]),
            ])
            ->extraItemActions([
                Action::make('openProduct')
                    ->tooltip('Open product')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(function (array $arguments, Repeater $component): ?string {
                        $itemData = $component->getRawItemState($arguments['item']);

                        $product = Product::find($itemData['product_id']);

                        if (!$product) {
                            return null;
                        }

                        return ProductResource::getUrl('edit', ['record' => $product]);
                    }, shouldOpenInNewTab: true)
                    ->hidden(fn (array $arguments, Repeater $component): bool => blank($component->getRawItemState($arguments['item'])['product_id'])),
            ])
            ->defaultItems(1)
            ->hiddenLabel()
            ->columns([
                'md' => 10,
            ])
            ->required()
            ->live()
            ->afterStateUpdated(function (Get $get, Set $set) {
                self::updateTotal($get, $set);
            });
    }

    public static function updateTotal(Get $get, Set $set)
    {
        $selectedProducts = collect($get('items'))->filter(fn ($item) => !empty($item['product_id']) && !empty($item['qty']));

        $prices = Product::find($selectedProducts->pluck('product_id'))->pluck('price', 'id');

        $totalPrice = $selectedProducts->reduce(function ($totalPrice, $product) use ($prices) {
            return $totalPrice + ($prices[$product['product_id']] * $product['qty']);
        }, 0);

        $set('total_price', number_format($totalPrice, 2, ',', '.'));
    }
}
