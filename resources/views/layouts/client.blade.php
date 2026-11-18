<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PawPaw</title>
    @php
        $isClinicPage = request()->routeIs('client.clinic', 'client.vendor.detail', 'client.clinic.booking', 'client.my-bookings', 'client.profile');
    @endphp
    @if ($isClinicPage)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @endif
    {{-- @fluxAppearance --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="{{ $isClinicPage ? 'clinic-shell' : 'bg-[#FFF]' }} {{ $isClinicPage ? 'clinic-browse-shell' : '' }} {{ request()->routeIs('client.clinic.booking') ? 'clinic-booking-shell' : '' }}">

    <div>
        @include('components.client.components.clinic-header')
        <flux:main container class="{{ $isClinicPage ? 'clinic-main' : '' }}">
            {{ $slot }}

        </flux:main>
    </div>


    @livewireScripts
    @fluxScripts
    <x-client.auth.toast />
</body>

</html>
