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

        $typeCounts = Item::where('user_id', $userId)
            ->selectRaw("type, COUNT(*) as cnt")
            ->groupBy('type')
            ->pluck('cnt', 'type')
            ->toArray();

        $pendingTodos = Item::where('user_id', $userId)
            ->where('type', 'todo')
            ->whereJsonContains('metadata->completed', false)
            ->count();

        return view('dashboard', [
            'totalBookmarks' => $typeCounts['bookmark'] ?? 0,
            'totalNotes' => $typeCounts['note'] ?? 0,
            'totalWorksheets' => $typeCounts['worksheet'] ?? 0,
            'totalTodos' => $typeCounts['todo'] ?? 0,
            'pendingTodos' => $pendingTodos,
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
