@php
$html = file_get_contents(public_path('game_dev/index.html'));
$html = str_replace('</body>', '<script src="/game_dev/v2-runtime.js"></script></body>', $html);
echo $html;
@endphp
