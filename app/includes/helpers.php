<?php

/**
 * Append ?v=<filemtime> to a static asset path so browsers re-fetch it
 * the moment the file changes on disk. Path is relative to /app
 * (e.g. "/assets/css/output.css"). Unknown files pass through.
 */
function asset(string $path): string {
    static $appRoot = null;
    if ($appRoot === null) {
        $appRoot = realpath(__DIR__ . '/..');
    }
    $file = $appRoot . $path;
    if (is_file($file)) {
        return $path . '?v=' . filemtime($file);
    }
    return $path;
}
