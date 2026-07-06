<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class DeployWebhookController extends Controller
{
    private string $scriptPath;
    private string $logPath;

    public function __construct()
    {
        $this->scriptPath = base_path('scripts/deploy.sh');
        $this->logPath    = storage_path('logs/deploy.log');
    }

    /** POST /deploy/webhook  — trigger deploy dari CI/CD */
    public function trigger(Request $request): JsonResponse
    {
        if (!$this->authorized($request)) {
            Log::warning('Deploy webhook: token tidak valid dari ' . $request->ip());
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!file_exists($this->scriptPath)) {
            return response()->json([
                'error' => 'Deploy script tidak ditemukan: ' . $this->scriptPath,
            ], 500);
        }

        // Potong log lama agar tidak membengkak (simpan 500 baris terakhir)
        $this->trimLog();

        // Jalankan deploy di background — langsung return 202
        $cmd = sprintf(
            'nohup bash %s >> %s 2>&1 &',
            escapeshellarg($this->scriptPath),
            escapeshellarg($this->logPath)
        );
        exec($cmd);

        Log::info('Deploy webhook triggered dari ' . $request->ip());

        return response()->json([
            'status'   => 'started',
            'message'  => 'Deploy berjalan di background. Cek /deploy/log untuk progress.',
            'log_url'  => url('/deploy/log'),
        ], 202);
    }

    /** GET /deploy/log  — lihat output deploy terbaru */
    public function log(Request $request): Response
    {
        if (!$this->authorized($request)) {
            abort(403, 'Unauthorized');
        }

        if (!file_exists($this->logPath)) {
            return response('Belum ada deploy log.', 200)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }

        // Kembalikan 100 baris terakhir
        $lines   = file($this->logPath) ?: [];
        $content = implode('', array_slice($lines, -100));

        return response($content, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }

    /** GET /deploy/status  — cek apakah deploy terakhir berhasil (untuk CI poll) */
    public function status(Request $request): JsonResponse
    {
        if (!$this->authorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!file_exists($this->logPath)) {
            return response()->json(['status' => 'no_log']);
        }

        $content = file_get_contents($this->logPath);

        // Ambil blok deploy terakhir
        $blocks = explode('════════════════════════════════════', $content);
        $last   = end($blocks);

        if (str_contains($content, 'DEPLOY_SUCCESS')) {
            // Pastikan DEPLOY_SUCCESS ada di blok terakhir
            $lastSuccess = strrpos($content, 'DEPLOY_SUCCESS');
            $lastStart   = strrpos($content, 'DEPLOY DIMULAI');
            $success = $lastSuccess !== false && ($lastStart === false || $lastSuccess > $lastStart);

            return response()->json([
                'status'  => $success ? 'success' : 'running',
                'preview' => implode('', array_slice(explode("\n", trim($last)), -8)),
            ]);
        }

        if (str_contains($last, 'DEPLOY DIMULAI')) {
            return response()->json([
                'status'  => 'running',
                'preview' => implode('', array_slice(explode("\n", trim($last)), -5)),
            ]);
        }

        return response()->json(['status' => 'idle']);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function authorized(Request $request): bool
    {
        $secret = config('app.deploy_secret');
        if (empty($secret)) return false;

        $token = $request->header('X-Deploy-Token')
              ?? $request->header('Authorization')
              ?? $request->query('token');

        // Support "Bearer xxx" format
        $token = str_starts_with((string) $token, 'Bearer ')
            ? substr($token, 7)
            : $token;

        return hash_equals($secret, (string) $token);
    }

    private function trimLog(): void
    {
        if (!file_exists($this->logPath)) return;
        if (filesize($this->logPath) < 51200) return; // < 50KB, biarkan

        $lines = file($this->logPath);
        $lines = array_slice($lines, -500); // simpan 500 baris terakhir
        file_put_contents($this->logPath, implode('', $lines));
    }
}
