<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Resources\ItemResource;
use App\Jobs\FetchBookmarkMetadata;
use App\Models\Item;
use App\Models\Tag;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ItemController extends Controller
{
    public function index(): JsonResource
    {
        $items = Item::with(['tags', 'folder'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return ItemResource::collection($items);
    }

    public function store(StoreItemRequest $request): JsonResponse
    {
        $item = Item::create([
            'user_id' => auth()->id(),
            'type' => $request->type,
            'title' => $request->title,
            'url' => $request->url,
            'content' => $request->content,
            'metadata' => $request->metadata ? json_decode($request->metadata, true) : null,
            'folder_id' => $request->folder_id,
            'favorite' => $request->favorite ?? false,
        ]);

        if ($request->has('tags')) {
            $tagIds = [];
            foreach ($request->tags as $tagName) {
                $tag = Tag::firstOrCreate([
                    'user_id' => auth()->id(),
                    'slug' => str($tagName)->slug(),
                ], [
                    'name' => $tagName,
                ]);
                $tagIds[] = $tag->id;
            }
            $item->tags()->sync($tagIds);
        }

        if ($item->type === 'bookmark' && $item->url) {
            FetchBookmarkMetadata::dispatchSync($item);
        }

        return (new ItemResource($item->load(['tags', 'folder'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Item $item): JsonResource
    {
        $this->authorizeOwnership($item);

        return new ItemResource($item->load(['tags', 'folder', 'attachments', 'aiSummary']));
    }

    public function update(StoreItemRequest $request, Item $item): JsonResource
    {
        $this->authorizeOwnership($item);

        $item->update($request->validated());

        return new ItemResource($item->load(['tags', 'folder']));
    }

    public function destroy(Item $item): JsonResponse
    {
        $this->authorizeOwnership($item);

        $item->delete();

        return response()->json(['message' => 'Deleted'], 200);
    }

    public function render(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:500',
            'url' => 'required|url',
            'content' => 'nullable|string',
        ]);

        $ai = new AIService;
        if (! $ai->isConfigured()) {
            return response()->json(['error' => 'AI not configured. Set API key in Settings.'], 422);
        }

        $summary = $ai->renderPage(
            $request->input('title'),
            $request->input('url'),
            $request->input('content')
        );

        if ($summary === null) {
            return response()->json(['error' => 'AI render failed. Check your API settings.'], 500);
        }

        return response()->json([
            'summary' => $summary,
            'model' => $ai->getSettings()['model'] ?? 'unknown',
        ]);
    }

    private function authorizeOwnership(Item $item): void
    {
        abort_if($item->user_id !== auth()->id(), 403, 'Forbidden');
    }
}
