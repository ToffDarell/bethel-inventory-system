<?php

namespace App\Http\Controllers;

use App\Exports\AssetsExport;
use App\Exports\AssetsTemplateExport;
use App\Models\Asset;
use App\Services\AssetImportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportExportController extends Controller
{
    public function __construct(
        protected AssetImportService $importService
    ) {}

    public function index()
    {
        $departments = Asset::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        return view('reports.index', [
            'totalAssets' => Asset::count(),
            'departments' => $departments,
            'statuses' => Asset::STATUSES,
        ]);
    }

    public function template(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'csv' || ! class_exists('XMLWriter')) {
            return Excel::download(
                new AssetsTemplateExport,
                'bethel-assets-import-template.csv',
                \Maatwebsite\Excel\Excel::CSV,
                ['Content-Type' => 'text/csv; charset=UTF-8']
            );
        }

        return Excel::download(
            new AssetsTemplateExport,
            'bethel-assets-import-template.xlsx'
        );
    }

    public function export(Request $request)
    {
        $department = $request->query('department');
        $status = $request->query('status');
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'csv' || ! class_exists('XMLWriter')) {
            $filename = 'bethel-assets-' . now()->format('Y-m-d') . '.csv';

            return Excel::download(
                new AssetsExport($department, $status),
                $filename,
                \Maatwebsite\Excel\Excel::CSV,
                ['Content-Type' => 'text/csv; charset=UTF-8']
            );
        }

        $filename = 'bethel-assets-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new AssetsExport($department, $status),
            $filename
        );
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [
            'file.required' => 'Please choose an Excel (.xlsx, .xls) or CSV (.csv) file to upload.',
            'file.mimes' => 'The file must be an Excel spreadsheet (.xlsx, .xls) or CSV (.csv).',
        ]);

        $preview = $this->importService->preview($request->file('file'));

        session(['asset_import_preview' => $preview['rows']]);

        return view('reports.import-preview', $preview);
    }

    public function commit(Request $request)
    {
        $rows = session('asset_import_preview', []);

        if (empty($rows)) {
            return redirect()
                ->route('reports.index')
                ->withErrors(['file' => 'No import preview found. Please upload your spreadsheet again.']);
        }

        $result = $this->importService->commit($rows, $request->user());
        session()->forget('asset_import_preview');

        return redirect()
            ->route('reports.index')
            ->with(
                'success',
                "Excel import complete: {$result['imported']} asset(s) added, {$result['skipped']} duplicate or incomplete row(s) skipped."
            );
    }
}
