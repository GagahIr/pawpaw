<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PawPaw</title>
    {{-- @fluxAppearance --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:ital,wght@0,400;0,600;0,700;0,800;0,900;1,700;1,800;1,900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="{{ request()->routeIs('landing_page') ? 'clinic-shell clinic-browse-shell clinic-landing-shell' : 'bg-[#FFF8F5]' }}">
    @if (request()->routeIs('landing_page'))
        @include('components.client.components.clinic-header', ['isClinicPage' => true, 'isLandingPage' => true])
    @else
        <x-client.navbar />
    @endif

    <main class="{{ request()->routeIs('landing_page') ? 'clinic-landing-main' : '' }}">
        @yield('content')
    </main>

    @if (request()->routeIs('landing_page'))
        @include('components.client.components.clinic-footer')
        @include('landing_page.components.idle-cat')
    @else
        <x-client.footer />
    @endif

    @livewireScripts
    @fluxScripts
    <x-client.auth.toast />
</body>

</html>
