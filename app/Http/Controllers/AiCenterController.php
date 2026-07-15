<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Item;
use App\Models\Tag;
use Illuminate\Http\Request;

class AiCenterController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $stats = [
            'totalItems' => Item::where('user_id', $user->id)->count(),
            'bookmarks' => Item::where('user_id', $user->id)->where('type', 'bookmark')->count(),
            'notes' => Item::where('user_id', $user->id)->where('type', 'note')->count(),
            'prompts' => Item::where('user_id', $user->id)->where('type', 'prompt')->count(),
            'snippets' => Item::where('user_id', $user->id)->where('type', 'snippet')->count(),
            'tags' => Tag::where('user_id', $user->id)->count(),
            'collections' => Collection::where('user_id', $user->id)->count(),
        ];

        $recentItems = Item::where('user_id', $user->id)
            ->with('tags')
            ->latest()
            ->take(10)
            ->get();

        $topTags = Tag::where('user_id', $user->id)
            ->withCount('items')
            ->orderByDesc('items_count')
            ->take(10)
            ->get();

        $aiSummaries = Item::where('user_id', $user->id)
            ->whereHas('aiSummary')
            ->with('aiSummary')
            ->latest()
            ->take(5)
            ->get();

        return view('pages.ai', compact('stats', 'recentItems', 'topTags', 'aiSummaries'));
    }
}
