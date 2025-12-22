<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\WalletResource\Pages;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\PaystackService;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\Action as ActionsAction;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('balance')
                    ->label('Balance (NGN)')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount (NGN)')
                    ->money('NGN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'credit' => 'success',
                        'debit' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Date'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'credit' => 'Credit',
                        'debit' => 'Debit',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                ActionsAction::make('fund')
                    ->label('Fund Wallet')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (NGN)')
                            ->numeric()
                            ->minValue(100) // Minimum ₦100
                            ->required()
                            ->helperText('Minimum amount is ₦100'),
                    ])
                    ->action(function (array $data) {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();

                        if (! $user) {
                            Notification::make()
                                ->title('Not authenticated')
                                ->danger()
                                ->send();
                            return;
                        }

                        $wallet = $user->wallet()->firstOrCreate([], [
                            'balance' => 0,
                        ]);

                        $amount = (float) $data['amount'];
                        $reference = 'WLT-' . Str::upper(Str::random(10)) . '-' . time();

                        try {
                            $paystackService = app(PaystackService::class);
                            
                            // Create pending transaction
                            $transaction = $wallet->transactions()->create([
                                'amount' => $amount,
                                'type' => 'credit',
                                'status' => 'pending',
                                'description' => 'Wallet funding via Paystack',
                                'reference' => $reference,
                                'meta' => [
                                    'user_id' => $user->id,
                                    'user_email' => $user->email,
                                ],
                            ]);

                            // Initialize Paystack transaction
                            $paystackResponse = $paystackService->initializeTransaction(
                                $amount,
                                $user->email,
                                $reference,
                                [
                                    'wallet_transaction_id' => $transaction->id,
                                    'user_id' => $user->id,
                                ]
                            );

                            if (isset($paystackResponse['data']['authorization_url'])) {
                                // Redirect to Paystack payment page
                                return redirect()->away($paystackResponse['data']['authorization_url']);
                            }

                            throw new \Exception('Failed to initialize payment');
                        } catch (\Exception $e) {
                            // Update transaction status to failed
                            if (isset($transaction)) {
                                $transaction->update([
                                    'status' => 'failed',
                                    'meta' => array_merge($transaction->meta ?? [], [
                                        'error' => $e->getMessage(),
                                    ]),
                                ]);
                            }

                            Notification::make()
                                ->title('Payment initialization failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([]); // No actions, read-only
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWallet::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Wallet';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Billing';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-wallet';
    }
}


