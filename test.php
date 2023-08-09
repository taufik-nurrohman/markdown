<?php

error_reporting(E_ALL | E_STRICT);

ini_set('display_errors', true);
ini_set('display_startup_errors', true);
ini_set('html_errors', 1);

define('D', DIRECTORY_SEPARATOR);
define('P', "\u{001A}");
define('PATH', __DIR__);

require __DIR__ . D . 'index.php';

$blocks = isset($_GET['blocks']);
$test = $_GET['test'] ?? 'p';
$view = isset($_GET['view']);

$files = glob(__DIR__ . D . 'test' . D . $test . D . '*.md', GLOB_NOSORT);

usort($files, static function ($a, $b) {
    $a = dirname($a) . D . basename($a, '.md');
    $b = dirname($b) . D . basename($b, '.md');
    return strnatcmp($a, $b);
});

$out = '<!DOCTYPE html>';
$out .= '<html dir="ltr">';
$out .= '<head>';
$out .= '<meta charset="utf-8">';
$out .= '<title>';
$out .= 'Test';
$out .= '</title>';
$out .= '</head>';
$out .= '<body>';

$out .= '<form method="get">';
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
$out .= '<input' . ($blocks ? ' checked' : "") . ' name="blocks" type="checkbox" value="1">';
$out .= ' ';
$out .= '<span>';
$out .= 'Show Blocks';
$out .= '</span>';
$out .= '</label>';
$out .= '<br>';
$out .= '<label>';
$out .= '<input' . ($view ? ' checked' : "") . ' name="view" type="checkbox" value="1">';
$out .= ' ';
$out .= '<span>';
$out .= 'Show HTML';
$out .= '</span>';
$out .= '</label>';
$out .= '</fieldset>';
$out .= '</form>';

foreach ($files as $v) {
    $content = "";
    $raw = file_get_contents($v);
    $start = microtime(true);
    if ($blocks) {
        [$rows, $lot] = x\markdown\rows($raw);
        $row = array_shift($rows);
        $content .= '<span style="background:' . (false === $row[0] ? 'rgba(0,0,0,.25)' : 'rgba(0,0,0,.125)') . ';display:block;">';
        $content .= htmlspecialchars(var_export($row, true));
        $content .= '</span>';
        while ($row = array_shift($rows)) {
            $content .= "\n";
            $content .= '<span style="background:' . (false === $row[0] ? 'rgba(0,0,0,.25)' : 'rgba(0,0,0,.125)') . ';display:block;">';
            $content .= htmlspecialchars(var_export($row, true));
            $content .= '</span>';
        }
        if ($lot = array_filter($lot)) {
            $content .= "\n\n";
            $content .= '$lot = ' . htmlspecialchars(var_export($lot, true)) . ';';
        }
    } else {
        $content .= htmlspecialchars(x\markdown\convert($raw));
    }
    $end = microtime(true);
    $out .= '<h1 id="' . ($n = basename(dirname($v)) . ':' . basename($v, '.md')) . '"><a aria-hidden="true" href="#' . $n . '">&sect;</a> ' . strtr($v, [PATH . D => '.' . D]) . '</h1>';
    $out .= '<div style="display:flex;gap:1em;">';
    $out .= '<pre style="background:#ccc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;white-space:pre-wrap;word-wrap:break-word;">';
    $out .= strtr(htmlspecialchars($raw), [' ' => '<span style="opacity:.5">·</span>']);
    $out .= '</pre>';
    $out .= '<pre style="background:#cfc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;white-space:pre-wrap;word-wrap:break-word;">';
    $out .= $content;
    $out .= '</pre>';
    if (!$blocks && $view) {
        $out .= '<div style="border:1px solid;box-shadow:inset 0 0 0 1em #eee;flex:1;padding:1em;">';
        $out .= htmlspecialchars_decode($content);
        $out .= '</div>';
    }
    $out .= '</div>';
    $time = round(($end - $start) * 1000, 2);
    $out .= '<p style="color:#' . ($time >= 1 ? '800' : '080') . ';">Parsed in ' . $time . ' ms.</p>';
}

$out .= '</body>';
$out .= '</html>';

echo $out;