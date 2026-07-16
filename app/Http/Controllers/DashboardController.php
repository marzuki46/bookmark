<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Item;
use App\Models\Tag;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $userId = auth()->id();

        return view('dashboard', [
            'totalBookmarks' => Item::where('user_id', $userId)->where('type', 'bookmark')->count(),
            'totalNotes' => Item::where('user_id', $userId)->where('type', 'note')->count(),
            'totalWorksheets' => Item::where('user_id', $userId)->where('type', 'worksheet')->count(),
            'totalTodos' => Item::where('user_id', $userId)->where('type', 'todo')->count(),
            'pendingTodos' => Item::where('user_id', $userId)->where('type', 'todo')->whereJsonContains('metadata->completed', false)->count(),
            'totalTags' => Tag::where('user_id', $userId)->count(),
            'totalCollections' => Collection::where('user_id', $userId)->count(),
            'recentBookmarks' => Item::with('tags')
                ->where('user_id', $userId)
                ->where('type', 'bookmark')
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
