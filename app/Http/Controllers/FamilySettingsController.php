<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final class FamilySettingsController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.family-settings');
    }
}
