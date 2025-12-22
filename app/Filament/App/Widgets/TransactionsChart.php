<?php

namespace App\Filament\App\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\WalletTransaction;
use Illuminate\Support\Carbon;

class TransactionsChart extends ApexChartWidget
{
    protected static ?int $sort = 3;

    protected static ?string $chartId = 'transactionsChart';

    protected static ?string $heading = 'Transactions Chart';

    protected function getOptions(): array
    {
        // Month labels
        $months = collect(range(1, 12))->map(
            fn($m) =>
            Carbon::create()->month($m)->format('M')
        );

        // CREDIT per month
        $creditData = WalletTransaction::where('type', 'credit')
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // DEBIT per month
        $debitData = WalletTransaction::where('type', 'debit')
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        return [
            'chart' => [
                'type' => 'area',
                'height' => 300,
                'toolbar' => ['show' => false],
            ],

            'series' => [
                [
                    'name' => 'Credit',
                    'data' => array_map(
                        fn($m) => (float) ($creditData[$m] ?? 0),
                        range(1, 12)
                    ),
                ],
                [
                    'name' => 'Debit',
                    'data' => array_map(
                        fn($m) => (float) ($debitData[$m] ?? 0),
                        range(1, 12)
                    ),
                ],
            ],

            'xaxis' => [
                'categories' => $months->toArray(),
                'labels' => [
                    'style' => ['fontFamily' => 'inherit'],
                ],
            ],

            'yaxis' => [
                'labels' => [
                    'style' => ['fontFamily' => 'inherit'],
                ],
            ],

            'colors' => ['#22c55e', '#ef4444'], // Credit | Debit

            'stroke' => [
                'curve' => 'smooth',
                'width' => 2,
            ],

            'dataLabels' => [
                'enabled' => false,
            ],

            'legend' => [
                'position' => 'top',
            ],
        ];
    }
}
