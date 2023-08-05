<?php

define('D', DIRECTORY_SEPARATOR);
define('P', "\u{001A}");
define('PATH', __DIR__);

require __DIR__ . D . 'index.php';

$files = glob(__DIR__ . D . 'test' . D . ($test = $_GET['test'] ?? 'p') . D . '*.md', GLOB_NOSORT);

usort($files, static function ($a, $b) {
    $a = dirname($a) . D . basename($a, '.md');
    $b = dirname($b) . D . basename($b, '.md');
    return strcasecmp($a, $b);
});

$out = '<form method="get">';
$out .= '<fieldset>';
$out .= '<legend>';
$out .= 'Tests';
$out .= '</legend>';
$out .= '<button' . ('*' === $test ? ' disabled' : "") . ' name="test" type="submit" value="*">';
$out .= '*';
$out .= '</button>';
foreach (glob(__DIR__ . D . 'test' . D . '*', GLOB_ONLYDIR) as $v) {
    $out .= ' ';
    $out .= '<button' . ($test === ($n = basename($v)) ? ' disabled' : "") . ' name="test" type="submit" value="' . $n . '">';
    $out .= $n;
    $out .= '</button>';
}
$out .= '</fieldset>';
$out .= '<fieldset>';
$out .= '<legend>';
$out .= 'Options';
$out .= '</legend>';
$out .= '<label>';
$out .= '<input' . (isset($_GET['blocks']) ? ' checked' : "") . ' name="blocks" type="checkbox" value="1">';
$out .= ' ';
$out .= '<span>';
$out .= 'Show Blocks';
$out .= '</span>';
$out .= '</label>';
$out .= '</fieldset>';
$out .= '</form>';
$out .= '<hr>';

foreach ($files as $v) {
    $content = "";
    $raw = file_get_contents($v);
    $start = microtime(true);
    if (isset($_GET['blocks'])) {
        [$blocks, $lot] = x\markdown\rows($raw);
        $block = array_shift($blocks);
        $content .= '<span style="background:' . (false === $block[0] ? 'rgba(0,0,0,.25)' : 'rgba(0,0,0,.125)') . ';display:block;">';
        $content .= htmlspecialchars(var_export($block, true));
        $content .= '</span>';
        while ($block = array_shift($blocks)) {
            $content .= "\n";
            $content .= '<span style="background:' . (false === $block[0] ? 'rgba(0,0,0,.25)' : 'rgba(0,0,0,.125)') . ';display:block;">';
            $content .= htmlspecialchars(var_export($block, true));
            $content .= '</span>';
        }
        if ($lot = array_filter($lot)) {
            $content .= "\n\n";
            $content .= '$lot = ' . htmlspecialchars(var_export($lot, true)) . ';';
        }
    } else {
        $content .= htmlspecialchars(x\markdown\from($raw));
    }
    $end = microtime(true);
    $out .= '<h1 id="' . ($n = basename(dirname($v)) . ':' . basename($v, '.md')) . '"><a aria-hidden="true" href="#' . $n . '">&sect;</a> ' . strtr($v, [PATH . D => '.' . D]) . '</h1>';
    $out .= '<div style="display:flex;gap:1em;">';
    $out .= '<pre style="background:#ccc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;white-space:pre-wrap;word-wrap:break-word;">';
    $out .= htmlspecialchars($raw);
    $out .= '</pre>';
    $out .= '<pre style="background:#cfc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;white-space:pre-wrap;word-wrap:break-word;">';
    $out .= $content;
    $out .= '</pre>';
    if (!isset($_GET['blocks'])) {
        $out .= '<div style="border:1px solid;flex:1;">';
        $out .= htmlspecialchars_decode($content);
        $out .= '</div>';
    }
    $out .= '</div>';
    $time = round(($end - $start) * 1000, 2);
    $out .= '<p style="color:#' . ($time >= 1 ? '800' : '080') . ';">Parsed in ' . $time . ' ms.</p>';
}

echo $out;