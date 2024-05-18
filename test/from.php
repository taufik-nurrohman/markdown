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

require __DIR__ . D . '..' . D . 'from.php';

$test = basename($_GET['test'] ?? 'p');
$view = $_GET['view'] ?? 'source';

if ('LICENSE' === $test) {
    $files = [__DIR__ . D . '..' . D . 'LICENSE'];
} else if ('README' === $test) {
    $files = [__DIR__ . D . '..' . D . 'README.md'];
} else {
    $files = glob(__DIR__ . D . 'from' . D . $test . D . '*.md', GLOB_NOSORT);
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
$out .= 'Markdown to HTML';
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
$out .= '<a href="to.php">HTML to Markdown</a>';
$out .= '</fieldset>';

$out .= '<fieldset>';
$out .= '<legend>';
$out .= 'Filter';
$out .= '</legend>';
$out .= '<button' . ('*' === $test ? ' disabled' : "") . ' name="test" type="submit" value="*">';
$out .= '*';
$out .= '</button>';
foreach (glob(__DIR__ . D . 'from' . D . '*', GLOB_ONLYDIR) as $v) {
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

$error_count = 0;
foreach ($files as $v) {
    $error = false;
    $raw = file_get_contents($v);
    $out .= '<h1 id="' . ($n = basename(dirname($v)) . ':' . basename($v, '.md')) . '"><a aria-hidden="true" href="#' . $n . '">&sect;</a> ' . strtr($v, [PATH . D => '.' . D]) . '</h1>';
    $out .= '<div style="display:flex;gap:1em;margin:1em 0 0;">';
    $out .= '<pre style="background:#ccc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;tab-size:4;white-space:pre-wrap;word-wrap:break-word;">';
    $out .= strtr(htmlspecialchars($raw), [
        "\t" => '<span class="char-tab">' . "\t" . '</span>',
        ' ' => '<span class="char-space"> </span>'
    ]);
    $out .= '</pre>';
    if ('raw' === $view) {
        $out .= '<pre style="background:#cfc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;tab-size:4;white-space:pre-wrap;word-wrap:break-word;">';
        $start = microtime(true);
        [$blocks, $data] = x\markdown\from\raw($raw);
        $end = microtime(true);
        $out .= htmlspecialchars('$blocks = ' . preg_replace(['/=>\s*\n\s*/', '/\barray\s+\(/'], ['=> ', 'array('], var_export($blocks, true)) . ';');
        $out .= "\n\n";
        $out .= htmlspecialchars('$data = ' . preg_replace(['/=>\s*\n\s*/', '/\barray\s+\(/'], ['=> ', 'array('], var_export($data, true)) . ';');
        $out .= '</pre>';
    } else if ('result' === $view) {
        $out .= '<div style="border:2px solid #000;color:#000;flex:1;padding:1em;">';
        $start = microtime(true);
        $content = x\markdown\from($raw);
        $end = microtime(true);
        $out .= $content;
        $out .= '</div>';
    } else if ('source' === $view) {
        $out .= '<div style="flex:1;">';
        $a = $b = "";
        $a .= '<pre style="background:#cfc;border:1px solid rgba(0,0,0,.25);color:#000;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;tab-size:4;white-space:pre-wrap;word-wrap:break-word;">';
        $start = microtime(true);
        $content = x\markdown\from($raw) ?? "";
        $end = microtime(true);
        $a .= strtr(htmlspecialchars($content), [
            "\t" => '<span class="char-tab">' . "\t" . '</span>',
            ' ' => '<span class="char-space"> </span>'
        ]);
        $a .= '</pre>';
        if (is_file($f = dirname($v) . D . pathinfo($v, PATHINFO_FILENAME) . '.html')) {
            $test = strtr(file_get_contents($f), [
                "\r\n" => "\n",
                "\r" => "\n"
            ]);
            if ($error = $content !== $test) {
                $b .= '<pre style="background:#cff;border:1px solid rgba(0,0,0,.25);color:#000;font:normal normal 100%/1.25 monospace;margin:1em 0 0;padding:.5em;tab-size:4;white-space:pre-wrap;word-wrap:break-word;">';
                $b .= strtr(htmlspecialchars($test), [
                    "\t" => '<span class="char-tab">' . "\t" . '</span>',
                    ' ' => '<span class="char-space"> </span>'
                ]);
                $b .= '</pre>';
            }
        } else {
            // file_put_contents($f, $content);
            $error = false; // No test file to compare
        }
        $out .= ($error ? strtr($a, [':#cfc;' => ':#fcc;']) : $a) . $b . '</div>';
    }
    $out .= '</div>';
    $time = round(($end - $start) * 1000, 2);
    if ($error) {
        $error_count += 1;
    }
    $slow = $time >= 1;
    $out .= '<p style="color:#' . ($slow ? '800' : '080') . ';">Parsed in ' . $time . ' ms.</p>';
}

$out .= '</body>';
$out .= '</html>';

if ($error_count) {
    $out = strtr($out, ['</title>' => ' (' . $error_count . ')</title>']);
}

echo $out;