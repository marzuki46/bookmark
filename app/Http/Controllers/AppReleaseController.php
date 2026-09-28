<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AppRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AppReleaseController extends Controller
{
    public function index(): View
    {
        return view('pages.keuangan-app-releases', [
            'releases' => AppRelease::query()->orderByDesc('version_code')->limit(20)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'apk' => ['required', 'file', 'mimes:apk,application/vnd.android.package-archive', 'max:51200'],
            'version_code' => ['required', 'integer', 'min:1', Rule::unique('app_releases', 'version_code')],
            'version_name' => ['required', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $file = $request->file('apk');
        $path = $file->storeAs(
            'apk',
            'keuangan-'.$validated['version_name'].'-'.$validated['version_code'].'.apk',
            'local'
        );

        if ($path === false) {
            return back()->withErrors(['apk' => 'Gagal menyimpan berkas APK.']);
        }

        $realPath = Storage::disk('local')->path($path);

        AppRelease::create([
            'version_code' => (int) $validated['version_code'],
            'version_name' => $validated['version_name'],
            'notes' => $validated['notes'] ?? null,
            'file_path' => $path,
            'file_size' => Storage::disk('local')->size($path),
            'sha256' => hash_file('sha256', $realPath) ?: null,
        ]);

        return redirect()->route('keuangan.aplikasi')
            ->with('status', 'Rilis v'.$validated['version_name'].' diunggah.');
    }

    public function destroy(AppRelease $release): RedirectResponse
    {
        Storage::disk('local')->delete($release->file_path);
        $release->delete();

        return redirect()->route('keuangan.aplikasi')
            ->with('status', 'Rilis dihapus.');
    }

    public function download(AppRelease $release): StreamedResponse
    {
        $disk = Storage::disk('local');
        $absolute = $disk->path($release->file_path);

        if (! $disk->exists($release->file_path)) {
            abort(404);
        }

        return response()->streamDownload(
            static function () use ($absolute): void {
                $handle = fopen($absolute, 'rb');
                if ($handle === false) {
                    abort(500, 'Cannot open release file.');
                }
                while (! feof($handle)) {
                    echo fread($handle, 1024 * 512);
                    flush();
                }
                fclose($handle);
            },
            'keuangan-v'.$release->version_code.'.apk',
            [
                'Content-Type' => 'application/vnd.android.package-archive',
                'Content-Length' => $release->file_size,
            ]
        );
    }
}