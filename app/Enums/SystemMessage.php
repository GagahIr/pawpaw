<?php

namespace App\Enums;

enum SystemMessage: string
{
    case VENDOR_REGISTER_SUCCESS = 'Pendaftaran berhasil! Silakan tunggu verifikasi dari tim kami.';
    case ERROR_SYSTEM = 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.';
    case ERROR_EMAIL_TAKEN = 'Email sudah terdaftar, gunakan email lain.';
}
