<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AppRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use ZipArchive;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AppReleaseController extends Controller
{
    private const MAX_APK_BYTES = 52428800;

    public function index(): View
    {
        return view('pages.keuangan-app-releases', [
            'releases' => AppRelease::query()->orderByDesc('version_code')->limit(20)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
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

        $release = AppRelease::create([
            'version_code' => (int) $validated['version_code'],
            'version_name' => $validated['version_name'],
            'notes' => $validated['notes'] ?? null,
            'file_path' => $path,
            'file_size' => Storage::disk('local')->size($path),
            'sha256' => hash_file('sha256', $realPath) ?: null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Rilis v'.$validated['version_name'].' berhasil diunggah.',
                'release_id' => $release->id,
            ], 201);
        }

        return redirect()->route('keuangan.aplikasi')
            ->with('status', 'Rilis v'.$validated['version_name'].' diunggah.');
    }

    /** Store one small part so Cloudflare never has to hold a 50 MB request. */
    public function uploadChunk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{16,80}$/'],
            'chunk_index' => ['required', 'integer', 'min:0', 'max:100'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:100'],
            'chunk' => ['required', 'file', 'max:4096'],
        ]);

        if ($data['chunk_index'] >= $data['total_chunks']) {
            return response()->json(['message' => 'Nomor potongan upload tidak valid.'], 422);
        }

        $disk = Storage::disk('local');
        $directory = 'apk-chunks/'.$data['upload_id'];
        $disk->makeDirectory($directory);
        $path = $request->file('chunk')->storeAs(
            $directory,
            'chunk-'.$data['chunk_index'].'.part',
            'local',
        );

        return response()->json([
            'message' => 'Potongan diterima.',
            'chunk_index' => $data['chunk_index'],
            'path' => $path,
        ]);
    }

    /** Assemble the parts and create the same AppRelease record as normal upload. */
    public function finalizeChunked(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{16,80}$/'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:100'],
            'version_code' => ['required', 'integer', 'min:1', Rule::unique('app_releases', 'version_code')],
            'version_name' => ['required', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $disk = Storage::disk('local');
        $directory = 'apk-chunks/'.$data['upload_id'];
        $directoryPath = $disk->path($directory);
        $assembledRelative = $directory.'/assembled.apk';
        $assembledPath = $disk->path($assembledRelative);

        if (! is_dir($directoryPath)) {
            return response()->json(['message' => 'Sesi upload tidak ditemukan atau sudah kedaluwarsa.'], 422);
        }

        $output = fopen($assembledPath, 'wb');
        if ($output === false) {
            return response()->json(['message' => 'Server tidak dapat menyiapkan berkas APK.'], 500);
        }

        try {
            for ($index = 0; $index < $data['total_chunks']; $index++) {
                $chunkPath = $disk->path($directory.'/chunk-'.$index.'.part');
                if (! is_file($chunkPath)) {
                    fclose($output);
                    @unlink($assembledPath);

                    return response()->json(['message' => 'Potongan upload belum lengkap. Silakan ulangi upload.'], 422);
                }

                $input = fopen($chunkPath, 'rb');
                if ($input === false) {
                    fclose($output);
                    @unlink($assembledPath);

                    return response()->json(['message' => 'Potongan APK tidak dapat dibaca.'], 422);
                }
                stream_copy_to_stream($input, $output);
                fclose($input);
            }
            fclose($output);

            if (filesize($assembledPath) > self::MAX_APK_BYTES || ! $this->isApk($assembledPath)) {
                @unlink($assembledPath);

                return response()->json(['message' => 'Berkas gabungan bukan APK yang valid atau melebihi 50 MB.'], 422);
            }

            $finalRelative = 'apk/keuangan-'.$data['version_name'].'-'.$data['version_code'].'.apk';
            $disk->makeDirectory('apk');
            $disk->move($assembledRelative, $finalRelative);
            $finalPath = $disk->path($finalRelative);
            $release = AppRelease::create([
                'version_code' => (int) $data['version_code'],
                'version_name' => $data['version_name'],
                'notes' => $data['notes'] ?? null,
                'file_path' => $finalRelative,
                'file_size' => filesize($finalPath),
                'sha256' => hash_file('sha256', $finalPath) ?: null,
            ]);
        } finally {
            $disk->deleteDirectory($directory);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Rilis v'.$data['version_name'].' berhasil diunggah.',
                'release_id' => $release->id,
            ], 201);
        }

        return redirect()->route('keuangan.aplikasi')
            ->with('status', 'Rilis v'.$data['version_name'].' diunggah.');
    }

    private function isApk(string $path): bool
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            return false;
        }

        $valid = $zip->locateName('AndroidManifest.xml') !== false;
        $zip->close();

        return $valid;
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
