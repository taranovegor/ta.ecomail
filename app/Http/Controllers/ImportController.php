<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexImportIssueRequest;
use App\Http\Requests\StoreImportRequest;
use App\Http\ViewModels\ImportIssueViewModel;
use App\Http\ViewModels\ImportViewModel;
use App\Models\Import;
use App\Models\ImportIssue;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class ImportController extends Controller
{
    public function __construct(
        private readonly ImportService $importService,
    ) {}

    public function create(): View
    {
        return view('imports.create');
    }

    public function store(StoreImportRequest $request): RedirectResponse
    {
        try {
            $import = $this->importService->initiate(
                $request->file('file'),
            );
        } catch (Throwable $e) {
            return redirect()
                ->route('imports.create')
                ->with('warning', $e->getMessage());
        }

        return redirect()
            ->route('imports.show', $import)
            ->with('success', 'File uploaded. Import is being processed.');
    }

    public function show(Import $import): View
    {
        return view('imports.show', [
            'import' => new ImportViewModel($import),
        ]);
    }

    public function issues(Import $import, IndexImportIssueRequest $request): View
    {
        $query = $import->issues();

        $type = $request->validated('type');
        if ($type !== null) {
            $query->where('type', $type);
        }

        $issues = $query
            ->latest('id')
            ->paginate();

        return view('imports.issues', [
            'import' => new ImportViewModel($import),
            'issues' => $issues->through(fn (ImportIssue $issue) => new ImportIssueViewModel($issue)),
            'currentType' => $type,
        ]);
    }
}
