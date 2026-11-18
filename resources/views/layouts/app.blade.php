<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PawPaw</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    {{-- @fluxAppearance --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="clinic-shell clinic-browse-shell clinic-auth-shell">
    @include('components.client.components.clinic-header', [
        'isClinicPage' => true,
        'isLandingPage' => !auth('client')->check(),
    ])

    <main class="clinic-auth-main">
        {{ $slot }}
    </main>

    @include('components.client.components.clinic-footer')

    @livewireScripts
    @fluxScripts
    <x-client.auth.toast />
</body>

</html>
