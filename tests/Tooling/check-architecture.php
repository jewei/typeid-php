<?php

declare(strict_types=1);

use TypeID\Development\ArchitectureChecker;

require_once __DIR__.'/ArchitectureChecker.php';

/**
 * Enforces the public API documented in the README.
 *
 * TypeID is the only public entry point. PHP cannot hide the Base32 class from
 * Composer, so this check prevents repository code from depending on it.
 * External consumers can still call the class, but receive no compatibility
 * guarantee.
 *
 * Run with composer test:architecture.
 */
const INTERNAL = ['Base32'];

/** Production files allowed to reference each internal class. */
const PRODUCTION_ALLOW = [
    'src/TypeID.php' => ['Base32'],
];

/** Test directories allowed to reference each internal class. */
const TEST_ALLOW = [
    'tests/Codec/' => ['Base32'],
];

$root = dirname(__DIR__, 2);
$violations = [];

/** @return list<string> */
$filesIn = static function (string $dir) use ($root): array {
    $path = $root.'/'.$dir;

    if (! is_dir($path)) {
        return [];
    }

    $found = [];
    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

    foreach ($iterator as $file) {
        if (! $file instanceof \SplFileInfo) {
            continue;
        }

        if ($file->isFile() && $file->getExtension() === 'php') {
            $relative = substr($file->getPathname(), strlen($root) + 1);
            $found[] = str_replace('\\', '/', $relative);
        }
    }

    sort($found);

    return $found;
};

/** @return list<string> Internal classes referenced by this file. */
$referencesIn = static function (string $relative) use ($root): array {
    $source = file_get_contents($root.'/'.$relative);

    if ($source === false) {
        return [];
    }

    return ArchitectureChecker::internalReferences($source, INTERNAL);
};

foreach ($filesIn('src') as $file) {
    // A class may reference itself.
    $self = basename($file, '.php');
    $allowed = PRODUCTION_ALLOW[$file] ?? [];

    foreach ($referencesIn($file) as $module) {
        if ($module === $self || in_array($module, $allowed, true)) {
            continue;
        }

        $violations[] = "{$file} references internal module {$module} (not in the production allow-list)";
    }
}

foreach ($filesIn('tests') as $file) {
    $allowed = [];

    foreach (TEST_ALLOW as $prefix => $modules) {
        if (str_starts_with($file, $prefix)) {
            $allowed = $modules;
        }
    }

    foreach ($referencesIn($file) as $module) {
        if (in_array($module, $allowed, true)) {
            continue;
        }

        $violations[] = "{$file} references internal module {$module} (tests must use the TypeID seam; only tests/Codec/ may reach Base32)";
    }
}

// Documentation may name an internal class, but must not call it.
foreach (['README.md', 'CONTEXT.md'] as $doc) {
    $path = $root.'/'.$doc;

    if (! is_file($path)) {
        continue;
    }

    $contents = file_get_contents($path);

    foreach (INTERNAL as $module) {
        if ($contents !== false && preg_match('/\b'.$module.'::/', $contents) === 1) {
            $violations[] = "{$doc} documents {$module} as a callable entry point (it may be named only as unsupported)";
        }
    }
}

if ($violations !== []) {
    fwrite(STDERR, "Architecture check failed:\n\n");

    foreach ($violations as $violation) {
        fwrite(STDERR, "  • {$violation}\n");
    }

    fwrite(STDERR, "\n".count($violations)." violation(s).\n");

    exit(1);
}

$checked = count($filesIn('src')) + count($filesIn('tests'));

echo "Architecture check passed ({$checked} files; TypeID is the only supported seam).\n";

exit(0);
