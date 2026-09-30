<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Affirmation;
use Illuminate\Database\Seeder;

final class AffirmationSeeder extends Seeder
{
    /**
     * Kang Cuan message templates. The app picks a random one per slot/variant
     * when the scheduled alarm fires, so users don't see the same text twice.
     * Admin can add more from the KEUANGAN web menu without an app update.
     */
    public function run(): void
    {
        $messages = [
            // ─── Pagi (penyemangat sebelum beraktifitas) ───
            ['slot' => 'pagi', 'variant' => null, 'content' => 'Selamat pagi Kak {nama}! ☀️ Hari baru adalah lembaran baru untuk mencatat cuan baru. Semoga rezeki hari ini mengalir lancar dan setiap pengeluaran selalu berbuah kebaikan.'],
            ['slot' => 'pagi', 'variant' => null, 'content' => 'Halo Kak {nama}! 🌅 Semangat pagi! Yuk mulai hari dengan mencatat rencana keuangan, biar setiap rupiah yang keluar terarah dan yang masuk makin deras.'],
            ['slot' => 'pagi', 'variant' => null, 'content' => 'Pagi yang cerah, Kak {nama}! ☀️ Ingat, kebiasaan kecil mencatat pemasukan dan pengeluaran adalah kunci menuju kebebasan finansial. Tetap semangat!'],
            ['slot' => 'pagi', 'variant' => null, 'content' => 'Selamat beraktivitas, Kak {nama}! 💪 Mulailah hari dengan pikiran yang tenang. Keuangan yang sehat dimulai dari catatan yang rapi. Cuan hari ini nanti.'],
            ['slot' => 'pagi', 'variant' => null, 'content' => 'Hai Kak {nama}! 🌤️ Hari ini adalah kesempatan baru untuk mendekatkan diri pada tujuan finansial. Selamat berjuang, dan jangan lupa sisihkan sedikit untuk tabungan.'],

            // ─── Malam, pengeluaran lebih besar dari pemasukan ───
            ['slot' => 'malam', 'variant' => 'pengeluaran-luas', 'content' => 'Kak {nama}, hari ini pengeluaran lebih besar daripada pemasukan: pengeluaran {pengeluaran} dan pemasukan {pemasukan}. Tenang, ini cuma rekor harian, bukan vonis! Besok masih ada kesempatan untuk balik arah. Sabar yaa 🤗'],
            ['slot' => 'malam', 'variant' => 'pengeluaran-luas', 'content' => 'Hari ini kamu mengeluarkan uang sebesar {pengeluaran} ya Kak {nama}, sementara pemasukan {pemasukan}. Tidak apa-apa, yang penting sudah tercatat dan bisa dievaluasi. Tidur nyenyak, besok mulai lagi dengan lebih hemat 🌙'],
            ['slot' => 'malam', 'variant' => 'pengeluaran-luas', 'content' => 'Kak {nama}, catatan hari ini menunjukkan pengeluaran ({pengeluaran}) melebihi pemasukan ({pemasukan}). Anggap saja ini bahan evaluasi, bukan alasan khawatir. Esok hari bisa lebih baik lagi, semangat! 💛'],
            ['slot' => 'malam', 'variant' => 'pengeluaran-luas', 'content' => 'Wah pengeluaranmu hari ini cukup besar kak, yaitu sebesar {pengeluaran}, sementara pemasukan {pemasukan}. Semoga apa yang dikeluarkan hari ini akan datang kembali dalam bentuk keberlimpahan yang masuk ke rekeningmu esok hari.'],

            // ─── Malam, pemasukan lebih besar atau sama ───
            ['slot' => 'malam', 'variant' => 'pemasukan-luas', 'content' => 'Selamat! 🥳 Hari ini pemasukan Kak {nama} ({pemasukan}) lebih besar daripada pengeluaran ({pengeluaran}). Cuan jalan terus! Semoga alur keberlimpahan ini makin deras ke rekeningmu.'],
            ['slot' => 'malam', 'variant' => 'pemasukan-luas', 'content' => 'Keren, Kak {nama}! ⚡ Hari ini kamu berhasil mencatat pemasukan {pemasukan} yang lebih besar daripada pengeluaran {pengeluaran}. Pertahankan pola ini dan lihat tabunganmu bertumbuh!'],
            ['slot' => 'malam', 'variant' => 'pemasukan-luas', 'content' => 'Cuan masuk, pengeluaran rapi! 🎉 Kak {nama}, hari ini keuanganmu surplus: pemasukan {pemasukan}, pengeluaran {pengeluaran}. Jangan lupa sisihkan sebagian untuk masa depan ya. Hebat!'],

            // ─── Malam, tidak ada transaksi hari ini ───
            ['slot' => 'malam', 'variant' => 'kosong', 'content' => 'Kak {nama}, hari ini tidak ada transaksi yang tercatat. Semoga artinya kamu bisa menabung lebih banyak! Jangan lupa catat besok ya 🌙'],
            ['slot' => 'malam', 'variant' => 'kosong', 'content' => 'Tenang malam, Kak {nama}! 😴 Hari ini sepi transaksi. Bagus untuk kantong, tapi jangan lupa tetap mencatat kelak agar kebiasaannya tidak putus.'],

            // ─── Refleksi bulanan (tanggal 1 jam 10 malam) ───
            ['slot' => 'bulanan', 'variant' => null, 'content' => 'Kak {nama}, bulan ini sudah kita lampaui bersama! 📊 Yuk luangkan 5 menit merenung: sudah seberapa dekat kita dengan tujuan finansial keluarga? Mari atur strategi untuk bulan depan lebih baik.'],
            ['slot' => 'bulanan', 'variant' => null, 'content' => 'Bulan berganti, Kak {nama}! 📅 Saatnya refleksi: mana pengeluaran yang bisa dipangkas, mana pemasukan yang bisa ditingkatkan. Cuan adalah hasil dari evaluasi yang konsisten.'],
            ['slot' => 'bulanan', 'variant' => null, 'content' => 'Malam renungan, Kak {nama} 🌙 Bulan baru adalah kesempatan untuk reset. Lihat kembali target tabungan, lalu susun langkah kecil yang realistis untuk mencapai mimpi keluarga.'],
        ];

        foreach ($messages as $message) {
            Affirmation::query()->updateOrCreate(
                ['slot' => $message['slot'], 'variant' => $message['variant'], 'content' => $message['content']],
            );
        }
    }
}