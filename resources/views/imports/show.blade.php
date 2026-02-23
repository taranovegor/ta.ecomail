@extends('app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('contacts.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Back to contacts</a>
    </div>

    <h1 class="text-2xl text-gray-800 mb-6">Import #{{ $import->id() }}</h1>

    <div class="bg-white border border-gray-200 p-6 max-w-2xl">
        <div class="mb-6">
            <span class="text-sm text-gray-500">Status:</span>
            @switch($import->status())
                @case('pending')
                    <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs bg-yellow-100 text-yellow-800">
                        Pending
                    </span>
                    @break
                @case('processing')
                    <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800">
                        Processing...
                    </span>
                    @break
                @case('completed')
                    <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs bg-green-100 text-green-800">
                        Completed
                    </span>
                    @break
                @case('completed_with_errors')
                    <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs bg-orange-100 text-orange-800">
                        Completed with errors
                    </span>
                    @break
                @case('failed')
                    <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs bg-red-100 text-red-800">
                        Failed
                    </span>
                    @break
            @endswitch
        </div>

        <dl class="space-y-3 mb-6">
            <div>
                <dt class="text-sm text-gray-500">File</dt>
                <dd class="mt-1 text-gray-900">{{ $import->originalFilename() }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Uploaded</dt>
                <dd class="mt-1 text-gray-900">{{ $import->createdAt()->format('d M Y, H:i:s') }}</dd>
            </div>
        </dl>

        @if ($import->isFinished())
            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-lg text-gray-800 mb-4">Report</h2>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="bg-gray-50 p-3 text-center">
                        <div class="text-2xl text-gray-900">{{ number_format($import->totalRecords() ?? 0) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Total records</div>
                    </div>
                    <div class="bg-green-50 p-3 text-center">
                        <div class="text-2xl text-green-700">{{ number_format($import->importedCount()) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Imported</div>
                    </div>
                    <div class="bg-yellow-50 p-3 text-center">
                        <div class="text-2xl text-yellow-700">{{ number_format($import->duplicatesCount()) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Duplicates</div>
                    </div>
                    <div class="bg-red-50 p-3 text-center">
                        <div class="text-2xl text-red-700">{{ number_format($import->invalidCount()) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Invalid</div>
                    </div>
                </div>

                @if ($import->processingTimeSeconds())
                    <p class="mt-4 text-sm text-gray-500">
                        Processed in {{ number_format($import->processingTimeSeconds(), 2) }} seconds.
                    </p>
                @endif

                @if ($import->duplicatesCount() > 0 || $import->invalidCount() > 0)
                    <div class="mt-4 flex gap-3">
                        @if ($import->duplicatesCount() > 0)
                            <a href="{{ route('imports.issues', [$import, 'type' => 'duplicate']) }}"
                               class="text-sm text-blue-600 hover:underline">
                                View {{ number_format($import->duplicatesCount()) }} duplicate(s)
                            </a>
                        @endif
                        @if ($import->invalidCount() > 0)
                            <a href="{{ route('imports.issues', [$import, 'type' => 'invalid']) }}"
                               class="text-sm text-blue-600 hover:underline">
                                View {{ number_format($import->invalidCount()) }} invalid record(s)
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            @if ($import->errorMessage())
                <div class="mt-4 p-3 bg-red-50 border border-red-200">
                    <p class="text-sm text-red-700">{{ $import->errorMessage() }}</p>
                </div>
            @endif
        @endif
    </div>

    @unless ($import->isFinished())
        <script>
            setTimeout(() => window.location.reload(), 3000);
        </script>
    @endunless
@endsection
