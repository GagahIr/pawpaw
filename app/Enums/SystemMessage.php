<?php

namespace App\Enums;

enum SystemMessage: string
{
    case VENDOR_REGISTER_SUCCESS = 'Pendaftaran berhasil! Silakan tunggu verifikasi dari tim kami.';
    case ERROR_SYSTEM = 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.';
    case ERROR_EMAIL_TAKEN = 'Email sudah terdaftar, gunakan email lain.';
    case DATA_CREATED_SUCCESS = 'Data berhasil ditambahkan.';
    case DATA_UPDATED_SUCCESS = 'Data berhasil diperbarui.';
    case DATA_DELETED_SUCCESS = 'Data berhasil dihapus.';
    case DATA_RESTORED_SUCCESS = 'Data berhasil dipulihkan.';
}
