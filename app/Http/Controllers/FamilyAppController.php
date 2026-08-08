<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final class FamilyAppController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.family-app');
    }

    public function add(): View
    {
        return view('pages.family-app-add');
    }

    public function allocation(): View
    {
        return view('pages.family-app-allocation');
    }

    public function goals(): View
    {
        return view('pages.family-app-goals');
    }

    public function debts(): View
    {
        return view('pages.family-app-debts');
    }

    public function budget(): View
    {
        return view('pages.family-app-budget');
    }
}
