@extends('app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('contacts.show', $contact) }}"
           class="text-sm text-gray-500 hover:text-gray-700">Back to contact</a>
    </div>

    <h1 class="text-2xl text-gray-800 mb-6">Edit Contact</h1>

    <div class="bg-white border border-gray-200 p-6">
        <form action="{{ route('contacts.update', $contact) }}" method="POST">
            @csrf
            @method('PUT')

            @include('contacts._form', ['contact' => $contact])

            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700">Update Contact</button>
        </form>
    </div>
@endsection
