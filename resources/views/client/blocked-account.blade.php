<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Akun Diblokir - PawPaw</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="clinic-shell clinic-browse-shell">
    <div>
        @include('components.client.components.clinic-header')
        
        <flux:main container class="clinic-main">
            <div class="clinic-page">
                @include('components.client.components.clinic-atmosphere')
                <div class="clinic-wrap">
                    <div class="mx-auto max-w-2xl px-4 py-6 sm:py-10">
                        <div class="flex flex-col items-center justify-center min-h-[calc(100vh-14rem)]">
                            <div class="text-center space-y-6">
                                <div class="flex justify-center">
                                    <div class="rounded-full bg-red-100 dark:bg-red-900/30 p-4">
                                        <flux:icon.x-circle class="w-16 h-16 text-red-600 dark:text-red-400" />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <flux:heading size="xl" class="text-red-600 dark:text-red-400">
                                        Akun Anda Telah Diblokir
                                    </flux:heading>
                                    <flux:subheading class="text-base">
                                        Kami telah mendeteksi aktivitas pemesanan fiktif pada akun Anda
                                    </flux:subheading>
                                </div>

                                <flux:card class="bg-red-50 dark:bg-red-950/20 border-red-200 dark:border-red-900/50 max-w-md mx-auto">
                                    <div class="space-y-4">
                                        <div>
                                            <flux:text class="font-medium text-red-900 dark:text-red-100">
                                                Alasan Pemblokiran:
                                            </flux:text>
                                            <flux:text class="text-sm text-red-800 dark:text-red-200 mt-1">
                                                Berdasarkan Syarat & Ketentuan PawPaw, akun yang melakukan pemesanan fiktif (<em>fake booking</em>) atau aktivitas mencurigakan lainnya dapat diblokir untuk menjaga kualitas layanan.
                                            </flux:text>
                                        </div>

                                        <flux:separator variant="subtle" class="!my-4" />

                                        <div class="space-y-3">
                                            <flux:text class="font-medium text-red-900 dark:text-red-100">
                                                Apa yang bisa Anda lakukan:
                                            </flux:text>
                                            <ul class="space-y-2 text-left">
                                                <li class="flex items-start gap-2">
                                                    <flux:icon.check class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" />
                                                    <span class="text-sm text-red-800 dark:text-red-200">
                                                        Hubungi tim support kami untuk mengajukan banding
                                                    </span>
                                                </li>
                                                <li class="flex items-start gap-2">
                                                    <flux:icon.check class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" />
                                                    <span class="text-sm text-red-800 dark:text-red-200">
                                                        Jelaskan situasi Anda dan kemungkinan kesalahpahaman
                                                    </span>
                                                </li>
                                                <li class="flex items-start gap-2">
                                                    <flux:icon.check class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" />
                                                    <span class="text-sm text-red-800 dark:text-red-200">
                                                        Tim kami akan meninjau kasus Anda dalam 24-48 jam
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </flux:card>

                                <flux:card class="bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-900/50 max-w-md mx-auto">
                                    <div class="space-y-2 text-left">
                                        <flux:text class="font-medium text-blue-900 dark:text-blue-100 flex items-center gap-2">
                                            <flux:icon.envelope class="w-5 h-5" />
                                            Hubungi Support
                                        </flux:text>
                                        <flux:text class="text-sm text-blue-800 dark:text-blue-200">
                                            Email: <span class="font-semibold">{{ config('mail.from.address') }}</span>
                                        </flux:text>
                                        <flux:text class="text-xs text-blue-700 dark:text-blue-300 mt-2">
                                            Cantumkan email akun Anda saat menghubungi support agar kami dapat memverifikasi dengan lebih cepat.
                                        </flux:text>
                                    </div>
                                </flux:card>

                                <div class="pt-4">
                                    <flux:link href="{{ route('landing_page') }}" variant="subtle" class="inline-flex items-center gap-1">
                                        <flux:icon.arrow-left variant="micro" />
                                        Kembali ke Beranda
                                    </flux:link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </flux:main>
    </div>

    @fluxScripts
</body>

</html>
