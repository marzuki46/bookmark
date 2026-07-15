<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Item;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ExportController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $items = Item::where('user_id', $userId)
            ->with(['tags', 'collections'])
            ->get()
            ->map(fn (Item $item) => [
                'type' => $item->type,
                'title' => $item->title,
                'url' => $item->url,
                'content' => $item->content,
                'metadata' => $item->metadata,
                'favorite' => $item->favorite,
                'tags' => $item->tags->pluck('name'),
                'collections' => $item->collections->pluck('name'),
            ]);

        $tags = Tag::where('user_id', $userId)->get()->map(fn (Tag $tag) => [
            'name' => $tag->name,
            'slug' => $tag->slug,
        ]);

        $collections = Collection::where('user_id', $userId)->get()->map(fn (Collection $c) => [
            'name' => $c->name,
            'slug' => $c->slug,
            'description' => $c->description,
        ]);

        $export = [
            'version' => '1.0',
            'exported_at' => now()->toISOString(),
            'user' => $request->user()->only(['name', 'email']),
            'items' => $items,
            'tags' => $tags,
            'collections' => $collections,
        ];

        return response()->json($export, 200, [
            'Content-Disposition' => 'attachment; filename="bookmark-backup-'.now()->format('Y-m-d').'.json"',
        ]);
    }
}
