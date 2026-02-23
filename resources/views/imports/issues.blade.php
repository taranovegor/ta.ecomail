@extends('app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('imports.show', $import) }}" class="text-sm text-gray-500 hover:text-gray-700">
            Back to import #{{ $import->id() }}
        </a>
    </div>

    <h1 class="text-2xl text-gray-800 mb-6">
        Import #{{ $import->id() }} - Issues
    </h1>

    <div class="flex gap-2 mb-6">
        <a href="{{ route('imports.issues', $import) }}"
           class="px-3 py-1.5 text-sm
                  {{ $currentType === null ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            All ({{ $import->duplicatesCount() + $import->invalidCount() }})
        </a>
        <a href="{{ route('imports.issues', [$import, 'type' => 'duplicate']) }}"
           class="px-3 py-1.5 text-sm
                  {{ $currentType === 'duplicate' ? 'bg-yellow-600 text-white' : 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100' }}">
            Duplicates ({{ $import->duplicatesCount() }})
        </a>
        <a href="{{ route('imports.issues', [$import, 'type' => 'invalid']) }}"
           class="px-3 py-1.5 text-sm
                  {{ $currentType === 'invalid' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100' }}">
            Invalid ({{ $import->invalidCount() }})
        </a>
    </div>

    @if ($issues->isEmpty())
        <div class="text-center py-12 text-gray-500">No issues found</div>
    @else
        <div class="bg-white border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">First Name</th>
                    <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Last Name</th>
                    <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Reason</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @foreach ($issues as $issue)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">
                            @if ($issue->type() === 'duplicate')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-yellow-100 text-yellow-800">duplicate</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-800">invalid</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ Str::limit($issue->email(), 40) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $issue->firstName() ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $issue->lastName() ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate" title="{{ $issue->reason() }}">
                            {{ $issue->reason() }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $issues->withQueryString()->links() }}
        </div>
    @endif
@endsection
