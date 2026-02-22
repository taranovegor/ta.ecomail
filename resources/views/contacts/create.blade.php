@extends('app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('contacts.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Back to contacts</a>
    </div>

    <h1 class="text-2xl text-gray-800 mb-6">Create Contact</h1>

    <div class="bg-white border border-gray-200 p-6">
        <form action="{{ route('contacts.store') }}" method="POST">
            @csrf

            @include('contacts._form')

            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700">Create Contact</button>
        </form>
    </div>
@endsection
