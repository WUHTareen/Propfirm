<?php

namespace App\Filament\Pages;

use App\Services\Reports\ReportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Revenue, order mix, pass/fail rates and payout obligations for a date range,
 * with a CSV export for anyone who wants it in a spreadsheet.
 */
class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.reports';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('from')->label('From')->required()->live(),
                DatePicker::make('to')->label('To')->required()->live()->afterOrEqual('from'),
            ])
            ->columns(2)
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportCsv'),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        [$from, $to] = $this->range();

        $rows = app(ReportService::class)->exportRows($from, $to);
        $filename = 'propfirm-report-'.$from->toDateString().'-to-'.$to->toDateString().'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * The selected range, widened to whole days so "today" includes today.
     */
    protected function range(): array
    {
        $from = Carbon::parse($this->data['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($this->data['to'] ?? now())->endOfDay();

        return [$from, $to];
    }

    /**
     * Everything the view renders, recomputed whenever the dates change.
     */
    public function getReportProperty(): array
    {
        [$from, $to] = $this->range();
        $service = app(ReportService::class);

        return [
            'summary' => $service->summary($from, $to),
            'byType' => $service->byChallengeType($from, $to),
            'bySize' => $service->byAccountSize($from, $to),
            'outcomes' => $service->outcomes(),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view reports') ?? false;
    }
}
