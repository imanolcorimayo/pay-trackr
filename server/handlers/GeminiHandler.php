<?php
/**
 * Gemini API handler with model rotation, daily exhaustion cache, and 503 retries.
 * Mirrors the gasto-obra GeminiHandler.js pattern but adapted for PHP request scope.
 */

class GeminiHandler {
    public const MODELS = [
        'gemini-2.5-flash',
        'gemini-3.1-flash-lite-preview',
        'gemini-2.5-flash-lite',
        'gemini-2.5-pro',
    ];

    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';
    // Per-model HTTP timeout. Vision with several images on Flash is usually <30s
    // but heavy spikes can hit 45s. Keep this snug so model rotation stays under
    // nginx's fastcgi_read_timeout.
    private const REQUEST_TIMEOUT_SEC = 45;
    // Total budget across all model attempts. If we exceed this, stop rotating
    // and return what we have rather than letting nginx kill us with a 504.
    private const TOTAL_BUDGET_SEC = 110;

    private string $api_key;
    private string $exhausted_cache_file;

    public function __construct(string $api_key) {
        $this->api_key = $api_key;
        $this->exhausted_cache_file = sys_get_temp_dir() . '/mangos-gemini-exhausted.json';
    }

    public function generateContent(array $parts, array $options = []): array {
        $opts = array_merge([
            'maxOutputTokens' => 16384,
            'temperature' => 0.2,
            'responseSchema' => null,
        ], $options);

        $tried = [];
        $last_error = null;
        $start = microtime(true);

        foreach (self::MODELS as $model) {
            $elapsed = microtime(true) - $start;
            if ($elapsed > self::TOTAL_BUDGET_SEC) {
                error_log("[GeminiHandler] Total budget exceeded after " . round($elapsed, 1) . "s, aborting before $model");
                $tried[] = "$model (skipped — budget exceeded)";
                $last_error = $last_error ?? 'budget_exceeded';
                break;
            }
            if ($this->isExhausted($model)) {
                $tried[] = "$model (skipped — exhausted today)";
                continue;
            }

            $model_start = microtime(true);
            $tried[] = $model;
            $result = $this->tryWithModel($model, $parts, $opts);
            $model_ms = (int)((microtime(true) - $model_start) * 1000);
            error_log("[GeminiHandler] $model took {$model_ms}ms, error=" . ($result['error'] ?? 'none'));

            if ($result['error'] === 'rate_limit') {
                $this->markExhausted($model);
                $last_error = 'rate_limit';
                continue;
            }
            if ($result['error'] === 'unavailable_503' || $result['error'] === 'transport_error') {
                // Rotate immediately, no in-model retry — keeps total budget tight.
                $last_error = $result['error'];
                continue;
            }
            if ($result['error'] !== null) {
                $last_error = $result['error'];
                continue;
            }

            return ['data' => $result['data'], 'model_used' => $model, 'tried' => $tried];
        }

        error_log("[GeminiHandler] All models failed. Last error: $last_error. Tried: " . implode(', ', $tried));
        return ['error' => $last_error ?? 'all_failed', 'tried' => $tried];
    }

    private function tryWithModel(string $model, array $parts, array $opts): array {
        $url = self::BASE_URL . "/{$model}:generateContent?key=" . urlencode($this->api_key);

        $generationConfig = [
            'temperature' => $opts['temperature'],
            'maxOutputTokens' => $opts['maxOutputTokens'],
        ];
        if ($opts['responseSchema']) {
            $generationConfig['responseMimeType'] = 'application/json';
            $generationConfig['responseSchema'] = $opts['responseSchema'];
        }

        $payload = [
            'contents' => [['parts' => $parts]],
            'generationConfig' => $generationConfig,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT_SEC,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log("[GeminiHandler] $model transport error: $err");
            return ['error' => 'transport_error', 'data' => null];
        }
        if ($status === 429) {
            return ['error' => 'rate_limit', 'data' => null];
        }
        if ($status === 503) {
            return ['error' => 'unavailable_503', 'data' => null];
        }
        if ($status !== 200) {
            error_log("[GeminiHandler] $model status $status: " . substr($response, 0, 500));
            return ['error' => "http_$status", 'data' => null];
        }

        $body = json_decode($response, true);
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ($text === '') {
            error_log("[GeminiHandler] $model empty text. Raw: " . substr($response, 0, 500));
            return ['error' => 'empty_response', 'data' => null];
        }

        if ($opts['responseSchema']) {
            $parsed = json_decode($text, true);
            if (!is_array($parsed)) {
                error_log("[GeminiHandler] $model JSON parse failed: " . substr($text, 0, 300));
                return ['error' => 'json_parse_failed', 'data' => null];
            }
            return ['error' => null, 'data' => $parsed];
        }

        return ['error' => null, 'data' => $text];
    }

    private function isExhausted(string $model): bool {
        $data = $this->loadExhausted();
        return ($data[$model] ?? '') === date('Y-m-d');
    }

    private function markExhausted(string $model): void {
        $data = $this->loadExhausted();
        $data[$model] = date('Y-m-d');
        @file_put_contents($this->exhausted_cache_file, json_encode($data));
    }

    private function loadExhausted(): array {
        if (!is_file($this->exhausted_cache_file)) return [];
        $raw = @file_get_contents($this->exhausted_cache_file);
        $data = json_decode($raw ?: '[]', true);
        return is_array($data) ? $data : [];
    }
}
