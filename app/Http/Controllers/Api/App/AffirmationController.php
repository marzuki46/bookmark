<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Affirmation;
use Illuminate\Http\JsonResponse;

/**
 * Kang Cuan message templates. The server only stores templates — scheduling,
 * message history and deletion all happen on the device (stored in the user's
 * local JSON), so a simple list is all the app needs to stay fresh.
 */
final class AffirmationController extends Controller
{
    public function index(): JsonResponse
    {
        $affirmations = Affirmation::query()
            ->where('is_active', true)
            ->orderBy('slot')
            ->orderBy('variant')
            ->get([
                'id',
                'slot',
                'variant',
                'content',
            ]);

        return response()->json([
            'data' => $affirmations->map(fn (Affirmation $a) => [
                'id' => $a->id,
                'slot' => $a->slot,
                'variant' => $a->variant,
                'content' => $a->content,
            ]),
        ]);
    }
}