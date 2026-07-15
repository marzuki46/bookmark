<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FinancialWebhookController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// WhatsApp Financial Webhook (no auth, Fonnte sends here)
// Fonnte requires POST and GET method support
Route::match(['GET', 'POST'], '/webhook/wa-finance', [FinancialWebhookController::class, 'handleIncoming']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn (Request $request) => $request->user());

    Route::get('/tokens', [TokenController::class, 'index']);
    Route::post('/tokens', [TokenController::class, 'store']);
    Route::delete('/tokens/{id}', [TokenController::class, 'destroy']);

    Route::apiResource('items', ItemController::class);
    Route::post('/items/render', [ItemController::class, 'render']);

    Route::get('/search', function (Request $request) {
        $query = $request->input('q');
        if (! $query) {
            return response()->json(['data' => []]);
        }

        $items = Item::where('user_id', auth()->id())
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('url', 'LIKE', "%{$query}%")
                    ->orWhere('content', 'LIKE', "%{$query}%");
            })
            ->with(['tags'])
            ->latest()
            ->limit(20)
            ->get();

        return response()->json(['data' => $items]);
    });
});
