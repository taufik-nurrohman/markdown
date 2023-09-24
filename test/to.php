<?php session_start();

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

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    $_SESSION['test'] = $_POST['value'] ?? "";
    header('location: ?test=' . urlencode(basename(strip_tags($_POST['test'] ?? 'p'))) . '&view=' . urlencode(strip_tags($_POST['view'] ?? 'source')));
    exit;
}

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
$out .= ' ';
$out .= '<button' . ('TEST' === $test ? ' disabled' : "") . ' name="test" type="submit" value="TEST">';
$out .= 'TEST';
$out .= '</button>';
$out .= '</fieldset>';

if ('TEST' !== $test) {
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
}

$out .= '</form>';

foreach ($files as $v) {
    $content = "";
    if ('TEST' !== $test) {
        $raw = file_get_contents($v);
        $out .= '<h1 id="' . ($n = basename(dirname($v)) . ':' . basename($v, '.md')) . '"><a aria-hidden="true" href="#' . $n . '">&sect;</a> ' . strtr($v, [PATH . D => '.' . D]) . '</h1>';
    } else {
        $raw = $_SESSION['test'] ?? "";
        if (strlen($raw) > 50000) {
            $raw = '*Maximum character length for this demo page must be less than or equal to 50000 characters.*';
        }
        unset($_SESSION['test']);
    }
    $out .= '<div style="display:flex;gap:1em;margin:1em 0 0;">';
    if ('TEST' !== $test) {
        $out .= '<pre style="background:#ccc;border:1px solid rgba(0,0,0,.25);color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;tab-size:4;white-space:pre-wrap;word-wrap:break-word;">';
        $out .= strtr(htmlspecialchars($raw), [
            "\t" => '<span class="char-tab">' . "\t" . '</span>',
            ' ' => '<span class="char-space"> </span>'
        ]);
        $out .= '</pre>';
    } else {
        $out .= '<form method="post" style="display:flex;flex:1;flex-direction:column;">';
        $out .= '<textarea autofocus maxlength="50000" name="value" placeholder="Markdown goes here&hellip;" style="background:#ffa;border:2px solid #000;color:#000;flex:1;font:normal normal 100%/1.25 monospace;margin:0;padding:.5em;min-height:28em;outline:0;resize:vertical;tab-size:4;white-space:pre-wrap;word-wrap:break-word;">';
        $out .= htmlspecialchars($raw);
        $out .= '</textarea>';
        $out .= '<p style="text-align:right;">';
        $out .= '<select name="view">';
        $out .= '<option' . ('raw' === $view ? ' selected' : "") . ' value="raw">Raw</option>';
        $out .= '<option' . ('source' === $view ? ' selected' : "") . ' value="source">Source</option>';
        $out .= '</select>';
        $out .= ' ';
        $out .= '<button type="submit">';
        $out .= 'Submit';
        $out .= '</button>';
        $out .= '</p>';
        $out .= '<input name="test" type="hidden" value="' . $test . '">';
        $out .= '</form>';
    }
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