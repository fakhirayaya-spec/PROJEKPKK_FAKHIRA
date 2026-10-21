<?php

namespace Database\Seeders;

/**
 * Akun admin & kasir (password sudah di-hash, sama persis dengan database).
 * Disalin dari db_potato.sql. Aman dijalankan berulang (upsert berdasarkan id).
 */
class UserSeeder extends DumpSeeder
{
    public function run(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'Admin potato curly', 'email' => 'admin@gmail.com', 'email_verified_at' => null, 'password' => '$2y$12$znhmUIWgPiI5geivK4JR0eVkWRg11bYF5YWCmgNuZy0r4FpAo7XZq', 'role' => 'admin', 'status' => 'aktif', 'avatar' => 'admin/Bu2a4Cr1Nql1nvd7YzMWlqybe5mQe3h0ko9GULUU.jpg', 'alamat' => null, 'jenis_kelamin' => null, 'tanggal_lahir' => null, 'remember_token' => null, 'created_at' => '2026-09-14 23:18:40', 'updated_at' => '2026-09-29 02:14:40'],
            ['id' => 2, 'name' => 'Kasir potato', 'email' => 'kasir@gmail.com', 'email_verified_at' => null, 'password' => '$2y$12$fuWe1hdrjJKB/hT8VuOSIef.fUrtOzv6D99cURdGSa.cfen0VJOlO', 'role' => 'kasir', 'status' => 'aktif', 'avatar' => 'kasir/0GjZsb8MCE885ew7jPw97FU3pLf8eV5XvxiiN0Ks.jpg', 'alamat' => 'baros kec cimahi tengah', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2005-10-04', 'remember_token' => null, 'created_at' => '2026-09-14 23:18:40', 'updated_at' => '2026-10-07 15:07:04'],
            ['id' => 3, 'name' => 'byeonwooseok', 'email' => 'kasir2@gmail.com', 'email_verified_at' => null, 'password' => '$2y$12$tnNoczTNe8CDy3oiCghhmuVGeWfan1X0cvzvREnyhmu4QV1ZICgla', 'role' => 'kasir', 'status' => 'aktif', 'avatar' => 'kasir/wQrMx2hrerKElEgeudXa59u3X2YlkJqXakappED0.jpg', 'alamat' => 'korea selatan', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '1991-10-31', 'remember_token' => null, 'created_at' => '2026-09-25 04:45:43', 'updated_at' => '2026-10-02 06:47:56'],
        ];

        $this->upsertDump('users', $rows, ['name', 'email', 'email_verified_at', 'password', 'role', 'status', 'avatar', 'alamat', 'jenis_kelamin', 'tanggal_lahir', 'remember_token', 'created_at', 'updated_at']);
    }
}
