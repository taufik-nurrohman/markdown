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

if ('LICENSE' === $test) {
    $files = [__DIR__ . D . 'LICENSE'];
} else if ('README' === $test) {
    $files = [__DIR__ . D . 'README.md'];
} else {
    $files = glob(__DIR__ . D . 'test' . D . $test . D . '*.md', GLOB_NOSORT);
    usort($files, static function ($a, $b) {
        $a = dirname($a) . D . basename($a, '.md');
        $b = dirname($b) . D . basename($b, '.md');
        return strnatcmp($a, $b);
    });
}

$out = '<!DOCTYPE html>';
$out .= '<html dir="ltr">';
$out .= '<head>';
$out .= '<meta charset="utf-8">';
$out .= '<title>';
$out .= 'Test';
$out .= '</title>';
$out .= '<style>';
$out .= <<<CSS
body > div > div blockquote {
  border-left: 4px solid #eee;
  margin-left: 0;
  margin-right: 0;
  padding-left: 1.25em;
  padding-right: 1.25em;
}

body > div > div img[src$='.gif'] {
  display: inline-block;
  vertical-align: middle;
}

body > div > div pre {
  background: #000;
  color: #fff;
  overflow: auto;
  padding: 1em 1.25em;
}

body > div > div table {
  border-collapse: collapse;
  table-layout: fixed;
  width: 100%;
}

body > div > div td,
body > div > div th {
  border: 1px solid;
  padding: 0.5em;
  text-align: left;
  vertical-align: top;
}

body > div > div > :first-child {
  margin-top: 0;
}

body > div > div > :last-child {
  margin-bottom: 0;
}

.char-space,
.char-tab {
  opacity: 0.5;
  position: relative;
}

.char-space::before {
  bottom: 0;
  content: '·';
  left: 0;
  position: absolute;
  right: 0;
  text-align: center;
  top: 0;
}

.char-tab::before {
  bottom: 0;
  content: '→';
  left: 0;
  position: absolute;
  right: 0;
  text-align: center;
  top: 0;
}

CSS;
$out .= '</style>';
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

$out .= ' ';
$out .= '<button' . ('LICENSE' === $test ? ' disabled' : "") . ' name="test" type="submit" value="LICENSE">';
$out .= 'LICENSE';
$out .= '</button>';
$out .= ' ';
$out .= '<button' . ('README' === $test ? ' disabled' : "") . ' name="test" type="submit" value="README">';
$out .= 'README';
$out .= '</button>';

$out .= '</fieldset>';
$out .= '<fieldset>';
$out .= '<legend>';
$out .= 'Preview';
$out .= '</legend>';
$out .= '<select name="view">';
$out .= '<option' . ('raw' === $view ? ' selected' : "") . ' value="raw">Raw</option>';
$out .= '<option' . ('result' === $view ? ' selected' : "") . ' value="result">HTML</option>';
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
    $out .= '<pre style="background:#ccc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;tab-size:4;white-space:pre-wrap;word-wrap:break-word;">';
    $out .= strtr(htmlspecialchars($raw), [
        "\t" => '<span class="char-tab">' . "\t" . '</span>',
        ' ' => '<span class="char-space"> </span>'
    ]);
    $out .= '</pre>';
    $start = microtime(true);
    if ('raw' === $view) {
        $out .= '<pre style="background:#cfc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;tab-size:4;white-space:pre-wrap;word-wrap:break-word;">';
        [$blocks, $lot] = x\markdown\raw($raw);
        $out .= htmlspecialchars('$blocks = ' . var_export($blocks, true) . ';');
        $out .= "\n\n";
        $out .= htmlspecialchars('$lot = ' . var_export($lot, true) . ';');
        $out .= '</pre>';
    } else if ('result' === $view) {
        $out .= '<div style="border:2px solid;flex:1;padding:1em;">';
        $out .= x\markdown\from($raw);
        $out .= '</div>';
    } else if ('source' === $view) {
        $out .= '<pre style="background:#cfc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;tab-size:4;white-space:pre-wrap;word-wrap:break-word;">';
        $out .= strtr(htmlspecialchars(x\markdown\from($raw) ?? ""), [
            "\t" => '<span class="char-tab">' . "\t" . '</span>',
            ' ' => '<span class="char-space"> </span>'
        ]);
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