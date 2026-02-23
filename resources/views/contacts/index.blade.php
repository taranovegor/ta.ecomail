@extends('app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl text-gray-800">Contacts</h1>
        <div class="flex justify-between gap-3">
            <a href="{{ route('contacts.create') }}"
               class="px-4 py-2 bg-blue-600 text-white text-sm hover:bg-blue-700">Add Contact</a>
            <a href="{{ route('imports.create') }}"
               class="px-4 py-2 bg-blue-600 text-white text-sm hover:bg-blue-700">Import Contacts</a>
        </div>
    </div>

    <form action="{{ route('contacts.index') }}" method="GET" class="mb-6">
        <div class="flex gap-2">
            <input type="text"
                   name="search"
                   value="{{ $search ?? '' }}"
                   placeholder="Search..."
                   class="flex-1 px-4 py-2 border border-gray-300">
            <button type="submit"
                    class="px-4 py-2 bg-gray-100 text-gray-700
                           hover:bg-gray-200 border border-gray-300">Search</button>
            @if ($search)
                <a href="{{ route('contacts.index') }}"
                   class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 self-center">Clear</a>
            @endif
        </div>
    </form>

    @if ($contacts->isEmpty())
        <div class="text-center py-12 text-gray-500">
            @if ($search)
                No contacts found for "{{ $search }}".
            @else
                No contacts yet. <a href="{{ route('contacts.create') }}"
                                    class="text-blue-600">Create one</a>.
            @endif
        </div>
    @else
        <div class="bg-white border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">First Name</th>
                    <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Last Name</th>
                    <th class="px-4 py-3 text-right text-xs text-gray-500 uppercase">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @foreach ($contacts as $contact)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $contact->email() }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $contact->firstName() }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $contact->lastName() }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <a href="{{ route('contacts.show', $contact) }}"
                               class="text-blue-600">View</a>
                            <a href="{{ route('contacts.edit', $contact) }}"
                               class="text-blue-600 ml-3">Edit</a>
                            <form action="{{ route('contacts.destroy', $contact) }}"
                                  method="POST"
                                  class="inline ml-3"
                                  onsubmit="return confirm('Delete this contact?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $contacts->withQueryString()->links() }}
        </div>
    @endif
@endsection
