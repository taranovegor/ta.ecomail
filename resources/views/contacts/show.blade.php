@extends('app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('contacts.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Back to contacts</a>
    </div>

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl text-gray-800">{{ $contact->first_name }} {{ $contact->last_name }}</h1>
        <div class="flex gap-3">
            <a href="{{ route('contacts.edit', $contact) }}"
               class="px-4 py-2 bg-gray-100 text-gray-700 text-sm
                      hover:bg-gray-200 border border-gray-300">Edit</a>
            <form action="{{ route('contacts.destroy', $contact) }}"
                  method="POST"
                  onsubmit="return confirm('Delete this contact?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 bg-red-50 text-red-600 text-sm
                               hover:bg-red-100 border border-red-200">Delete</button>
            </form>
        </div>
    </div>

    <div class="bg-white border border-gray-200 p-6">
        <dl class="space-y-4">
            <div>
                <dt class="text-sm text-gray-500">Email</dt>
                <dd class="mt-1 text-gray-900">{{ $contact->email }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">First Name</dt>
                <dd class="mt-1 text-gray-900">{{ $contact->first_name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Last Name</dt>
                <dd class="mt-1 text-gray-900">{{ $contact->last_name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Created</dt>
                <dd class="mt-1 text-gray-900">{{ $contact->created_at->format('c') }}</dd>
            </div>
        </dl>
    </div>
@endsection
