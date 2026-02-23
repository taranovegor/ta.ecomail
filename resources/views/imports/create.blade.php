@extends('app')

@section('content')
    <div class="mb-6">
        <a href="{{ route('contacts.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Back to contacts</a>
    </div>

    <h1 class="text-2xl text-gray-800 mb-6">Import Contacts</h1>

    <div class="bg-white border border-gray-200 p-6">
        <form action="{{ route('imports.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-6">
                <label for="file" class="block text-sm text-gray-700 mb-2">
                    XML File
                </label>
                <input type="file"
                       id="file"
                       name="file"
                       accept=".xml"
                       class="w-full text-sm text-gray-700 border border-gray-300
                              file:mr-4 file:py-2 file:px-4 file:border-0
                              file:text-sm file:font-medium
                              file:bg-blue-50 file:text-blue-700
                              hover:bg-blue-50
                              @error('file') border-red-500 @enderror"
                       required>
                @error('file')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-gray-500">
                    Maximum file size: {{ config('import.max_file_size_kb') / 1024 }}MB. Accepted format: XML.
                </p>
            </div>

            <button type="submit" class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700">Upload & Import</button>
        </form>
    </div>
@endsection
