<?php

use Illuminate\Support\Facades\File;

it('keeps public chrome free from literal emoji glyphs and loads the global cleanup', function () {
    $emojiPattern = '/[\x{2190}-\x{21FF}\x{2300}-\x{23FF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{1F000}-\x{1FAFF}\x{FE0F}]/u';
    $offenders = [];

    foreach ([resource_path('views/layouts/public.blade.php'), resource_path('views/public'), resource_path('css')] as $path) {
        $files = File::isDirectory($path) ? File::allFiles($path) : [new SplFileInfo($path)];

        foreach ($files as $file) {
            $contents = File::get($file->getPathname());

            if (! preg_match_all($emojiPattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[0] as [$glyph, $offset]) {
                $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
                $offenders[] = $file->getFilename().':'.$line.' '.$glyph;
            }
        }
    }

    expect($offenders)->toBe([], "Literal emoji/symbol glyphs found in public chrome:\n".implode("\n", $offenders));

    $entrypoint = File::get(resource_path('js/app.js'));
    $cleanup = File::get(resource_path('js/visible-symbols.js'));

    expect($entrypoint)->toContain("import './visible-symbols';")
        ->and($cleanup)->toContain('visibleEmojiPattern')
        ->and($cleanup)->toContain('MutationObserver')
        ->and($cleanup)->toContain('normalizeNode(document.body)');
});
