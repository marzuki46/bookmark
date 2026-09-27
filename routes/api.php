<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Finance\CategoryController;
use App\Http\Controllers\Api\Finance\DashboardController;
use App\Http\Controllers\Api\Finance\FinanceAiController;
use App\Http\Controllers\Api\Finance\TransactionController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

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

    // ─── Finance (Android app) ───
    Route::prefix('finance')->name('api.finance.')->group(function (): void {
        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::get('transactions', [TransactionController::class, 'index']);
        Route::post('transactions', [TransactionController::class, 'store']);
        Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
        Route::match(['put', 'patch'], 'transactions/{transaction}', [TransactionController::class, 'update']);
        Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy']);

        Route::get('categories', [CategoryController::class, 'index']);
        Route::post('categories', [CategoryController::class, 'store']);
        Route::match(['put', 'patch'], 'categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);

        Route::post('ai/advice', [FinanceAiController::class, 'advice']);
        Route::post('ai/ask', [FinanceAiController::class, 'ask']);
        Route::post('ai/parse', [FinanceAiController::class, 'parse']);
    });
});
