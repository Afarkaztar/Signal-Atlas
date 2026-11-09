<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Speed test berbasis browser: ping, unduh (stream byte acak), unggah.
 */
class SpeedTestController extends Controller
{
    public function index(): View
    {
        return view('speedtest.index');
    }

    public function ping(): JsonResponse
    {
        return response()->json(['t' => microtime(true)])
            ->header('Cache-Control', 'no-store');
    }

    public function download(Request $request): StreamedResponse
    {
        $megabytes = max(1, min(25, $request->integer('mb', 8)));
        $chunk = random_bytes(64 * 1024);

        return response()->stream(function () use ($megabytes, $chunk) {
            $total = $megabytes * 16; // 16 × 64KB = 1MB
            for ($i = 0; $i < $total; $i++) {
                echo $chunk;
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => (string) ($megabytes * 1024 * 1024),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        return response()->json(['received' => strlen($request->getContent())]);
    }
}
