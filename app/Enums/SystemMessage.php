<?php

namespace App\Enums;

enum SystemMessage: string
{
    case CLIENT_LOGIN_SUCCESS = 'Login akun pelanggan berhasil. Selamat datang kembali di PawPaw!';
    case CLIENT_REGISTER_SUCCESS = 'Pendaftaran akun pelanggan berhasil. Selamat datang di PawPaw!';
    case VENDOR_REGISTER_SUCCESS = 'Pendaftaran berhasil! Silakan tunggu verifikasi dari tim kami.';
    case ERROR_SYSTEM = 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.';
    case ERROR_EMAIL_TAKEN = 'Email sudah terdaftar, gunakan email lain.';
    case INVALID_CREDENTIALS = 'Email atau password salah, atau akun tidak terdaftar.';

    case DATA_CREATED_SUCCESS = 'Data berhasil ditambahkan.';
    case DATA_UPDATED_SUCCESS = 'Data berhasil diperbarui.';
    case DATA_DELETED_SUCCESS = 'Data berhasil dihapus.';
    case DATA_RESTORED_SUCCESS = 'Data berhasil dipulihkan.';
}
