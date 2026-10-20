<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AppRelease;
use App\Models\ApkDownload;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

final class KeuanganController extends Controller
{
    public function dashboard(): View
    {
        return view('pages.keuangan-dashboard');
    }

    public function users(): View
    {
        return view('pages.keuangan-users');
    }

    public function plans(): View
    {
        return view('pages.keuangan-plans');
    }

    public function subscriptions(): View
    {
        return view('pages.keuangan-subscriptions');
    }

    public function userFinances(): View
    {
        return view('pages.keuangan-user-finances');
    }

    public function familyDetail(Family $family): View
    {
        return view('pages.keuangan-family-detail', compact('family'));
    }

    public function logs(): View
    {
        return view('pages.keuangan-logs');
    }

    public function affirmations(): View
    {
        return view('pages.keuangan-affirmations');
    }

    public function appManager(Request $request): View
    {
        $activeTab = match ($request->query('tab')) {
            'rilis' => 'rilis',
            'lisensi' => 'lisensi',
            'log' => 'log',
            'pembayaran' => 'pembayaran',
            'promo' => 'promo',
            'ai' => 'ai',
            default => 'lisensi',
        };

        $downloadMonths = collect(range(0, 11))->map(function (int $monthsAgo): array {
            $start = now()->startOfMonth()->subMonths($monthsAgo);
            $end = $start->copy()->endOfMonth();

            return [
                'label' => $start->translatedFormat('F Y'),
                'count' => Schema::hasTable('apk_downloads')
                    ? ApkDownload::query()->whereBetween('downloaded_at', [$start, $end])->count()
                    : 0,
            ];
        });

        return view('pages.keuangan-aplikasi-manajemen', [
            'activeTab' => $activeTab,
            'releases' => AppRelease::query()->orderByDesc('version_code')->limit(20)->get(),
            'downloadMonths' => $downloadMonths,
            'downloadsThisMonth' => $downloadMonths->first()['count'],
        ]);
    }
}
