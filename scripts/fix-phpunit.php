<?php

declare(strict_types=1);

/**
 * KYAMCH Smart IPAM - Tooling Compatibility Patch
 *
 * Resolves PHPUnit 12 on Windows environments where PHP's native is_readable()
 * can return false due to Windows token / ACL descriptors even when files
 * are fully accessible and readable.
 */
$bootstrapLoaderFile = __DIR__ . '/../vendor/phpunit/phpunit/src/TextUI/Configuration/BootstrapLoader.php';

if (file_exists($bootstrapLoaderFile)) {
    $content = file_get_contents($bootstrapLoaderFile);
    if ($content !== false && str_contains($content, 'if (!is_readable($filename)) {')) {
        $content = str_replace(
            'if (!is_readable($filename)) {',
            'if (!is_readable($filename) && !file_exists($filename)) {',
            $content
        );
        file_put_contents($bootstrapLoaderFile, $content);
        echo "[KYAMCH IPAM] Patched PHPUnit BootstrapLoader for Windows sandbox compatibility.\n";
    }
}
