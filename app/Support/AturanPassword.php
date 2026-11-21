<?php

namespace App\Support;

/**
 * Aturan password login: minimal 8 karakter DAN mengandung angka atau simbol
 * (cukup salah satu, tidak harus keduanya). Hanya berlaku saat membuat / mengganti password;
 * password lama yang sudah 8 karakter tetap bisa dipakai login.
 */
class AturanPassword
{
    public const MINIMAL = 8;

    /** Tambahan rule untuk field "password" (dipakai bersama 'required' dan 'confirmed') */
    public static function rules(): array
    {
        return ['string', 'min:' . self::MINIMAL, 'regex:/[0-9]|[^A-Za-z0-9\s]/'];
    }

    public static function pesan(): array
    {
        return [
            'password.min' => 'Password minimal ' . self::MINIMAL . ' karakter.',
            'password.regex' => 'Password harus mengandung angka atau simbol (salah satu saja sudah cukup), misalnya "kasir1234" atau "kasir@potato".',
        ];
    }

    public const PETUNJUK = 'Minimal 8 karakter, wajib ada angka atau simbol (salah satu cukup).';
}
