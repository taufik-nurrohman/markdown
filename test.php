<?php

error_reporting(E_ALL | E_STRICT);

ini_set('display_errors', true);
ini_set('display_startup_errors', true);
ini_set('html_errors', 1);

define('D', DIRECTORY_SEPARATOR);
define('P', "\u{001A}");
define('PATH', __DIR__);

require __DIR__ . D . 'index.php';

$test = $_GET['test'] ?? 'p';
$view = $_GET['view'] ?? 'source';

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
    $out .= '<button' . ($test === ($n = basename($v)) ? ' disabled' : "") . ' name="test" type="submit" value="' . htmlspecialchars($n) . '">';
    $out .= htmlspecialchars($n);
    $out .= '</button>';
}
$out .= '</fieldset>';
$out .= '<fieldset>';
$out .= '<legend>';
$out .= 'Preview';
$out .= '</legend>';
$out .= '<select name="view">';
$out .= '<option' . ('result' === $view ? ' selected' : "") . ' value="result">HTML</option>';
$out .= '<option' . ('raw' === $view ? ' selected' : "") . ' value="raw">Raw</option>';
$out .= '<option' . ('source' === $view ? ' selected' : "") . ' value="source">Source</option>';
$out .= '</select>';
$out .= ' ';
$out .= '<button name="test" type="submit" value="' . $test . '">';
$out .= 'Update';
$out .= '</button>';
$out .= '</fieldset>';
$out .= '</form>';

foreach ($files as $v) {
    $content = "";
    $raw = file_get_contents($v);
    $out .= '<h1 id="' . ($n = basename(dirname($v)) . ':' . basename($v, '.md')) . '"><a aria-hidden="true" href="#' . $n . '">&sect;</a> ' . strtr($v, [PATH . D => '.' . D]) . '</h1>';
    $out .= '<div style="display:flex;gap:1em;">';
    $out .= '<pre style="background:#ccc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;white-space:pre-wrap;word-wrap:break-word;">';
    $out .= strtr(htmlspecialchars($raw), [' ' => '<span style="opacity:.5">·</span>']);
    $out .= '</pre>';
    $start = microtime(true);
    if ('raw' === $view) {
        $out .= '<pre style="background:#cfc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;white-space:pre-wrap;word-wrap:break-word;">';
        $rows = x\markdown\rows($raw);
        $out .= htmlspecialchars('$blocks = ' . var_export($rows[0], true) . ';');
        $out .= "\n\n";
        $out .= htmlspecialchars('$lot = ' . var_export($rows[1], true) . ';');
        $out .= '</pre>';
    } else if ('result' === $view) {
        $out .= '<div style="border:1px solid;box-shadow:inset 0 0 0 1em #eee;flex:1;padding:1em;">';
        $out .= x\markdown\convert($raw);
        $out .= '</div>';
    } else if ('source' === $view) {
        $out .= '<pre style="background:#cfc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;white-space:pre-wrap;word-wrap:break-word;">';
        $out .= htmlspecialchars(x\markdown\convert($raw));
        $out .= '</pre>';
    }
    $end = microtime(true);
    $out .= '</div>';
    $time = round(($end - $start) * 1000, 2);
    $out .= '<p style="color:#' . ($time >= 1 ? '800' : '080') . ';">Parsed in ' . $time . ' ms.</p>';
}

$out .= '</body>';
$out .= '</html>';

echo $out;