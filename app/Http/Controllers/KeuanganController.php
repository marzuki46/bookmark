<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AppRelease;
use Illuminate\Http\Request;
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

    public function logs(): View
    {
        return view('pages.keuangan-logs');
    }

    public function appManager(Request $request): View
    {
        $activeTab = match ($request->query('tab')) {
            'rilis' => 'rilis',
            'lisensi' => 'lisensi',
            'log' => 'log',
            default => 'lisensi',
        };

        return view('pages.keuangan-aplikasi-manajemen', [
            'activeTab' => $activeTab,
            'releases' => AppRelease::query()->orderByDesc('version_code')->limit(20)->get(),
        ]);
    }
}
