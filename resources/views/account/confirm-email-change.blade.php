<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Konfirmasi Email — PawPaw</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FFF8F5] text-zinc-900">
    <main class="mx-auto flex min-h-screen max-w-lg items-center px-5 py-12">
        <section class="w-full rounded-2xl border border-[#EAE4DC] bg-white p-6 sm:p-10" aria-labelledby="email-change-title">
            <p class="mb-3 text-sm font-semibold text-[#2D5945]">PawPaw</p>
            <h1 id="email-change-title" class="text-2xl font-bold">Konfirmasi Perubahan Email</h1>
            @if ($error)
                <p role="alert" class="mt-5 text-sm text-red-700">{{ $error }}</p>
                <a href="{{ route('landing_page') }}" class="mt-6 inline-block underline">Kembali ke beranda</a>
            @elseif ($success)
                <p role="status" class="mt-5 text-sm">Email akun berhasil diperbarui. Gunakan email baru untuk masuk dengan password. Login Google yang sudah terhubung tetap dapat digunakan.</p>
                <a href="{{ $profileUrl }}" class="mt-6 inline-block rounded-xl bg-[#2D5945] px-5 py-3 text-sm font-semibold text-white">Kembali ke profil</a>
            @else
                <p class="mt-5 text-sm">Konfirmasikan perubahan email akun Anda ke <strong class="break-all">{{ $email }}</strong>.</p>
                <p class="mt-3 text-sm text-zinc-600">Email lama tetap aktif sampai Anda menekan tombol di bawah.</p>
                <form method="POST" action="{{ $actionUrl }}" class="mt-6">
                    @csrf
                    <button type="submit" class="w-full rounded-xl bg-[#2D5945] px-5 py-3 text-sm font-semibold text-white focus:outline-2 focus:outline-offset-2 focus:outline-[#2D5945]">Konfirmasi perubahan email</button>
                </form>
            @endif
        </section>
    </main>
</body>
</html>
