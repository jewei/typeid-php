<?php

declare(strict_types=1);

/**
 * Architecture check: enforces the supported surface documented in the README.
 *
 * TypeID is the only supported entry-point module. Base32 and Validator are
 * internal: PHP cannot make them package-private, so this check enforces in
 * this repository what the compatibility policy promises to consumers.
 *
 * It cannot stop an external consumer from calling an autoloadable class. That
 * outer limit is a documented promise, not a mechanism.
 *
 * Run: composer test:architecture
 */
const INTERNAL = ['Base32', 'Validator'];

/** Production files permitted to reference each internal module. */
const PRODUCTION_ALLOW = [
    'src/TypeID.php' => ['Base32', 'Validator'],
    'src/Base32.php' => ['Validator'],
];

/** Test directories permitted to reference each internal module. */
const TEST_ALLOW = [
    'tests/Codec/' => ['Base32'],
];

$root = dirname(__DIR__);
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
        if ($file->isFile() && $file->getExtension() === 'php') {
            $found[] = substr($file->getPathname(), strlen($root) + 1);
        }
    }

    sort($found);

    return $found;
};

/** @return list<string> Internal modules referenced by this file. */
$referencesIn = static function (string $relative) use ($root): array {
    $source = file_get_contents($root.'/'.$relative);

    if ($source === false) {
        return [];
    }

    $found = [];

    foreach (INTERNAL as $module) {
        // Static call, instantiation, use statement, or fully-qualified mention.
        if (preg_match('/\b'.$module.'\s*::|use\s+TypeID\\\\'.$module.'\b|new\s+'.$module.'\b/', $source) === 1) {
            $found[] = $module;
        }
    }

    return $found;
};

// ── Production code ────────────────────────────────────────────────────────
foreach ($filesIn('src') as $file) {
    // A module referencing itself is not a dependency.
    $self = basename($file, '.php');
    $allowed = PRODUCTION_ALLOW[$file] ?? [];

    foreach ($referencesIn($file) as $module) {
        if ($module === $self || in_array($module, $allowed, true)) {
            continue;
        }

        $violations[] = "{$file} references internal module {$module} (not in the production allow-list)";
    }
}

// ── Tests ──────────────────────────────────────────────────────────────────
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

// ── Public documentation ───────────────────────────────────────────────────
// Naming an internal symbol to mark it unsupported is allowed. Showing it as a
// callable entry point is not.
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

// ── Report ─────────────────────────────────────────────────────────────────
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
