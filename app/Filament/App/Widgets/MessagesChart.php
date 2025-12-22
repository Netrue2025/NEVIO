<?php

namespace App\Filament\App\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\EmailMessage;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class MessagesChart extends ApexChartWidget
{
    protected static ?int $sort = 2;

    protected static ?string $chartId = 'messagesChart';

    protected static ?string $heading = 'Messages Chart';

    protected function getOptions(): array
    {
        $userId = Auth::id();

        // Initialize months
        $months = collect(range(1, 12))->map(fn ($m) =>
            Carbon::create()->month($m)->format('M')
        );

        // Email messages per month
        $emailData = EmailMessage::where('user_id', $userId)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // SMS messages per month
        $smsData = SmsMessage::where('user_id', $userId)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
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
                    'name' => 'Email Messages',
                    'data' => array_map(
                        fn ($m) => $emailData[$m] ?? 0,
                        range(1, 12)
                    ),
                ],
                [
                    'name' => 'SMS Messages',
                    'data' => array_map(
                        fn ($m) => $smsData[$m] ?? 0,
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

            'colors' => ['#3b82f6', '#f59e0b'], // Email | SMS

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
