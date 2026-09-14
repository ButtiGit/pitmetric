<?php

use Illuminate\Support\Facades\File;

it('keeps user-facing sources free of emoji glyphs', function () {
    $directories = [
        resource_path('views'),
        resource_path('css'),
        resource_path('js'),
        lang_path(),
        app_path('Notifications'),
    ];

    $emojiPattern = '/[\x{2190}-\x{21FF}\x{2300}-\x{23FF}\x{2600}-\x{27BF}\x{1F000}-\x{1FAFF}\x{FE0F}]/u';
    $violations = [];

    foreach ($directories as $directory) {
        if (! File::isDirectory($directory)) {
            continue;
        }

        foreach (File::allFiles($directory) as $file) {
            $contents = File::get($file->getPathname());

            if (! preg_match_all($emojiPattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[0] as [$symbol, $offset]) {
                $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
                $violations[] = sprintf(
                    '%s:%d contains %s',
                    str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()),
                    $line,
                    json_encode($symbol, JSON_UNESCAPED_UNICODE),
                );
            }
        }
    }

    expect(implode("\n", $violations))->toBe('');
});
