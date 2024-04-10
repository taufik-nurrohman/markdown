<?php

if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    exit;
}

error_reporting(E_ALL | E_STRICT);

ini_set('display_errors', true);
ini_set('display_startup_errors', true);
ini_set('html_errors', 1);

define('D', DIRECTORY_SEPARATOR);
define('P', "\u{001A}");
define('PATH', __DIR__);

require __DIR__ . D . '..' . D . 'to.php';

$test = basename($_GET['test'] ?? 'p');
$view = $_GET['view'] ?? 'source';

$files = glob(__DIR__ . D . 'to' . D . $test . D . '*.html', GLOB_NOSORT);
usort($files, static function ($a, $b) {
    $a = dirname($a) . D . basename($a, '.html');
    $b = dirname($b) . D . basename($b, '.html');
    return strnatcmp($a, $b);
});

$out = '<!DOCTYPE html>';
$out .= '<html dir="ltr">';
$out .= '<head>';
$out .= '<meta charset="utf-8">';
$out .= '<title>';
$out .= 'HTML to Markdown';
$out .= '</title>';
$out .= '<style>';
$out .= <<<CSS

body > div > div a {
  text-decoration: none;
}

body > div > div blockquote {
  border-left: 4px solid #eee;
  margin-left: 0;
  margin-right: 0;
  padding-left: 1.25em;
  padding-right: 1.25em;
}

body > div > div figure {
  text-align: center;
}

body > div > div figure img {
  display: block;
  margin: 0 auto;
}

body > div > div caption,
body > div > div figcaption {
  caption-side: bottom;
  font-style: italic;
  margin-top: 0.5em;
  text-align: center;
}

body > div > div dt {
  font-weight: bold;
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

body > div > div table + table {
  margin-top: 1em;
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

body > div > div :target {
  background: #ff0;
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
$out .= 'Navigation';
$out .= '</legend>';
$out .= '<a href="from.php">Markdown to HTML</a>';
$out .= '</fieldset>';

$out .= '<fieldset>';
$out .= '<legend>';
$out .= 'Filter';
$out .= '</legend>';
$out .= '<button' . ('*' === $test ? ' disabled' : "") . ' name="test" type="submit" value="*">';
$out .= '*';
$out .= '</button>';
foreach (glob(__DIR__ . D . 'to' . D . '*', GLOB_ONLYDIR) as $v) {
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
    $out .= '<div style="display:flex;gap:1em;margin:1em 0 0;">';
    $out .= '<pre style="background:#ccc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;tab-size:4;white-space:pre-wrap;word-wrap:break-word;">';
    $out .= strtr(htmlspecialchars($raw), [
        "\t" => '<span class="char-tab">' . "\t" . '</span>',
        ' ' => '<span class="char-space"> </span>'
    ]);
    $out .= '</pre>';
    $start = microtime(true);
    if ('raw' === $view) {
        $out .= '<pre style="background:#cfc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;tab-size:4;white-space:pre-wrap;word-wrap:break-word;">';
        // [$blocks, $lot] = x\markdown\from\raw($raw);
        // $out .= htmlspecialchars('$blocks = ' . var_export($blocks, true) . ';');
        // $out .= "\n\n";
        // $out .= htmlspecialchars('$data = ' . var_export($lot, true) . ';');
        // $out .= '</pre>';
    } else if ('result' === $view) {
        // $out .= '<div style="border:2px solid #000;color:#000;flex:1;padding:1em;">';
        // $out .= x\markdown\from($raw);
        // $out .= '</div>';
    } else if ('source' === $view) {
        $out .= '<pre style="background:#cfc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;tab-size:4;white-space:pre-wrap;word-wrap:break-word;">';
        $out .= strtr(htmlspecialchars(x\markdown\to($raw) ?? ""), [
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