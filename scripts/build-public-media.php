<?php

$root = dirname(__DIR__);

$assets = [
    [
        'source' => $root.'/tmp/original-media/profile.b64',
        'target' => $root.'/public/media/simone-buttice-profile-original.jpeg',
        'sha256' => '0bfcb24ddefd775edf036a4cf890220bc40a7c2340f81e6234b278b3ee6e3549',
    ],
    [
        'source' => $root.'/tmp/original-media/devlog-001-q100.b64',
        'target' => $root.'/public/media/devlog-001-hq.webp',
        'sha256' => 'a1b82396851671a36e625046d0942fe2289775da5ce266d104bc0e353acd3728',
    ],
];

foreach ($assets as $asset) {
    $encoded = file_get_contents($asset['source']);

    if ($encoded === false) {
        fwrite(STDERR, "Unable to read {$asset['source']}\n");
        exit(1);
    }

    $decoded = base64_decode(trim($encoded), true);

    if ($decoded === false || hash('sha256', $decoded) !== $asset['sha256']) {
        fwrite(STDERR, "Static media integrity check failed for {$asset['source']}\n");
        exit(1);
    }

    $directory = dirname($asset['target']);

    if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
        fwrite(STDERR, "Unable to create {$directory}\n");
        exit(1);
    }

    if (file_put_contents($asset['target'], $decoded) === false) {
        fwrite(STDERR, "Unable to write {$asset['target']}\n");
        exit(1);
    }
}

fwrite(STDOUT, "High-resolution public media restored.\n");
