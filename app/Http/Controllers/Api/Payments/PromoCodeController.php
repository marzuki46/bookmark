<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Services\PromoCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PromoCodeController extends Controller
{
    public function redeem(Request $request, PromoCodeService $promos): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:80']]);

        try {
            $promos->redeem($request->user(), $data['code']);
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return response()->json(['message' => 'Kode promo berhasil digunakan. Langganan gratis sudah aktif.']);
    }
}
