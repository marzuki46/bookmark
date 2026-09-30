<?php

use App\Http\Controllers\Api\App\AffirmationController;
use App\Http\Controllers\Api\App\AppErrorController;
use App\Http\Controllers\Api\App\AppUpdateController;
use App\Http\Controllers\Api\App\CodeAuthController;
use App\Http\Controllers\Api\App\DeviceController;
use App\Http\Controllers\Api\App\ProfileController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Family\BudgetController as FamilyBudgetController;
use App\Http\Controllers\Api\Family\DebtController as FamilyDebtController;
use App\Http\Controllers\Api\Family\FamilyAdvisorController;
use App\Http\Controllers\Api\Family\FamilyCategoryController;
use App\Http\Controllers\Api\Family\FamilyController;
use App\Http\Controllers\Api\Family\GoalController as FamilyGoalController;
use App\Http\Controllers\Api\Family\IncomeSourceController as FamilyIncomeSourceController;
use App\Http\Controllers\Api\Family\InsightController as FamilyInsightController;
use App\Http\Controllers\Api\Family\TransactionController as FamilyTransactionController;
use App\Http\Controllers\Api\Finance\CategoryController;
use App\Http\Controllers\Api\Finance\DashboardController;
use App\Http\Controllers\Api\Finance\FinanceAiController;
use App\Http\Controllers\Api\Finance\TransactionController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\Payments\CheckoutController;
use App\Http\Controllers\Api\Payments\DuitkuWebhookController;
use App\Http\Controllers\Api\Payments\MidtransWebhookController;
use App\Http\Controllers\Api\Payments\SubscriptionPlanController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// App login by permanent code (no email/password).
// The third argument is a counter prefix: without it this route would share a
// counter with the global 60/min API throttle, double-counting every hit and
// halving the effective limit.
Route::post('/app/login', [CodeAuthController::class, 'login'])
    ->middleware('throttle:5,1,login-code');

// Midtrans pushes transaction state changes here (no auth on purpose:
// authenticity comes from the signature, see MidtransWebhookController).
Route::post('/payments/midtrans/notification', [MidtransWebhookController::class, 'notification']);
Route::post('/payments/duitku/callback', [DuitkuWebhookController::class, 'callback'])
    ->name('payments.duitku.callback');

// App diagnostics & updates are public so the app can report crashes and
// check for updates before/without a login. Both are throttled.
Route::get('/app/updates', [AppUpdateController::class, 'index'])
    ->middleware('throttle:60,1,app-updates');
Route::post('/app/errors', [AppErrorController::class, 'store'])
    ->middleware('throttle:20,1,app-errors');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn (Request $request) => $request->user());

    // Personal profile + entitlement (Android "personal" menu).
    Route::get('/me', [ProfileController::class, 'me']);
    Route::match(['put', 'patch'], '/me', [ProfileController::class, 'update']);

    // Kang Cuan message templates (server only stores templates; scheduling,
    // history and deletion live on the device).
    Route::get('/app/affirmations', [AffirmationController::class, 'index']);

    // Paid plans & purchases (Midtrans Snap).
    Route::get('/subscription', [SubscriptionPlanController::class, 'current']);
    Route::get('/subscription/plans', [SubscriptionPlanController::class, 'index']);
    Route::post('/subscription/charge', [CheckoutController::class, 'charge'])
        ->middleware('throttle:10,1,checkout');

    Route::post('/app/login-code/rotate', [CodeAuthController::class, 'rotate']);

    // ─── Devices (FCM push registration) ───
    Route::get('/app/devices', [DeviceController::class, 'index']);
    Route::post('/app/devices', [DeviceController::class, 'store']);
    Route::delete('/app/devices/{device}', [DeviceController::class, 'destroy']);

    // ─── Family (multi-household) ───
    Route::get('/families', [FamilyController::class, 'index']);
    Route::get('/families/{family}', [FamilyController::class, 'show']);
    Route::get('/families/{family}/summary', [FamilyController::class, 'summary']);
    Route::get('/families/{family}/forecast', [FamilyController::class, 'forecast']);
    Route::match(['put', 'patch'], '/families/{family}/me', [FamilyController::class, 'updateMyProfile']);

    Route::prefix('families/{family}')->group(function (): void {
        Route::get('login-code', [FamilyController::class, 'loginCode']);
        Route::post('members', [FamilyController::class, 'storeMember']);
        Route::match(['put', 'patch'], 'members/{memberUser}', [FamilyController::class, 'updateMember']);
        Route::delete('members/{memberUser}', [FamilyController::class, 'destroyMember']);

        Route::get('transactions', [FamilyTransactionController::class, 'index']);
        Route::post('transactions', [FamilyTransactionController::class, 'store']);
        // Trend must be registered before the wildcard route below, otherwise
        // Laravel would treat "trend" as a {transaction} id.
        Route::get('transactions/trend', [FamilyController::class, 'trend']);
        Route::get('transactions/{transaction}', [FamilyTransactionController::class, 'show']);
        Route::match(['put', 'patch'], 'transactions/{transaction}', [FamilyTransactionController::class, 'update']);
        Route::delete('transactions/{transaction}', [FamilyTransactionController::class, 'destroy']);

        Route::get('debts', [FamilyDebtController::class, 'index']);
        Route::post('debts', [FamilyDebtController::class, 'store']);
        Route::get('debts/{debt}', [FamilyDebtController::class, 'show']);
        Route::match(['put', 'patch'], 'debts/{debt}', [FamilyDebtController::class, 'update']);
        Route::delete('debts/{debt}', [FamilyDebtController::class, 'destroy']);
        Route::post('debts/{debt}/pay', [FamilyDebtController::class, 'pay']);

        Route::get('budgets', [FamilyBudgetController::class, 'index']);
        Route::post('budgets', [FamilyBudgetController::class, 'store']);
        Route::match(['put', 'patch'], 'budgets/{budget}', [FamilyBudgetController::class, 'update']);
        Route::delete('budgets/{budget}', [FamilyBudgetController::class, 'destroy']);

        Route::get('goals', [FamilyGoalController::class, 'index']);
        Route::post('goals', [FamilyGoalController::class, 'store']);
        Route::match(['put', 'patch'], 'goals/{goal}', [FamilyGoalController::class, 'update']);
        Route::post('goals/{goal}/contribute', [FamilyGoalController::class, 'contribute']);
        Route::delete('goals/{goal}', [FamilyGoalController::class, 'destroy']);

        Route::get('income-sources', [FamilyIncomeSourceController::class, 'index']);
        Route::post('income-sources', [FamilyIncomeSourceController::class, 'store']);
        Route::match(['put', 'patch'], 'income-sources/{incomeSource}', [FamilyIncomeSourceController::class, 'update']);
        Route::delete('income-sources/{incomeSource}', [FamilyIncomeSourceController::class, 'destroy']);

        // Insights: weekly AI text (read from cache) + instant free nudges.
        Route::get('insights', [FamilyInsightController::class, 'index']);
        Route::get('nudge', [FamilyInsightController::class, 'nudge']);
        Route::post('insights/read', [FamilyInsightController::class, 'markRead']);

        // Reminders for the app's background notifications + the 6-month trend.
        Route::get('reminders', [FamilyController::class, 'reminders']);

        // Kang Cuan — the family financial advisor ("Pendamping Keuangan").
        Route::get('advisor', [FamilyAdvisorController::class, 'status']);
        Route::post('advisor', [FamilyAdvisorController::class, 'update'])->middleware('throttle:20,1,advisor');
        Route::put('advisor/profile', [FamilyAdvisorController::class, 'saveProfile'])->middleware('throttle:20,1,advisor');

        // Categories are needed to build a transaction and were previously
        // web-only, so the app could record spending but not categorise it.
        Route::get('categories', [FamilyCategoryController::class, 'index']);
        Route::post('categories', [FamilyCategoryController::class, 'store']);
        Route::match(['put', 'patch'], 'categories/{category}', [FamilyCategoryController::class, 'update']);
        Route::delete('categories/{category}', [FamilyCategoryController::class, 'destroy']);
    });

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
        Route::post('ai/pricing', [FinanceAiController::class, 'pricing']);
        Route::post('ai/parse', [FinanceAiController::class, 'parse']);
    });
});
