<?php

use Illuminate\Support\Facades\File;

it('keeps visible copy free from emoji and decorative symbol glyphs', function () {
    $emojiPattern = '/[\x{2190}-\x{21FF}\x{2300}-\x{23FF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{1F000}-\x{1FAFF}\x{FE0F}]/u';
    $offenders = [];

    foreach ([lang_path(), resource_path('views'), resource_path('css')] as $directory) {
        foreach (File::allFiles($directory) as $file) {
            $contents = File::get($file->getPathname());

            if (! preg_match_all($emojiPattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[0] as [$glyph, $offset]) {
                $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
                $offenders[] = $file->getRelativePathname().':'.$line.' '.$glyph;
            }
        }
    }

    expect($offenders)->toBe([], "Visible emoji/symbol glyphs found:\n".implode("\n", $offenders));

    $entrypoint = File::get(resource_path('js/app.js'));
    $cleanup = File::get(resource_path('js/visible-symbols.js'));

    expect($entrypoint)->toContain("import './visible-symbols';")
        ->and($cleanup)->toContain('visibleEmojiPattern')
        ->and($cleanup)->toContain('MutationObserver');
});
