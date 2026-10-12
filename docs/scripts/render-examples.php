<?php

declare(strict_types=1);

/*
 * This file is part of the QIF Library package.
 *
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

// Runs the examples of the documentation with the library itself, so the output the pages show is what the code
// really prints.
//
//     php docs/scripts/render-examples.php            # every example
//     php docs/scripts/render-examples.php parsing    # docs/examples/parsing.php only
//     php docs/scripts/render-examples.php --check    # fails when a committed output is missing or out of date (CI)
//
// Runs docs/examples/<example>.php from docs/examples/ (so they read export.qif by its name), with Composer's
// autoloader prepended, and writes what it prints to docs/src/data/examples/<example>.txt.

$root = \dirname(__DIR__, 2);
$examples = \dirname(__DIR__) . '/examples';
$output = \dirname(__DIR__) . '/src/data/examples';
$arguments = \array_slice($argv, 1);
$check = \in_array('--check', $arguments, true);
$only = array_values(array_diff($arguments, ['--check']))[0] ?? null;

/** @var array<string, string> $renders example name => output */
$renders = [];
foreach (glob("{$examples}/*.php") ?: [] as $file) {
    $name = basename($file, '.php');
    if (null !== $only && $name !== $only) {
        continue;
    }

    $process = proc_open(
        [\PHP_BINARY, '-d', "auto_prepend_file={$root}/vendor/autoload.php", '-d', 'display_errors=stderr', $file],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $examples,
    );
    if (false === $process) {
        fwrite(\STDERR, "Unable to run {$file}\n");
        exit(1);
    }
    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (0 !== proc_close($process) || '' !== $stderr) {
        fwrite(\STDERR, "docs/examples/{$name}.php failed:\n{$stderr}");
        exit(1);
    }

    $renders["{$name}.txt"] = rtrim($stdout) . "\n";
}
ksort($renders);

// Outputs without an example any more, only known when every example was run
$stale = [];
if (null === $only) {
    foreach (glob("{$output}/*.txt") ?: [] as $file) {
        if (!isset($renders[basename($file)])) {
            $stale[] = basename($file);
        }
    }
}

if ($check) {
    $errors = [];
    foreach ($renders as $name => $text) {
        $committed = @file_get_contents("{$output}/{$name}");
        if (false === $committed) {
            $errors[] = "missing   {$name}";
        } elseif ($committed !== $text) {
            $errors[] = "outdated  {$name}";
        }
    }
    foreach ($stale as $name) {
        $errors[] = "stale     {$name}";
    }

    if ([] !== $errors) {
        fwrite(\STDERR, implode("\n", $errors) . "\n\nThe documentation examples do not match the library: run php docs/scripts/render-examples.php and commit docs/src/data/examples/.\n");
        exit(1);
    }

    echo \count($renders) . " examples up to date\n";
    exit(0);
}

@mkdir($output, 0o755, true);
foreach ($renders as $name => $text) {
    file_put_contents("{$output}/{$name}", $text);
}
foreach ($stale as $name) {
    unlink("{$output}/{$name}");
}

echo implode("\n", array_keys($renders)) . "\n";
