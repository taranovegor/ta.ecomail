<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'ta.ecomail' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">

<main class="max-w-3xl mx-auto py-8">
    @if (session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @yield('content')
</main>
</body>
</html>
