<?php

use App\Http\Controllers\AiCenterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\SetupController;
use App\Models\Item;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/files/download/{id}', function ($id) {
        $item = Item::where('user_id', auth()->id())->where('type', 'file')->findOrFail($id);
        $path = $item->metadata['path'] ?? null;
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->download($path, $item->metadata['original_name'] ?? 'download');
        }
        abort(404);
    })->name('files.download');
    Route::view('/bookmarks', 'bookmarks')->name('bookmarks');
    Route::view('/notes', 'pages.notes')->name('notes');
    Route::view('/collections', 'pages.collections')->name('collections');
    Route::view('/tags', 'pages.tags')->name('tags');
    Route::view('/prompts', 'pages.prompts')->name('prompts');
    Route::view('/snippets', 'pages.snippets')->name('snippets');
    Route::view('/files', 'pages.files')->name('files');
    Route::view('/secrets', 'pages.secrets')->name('secrets');
    Route::get('/ai', AiCenterController::class)->name('ai');
    Route::view('/search', 'pages.search')->name('search');
    Route::view('/activity', 'pages.activity')->name('activity');
    Route::view('/backup', 'pages.backup')->name('backup');
    Route::view('/dead-links', 'pages.dead-links')->name('dead-links');
    Route::view('/ip-blocker', 'pages.ip-blocker')->name('ip-blocker');
    Route::view('/invoices', 'pages.invoices')->name('invoices');
    Route::view('/invoices/create', 'pages.invoice-create')->name('invoices.create');
    Route::get('/invoices/{id}/edit', fn ($id) => view('pages.invoice-edit', ['id' => $id]))->name('invoices.edit');
    Route::get('/invoices/{id}/print', fn ($id) => view('pages.invoice-print', ['id' => $id]))->name('invoices.print');
    Route::view('/bills', 'pages.bills')->name('bills');
    Route::get('/financial', FinancialReportController::class)->name('financial');
    Route::view('/companies', 'pages.companies')->name('companies');
    Route::view('/extension', 'pages.extension')->name('extension');
    Route::get('/extension/download', function () {
        $zipFile = storage_path('app/knowledge-hub-extension.zip');

        if (! file_exists($zipFile) || (time() - filemtime($zipFile)) > 3600) {
            $zip = new ZipArchive;
            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $extensionPath = base_path('extension');
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($extensionPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $file) {
                    if (! $file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = str_replace('\\', '/', substr($filePath, strlen($extensionPath) + 1));
                        $zip->addFile($filePath, $relativePath);
                    }
                }

                $zip->close();
            }
        }

        if (file_exists($zipFile)) {
            return response()->download($zipFile, 'knowledge-hub-extension.zip')->deleteFileAfterSend(true);
        }

        abort(500, 'Failed to create extension package.');
    })->name('extension.download');
    Route::view('/settings', 'pages.settings')->name('settings');
    Route::get('/export', ExportController::class)->name('export');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');

Route::get('/setup', [SetupController::class, 'show'])->name('setup');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');

Route::get('/password-change/approve/{token}', [PasswordChangeController::class, 'approve'])->name('password-change.approve');
Route::get('/password-change/reject/{token}', [PasswordChangeController::class, 'reject'])->name('password-change.reject');
