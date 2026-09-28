<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * In-app update check. When a release has been uploaded through the admin
 * panel it takes precedence; otherwise the environment config is used as a
 * fallback (a config change acts as a release without a new upload).
 */
final class AppUpdateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $current = max(0, $request->integer('current_version_code'));

        $release = AppRelease::query()->orderByDesc('version_code')->first();

        if ($release !== null) {
            return response()->json([
                'data' => [
                    'latest_version_code' => $release->version_code,
                    'latest_version_name' => $release->version_name,
                    'download_url' => route('app-release.download', $release),
                    'notes' => (string) $release->notes,
                    'file_size' => $release->file_size,
                    'sha256' => (string) $release->sha256,
                    'update_available' => $release->version_code > $current,
                ],
            ]);
        }

        $versionCode = (int) config('app.version_code', 1);
        $versionName = (string) config('app.version_name', '1.0.0');
        $downloadUrl = (string) config('app.apk_download_url', '');
        $notes = (string) config('app.apk_notes', '');

        return response()->json([
            'data' => [
                'latest_version_code' => $versionCode,
                'latest_version_name' => $versionName,
                'download_url' => $downloadUrl,
                'notes' => $notes,
                'file_size' => null,
                'sha256' => null,
                'update_available' => $versionCode > $current,
            ],
        ]);
    }
}