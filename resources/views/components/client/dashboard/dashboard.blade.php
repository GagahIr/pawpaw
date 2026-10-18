@extends('landing_page.layouts.app-landing')

@section('content')
    <section class="mx-auto flex min-h-[60vh] max-w-3xl items-center px-4 py-16 sm:px-6">
        <div class="w-full rounded-2xl border border-zinc-200 bg-white p-6 sm:p-10">
            <p class="text-sm font-semibold text-[#1a5c40]">Dashboard Pelanggan</p>
            <h1 class="mt-3 break-words text-3xl font-bold text-zinc-900">Halo, {{ auth()->user()->name }}!</h1>
            <p class="mt-4 text-zinc-600">Selamat datang di PawPaw. Akun Anda sudah berhasil masuk.</p>
            <p class="mt-2 text-sm text-zinc-500">Halaman dashboard sementara ini akan dilengkapi dengan fitur pelanggan.</p>

            <div class="mt-8 flex flex-wrap items-center gap-4">
                <flux:button href="{{ route('landing_page') }}" variant="ghost">Kembali ke Beranda</flux:button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:button type="submit" variant="primary" class="!bg-[#1a5c40]">Logout</flux:button>
                </form>
            </div>
        </div>
    </section>
@endsection
