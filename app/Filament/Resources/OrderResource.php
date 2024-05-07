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
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Str;

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
                                    ->dehydrated(),
                                // ->afterStateHydrated(function (Get $get, Set $set) {
                                //     self::updateTotal($get, $set);
                                // }),
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
                    ->badge()
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
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state) {
                        $price = Product::find($state)?->price ?? 0;
                        $set('unit_price', $price);
                        // $set('qty', 1);
                    })
                    ->distinct()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->columnSpan([
                        'md' => 5
                    ])
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                                if (($get('slug') ?? '') !== Str::slug($old)) {
                                    return;
                                }
                                $set('slug', Str::slug($state));
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->maxLength(255)
                            ->unique(Product::class, 'slug', ignoreRecord: true),

                        Forms\Components\RichEditor::make('description')
                            ->fileAttachmentsDirectory('products-attachments')
                            ->columnSpanFull(),

                        Forms\Components\Section::make('Images')
                            ->schema([
                                Forms\Components\FileUpload::make('photo_url')
                                    ->hiddenLabel()
                                    ->image()
                                    ->directory('products-images')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('Inventory')
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU (Stock Keeping Unit)')
                                    ->unique(Product::class, 'sku', ignoreRecord: true)
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->inputMode('decimal')
                                    ->minValue(0)
                                    ->prefix('Rp'),

                                Forms\Components\TextInput::make('qty')
                                    ->required()
                                    ->label('Quantity')
                                    ->numeric()
                                    ->rules(['integer', 'min:0'])
                                    ->minValue(0)
                                    ->default(0),

                                Forms\Components\TextInput::make('security_stock')
                                    ->required()
                                    ->helperText('The safety stock is the limit stock for your products which alerts you if the product stock will soon be out of stock.')
                                    ->numeric()
                                    ->rules(['integer', 'min:0'])
                                    ->minValue(0)
                                    ->default(0),
                            ])
                            ->columns(2),
                    ])
                    ->createOptionAction(function (Action $action) {
                        return $action
                            ->modalHeading('Create product')
                            ->modalWidth(\Filament\Support\Enums\MaxWidth::ThreeExtraLarge);
                    })
                    ->createOptionUsing(function (array $data): int {
                        return Product::create($data)->getKey();
                    }),

                Forms\Components\TextInput::make('qty')
                    ->label('Quantity')
                    ->numeric()
                    ->default(0)
                    ->dehydrated()
                    ->minValue(0)
                    ->required()
                    ->columnSpan([
                        'md' => 2,
                    ]),

                Forms\Components\TextInput::make('unit_price')
                    ->label('Unit Price')
                    ->dehydrated()
                    ->live(onBlur: true)
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
        $selectedProducts = collect($get('items'))->filter(fn ($item) => !empty($item['product_id']) && !empty($item['unit_price']));

        // $prices = Product::find($selectedProducts->pluck('product_id'))->pluck('price', 'id');
        $prices = $selectedProducts->pluck('unit_price', 'product_id');

        $totalPrice = $selectedProducts->reduce(function ($totalPrice, $product) use ($prices) {
            return $totalPrice + ($prices[$product['product_id']] * $product['qty']);
        }, 0);

        $set('total_price', $totalPrice);
    }
}
