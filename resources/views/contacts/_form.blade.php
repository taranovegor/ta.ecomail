<div class="mb-4">
    <label for="email" class="block text-sm text-gray-700 mb-1">Email</label>
    <input type="text"
           id="email"
           name="email"
           value="{{ old('email', $contact->email ?? '') }}"
           class="w-full px-3 py-2 border
                  @error('email') border-red-500 @else border-gray-300 @enderror
                  focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
    @error('email')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label for="first_name" class="block text-sm text-gray-700 mb-1">
        First Name
    </label>
    <input type="text"
           id="first_name"
           name="first_name"
           value="{{ old('first_name', $contact->first_name ?? '') }}"
           class="w-full px-3 py-2 border
                  @error('first_name') border-red-500 @else border-gray-300 @enderror
                  focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
    @error('first_name')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="mb-6">
    <label for="last_name" class="block text-sm text-gray-700 mb-1">
        Last Name
    </label>
    <input type="text"
           id="last_name"
           name="last_name"
           value="{{ old('last_name', $contact->last_name ?? '') }}"
           class="w-full px-3 py-2 border
                  @error('last_name') border-red-500 @else border-gray-300 @enderror
                  focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
    @error('last_name')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
