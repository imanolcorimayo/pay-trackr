<?php
/**
 * DigitalOcean Spaces (S3-compatible) wrapper for AI input artifacts.
 *
 * Pattern lifted from /aula-de-gabi/admin/models/image_service.php — simplified
 * for our use case: no image processing, no size variants. Just store/get/delete
 * the original bytes (image, audio, or PDF) under `<prefix>/ai-uploads/...`,
 * always with ACL=private. Served back through the PHP proxy in payments.php.
 *
 * Construction is lazy — `getS3()` is only called when an actual operation runs,
 * so requests that never touch artifacts pay no SDK init cost.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class SpacesHandler
{
    private array $conf;
    private ?S3Client $s3 = null;

    public function __construct(array $spacesConf)
    {
        // Defensive: any missing field means we should fail loudly later, not
        // silently upload to the wrong place.
        foreach (['key', 'secret', 'region', 'bucket', 'endpoint', 'prefix'] as $k) {
            if (empty($spacesConf[$k])) {
                throw new \RuntimeException("spaces config: '$k' is required");
            }
        }
        $this->conf = $spacesConf;
    }

    /**
     * Build the full object key for an AI artifact. Path is relative to
     * `<prefix>/ai-uploads/`. The user_id is part of the key for namespacing
     * and quick-glance debugging — it is NOT used for access control (auth
     * happens on read via the transaction row's user_id check).
     */
    public function artifactKey(string $userId, string $uuid, string $ext): string
    {
        $safeUid  = preg_replace('/[^a-zA-Z0-9_-]/', '', $userId);
        $safeUuid = preg_replace('/[^a-zA-Z0-9_-]/', '', $uuid);
        $safeExt  = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        return $this->conf['prefix'] . "/ai-uploads/{$safeUid}/{$safeUuid}.{$safeExt}";
    }

    /**
     * Upload raw bytes. Returns true on success, false on failure.
     * On failure the caller should log + continue (artifact = nice-to-have).
     */
    public function put(string $key, string $bytes, string $mimeType): bool
    {
        try {
            $this->getS3()->putObject([
                'Bucket'      => $this->conf['bucket'],
                'Key'         => $key,
                'Body'        => $bytes,
                'ACL'         => 'private',
                'ContentType' => $mimeType,
            ]);
            return true;
        } catch (S3Exception $e) {
            error_log('[Spaces] put failed for ' . $key . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Stream an object back to PHP output. Honors If-None-Match for 304s.
     * Sends Content-Type, Content-Length, Cache-Control headers itself.
     * Returns false (and sets 404) if the object doesn't exist or fails.
     */
    public function streamToOutput(string $key, ?string $fallbackMime = null): bool
    {
        try {
            $head = $this->getS3()->headObject([
                'Bucket' => $this->conf['bucket'],
                'Key'    => $key,
            ]);
        } catch (S3Exception $e) {
            http_response_code(404);
            return false;
        }

        $etag = $head['ETag'] ?? null;
        if ($etag && ($_SERVER['HTTP_IF_NONE_MATCH'] ?? null) === $etag) {
            http_response_code(304);
            return true;
        }

        try {
            $obj = $this->getS3()->getObject([
                'Bucket' => $this->conf['bucket'],
                'Key'    => $key,
            ]);
        } catch (S3Exception $e) {
            http_response_code(404);
            return false;
        }

        $mime = $obj['ContentType'] ?? $fallbackMime ?? 'application/octet-stream';
        header("Content-Type: {$mime}");
        // Private artifacts: cacheable in the user's browser, never on shared caches.
        header('Cache-Control: private, max-age=31536000, immutable');
        if ($etag) header("ETag: {$etag}");
        if (isset($obj['ContentLength'])) header('Content-Length: ' . $obj['ContentLength']);

        echo $obj['Body'];
        return true;
    }

    public function delete(string $key): bool
    {
        try {
            $this->getS3()->deleteObject([
                'Bucket' => $this->conf['bucket'],
                'Key'    => $key,
            ]);
            return true;
        } catch (S3Exception $e) {
            error_log('[Spaces] delete failed for ' . $key . ': ' . $e->getMessage());
            return false;
        }
    }

    private function getS3(): S3Client
    {
        if ($this->s3 === null) {
            $this->s3 = new S3Client([
                'version'     => 'latest',
                'region'      => $this->conf['region'],
                'endpoint'    => $this->conf['endpoint'],
                'credentials' => [
                    'key'    => $this->conf['key'],
                    'secret' => $this->conf['secret'],
                ],
                'use_path_style_endpoint' => false,
            ]);
        }
        return $this->s3;
    }
}
