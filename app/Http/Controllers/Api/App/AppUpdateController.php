<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * In-app update check. The values come from environment config so a release
 * is a config change on the server, not a new build pushed to the stores.
 */
final class AppUpdateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $current = max(0, $request->integer('current_version_code'));

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
                'update_available' => $versionCode > $current,
            ],
        ]);
    }
}