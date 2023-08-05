<?php

define('D', DIRECTORY_SEPARATOR);
define('P', "\u{001A}");
define('PATH', __DIR__);

$files = glob(__DIR__ . D . 'test' . D . ($test = $_GET['test'] ?? 'p') . D . '*.md', GLOB_NOSORT);

usort($files, static function ($a, $b) {
    $a = dirname($a) . D . basename($a, '.md');
    $b = dirname($b) . D . basename($b, '.md');
    return strcasecmp($a, $b);
});

$out = '<p>';
$out .= '<b>Test:</b>';
$out .= ' ';
$out .= '*' === $test ? '<a aria-current="page">*</a>' : '<a href="?test=*">*</a>';
foreach (glob(__DIR__ . D . 'test' . D . '*', GLOB_ONLYDIR) as $v) {
    $out .= ' ';
    if ($test === ($n = basename($v))) {
        $out .= '<a aria-current="page">' . $n . '</a>';
    } else {
        $out .= '<a href="?test=' . urlencode($n) . '">' . $n . '</a>';
    }
}
$out .= '</p>';
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