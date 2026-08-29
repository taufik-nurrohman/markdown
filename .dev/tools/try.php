<?php session_start();

if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    // exit;
}

error_reporting(E_ALL | E_STRICT);

ini_set('display_errors', true);
ini_set('display_startup_errors', true);
ini_set('html_errors', 1);

define('D', DIRECTORY_SEPARATOR);
define('PATH', __DIR__);

require PATH . D . '..' . D . 'from.php';
require PATH . D . 'try' . D . 'vendor' . D . 'autoload.php';

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    $content = $_POST['content'] ?? "";
    $token = $_POST['token'] ?? 0;
    if ($token !== ($_SESSION['token'] ?? 1)) {
        $_SESSION['alert'] = 'Invalid token.';
        header('location: try.php');
        exit;
    }
    $_SESSION['r'][0] = $content;
    $_SESSION['t'][0] = 0;
    $_SESSION['r'][1] = require PATH . D . 'try' . D . 'w' . D . 'taufik-nurrohman' . D . 'markdown.php';
    $_SESSION['t'][1] = $t;
    if ("" !== ($w = strip_tags($_POST['w'] ?? ""))) {
        $w = trim(strtr($w, ["\\" => D, '/' => D]), D);
        // Prevent directory traversal attack
        while (false !== strpos($w, '..' . D)) {
            $w = strtr($w, ['..' . D => ""]);
        }
        $_SESSION['r'][2] = require PATH . D . 'try' . D . 'w' . D . $w . '.php';
        $_SESSION['t'][2] = $t;
        $_SESSION['w'] = [$w, $with ?? $w];
    }
    $_SESSION['v'] = !empty($_POST['v']);
    header('location: try.php');
    exit;
}

function view_result(string $text) {
    $b1 = 'blockquote, dd, div, dt, figure, h1, h2, h3, h4, h5, h6, hgroup, hr, p, pre, table';
    $b2 = $b1 . ', dl, ol, ul';
    $s = '<!DOCTYPE html>';
    $s .= '<html dir="ltr">';
    $s .= '<head>';
    $s .= '<meta content="width=device-width" name="viewport">';
    $s .= '<title>Test</title>';
    $s .= '<style>';
    $s .= <<<CSS
* {
  background: 0 0;
  border: 0;
  box-sizing: border-box;
  color: inherit;
  font: inherit;
  margin: 0;
  padding: 0;
  text-decoration: none;
}
a {
  color: #00f;
}
a:focus {
  color: #f00;
}
abbr {
  border-bottom: 1px dotted #000;
  cursor: help;
}
b, legend, strong, th {
  font-weight: bold;
}
blockquote {
  border-left: 4px solid #eee;
  color: #666;
  padding: 0 0 0 0.75em;
}
code, textarea {
  font: normal normal 0.95em/1.25 'Courier New', monospace;
}
code {
  background: #eee;
  display: inline-block;
  padding: 0 0.15em;
  vertical-align: middle;
  white-space: pre;
}
del {
  text-decoration: line-through;
}
details:open > summary {
  margin-bottom: 1rem;
}
dl, ol, ul {
  margin-left: 2em;
}
em, i {
  font-style: italic;
}
fieldset {
  border: 1px solid #000;
  padding: 1em;
}
caption,
figcaption {
  caption-side: bottom;
  color: #666;
  font-size: 0.85em;
  margin-top: 0.5em;
}
figure {
  text-align: center;
}
figure img {
  display: block;
  margin: 0 auto;
}
/* <https://www.modularscale.com/?16&px&1.25> */
dt, h1, h2, h3, h4, h5, h6 {
  line-height: 1.25;
}
h1 {
  font-size: 3.815em;
}
h2 {
  font-size: 3.052em;
}
h3 {
  font-size: 2.441em;
}
h4 {
  font-size: 1.953em;
}
h5 {
  font-size: 1.563em;
}
dt, h6 {
  font-size: 1.25em;
}
hr {
  border-top: 1px solid #000;
}
ol {
  list-style-type: decimal;
}
ol[type='A' s] {
  list-style-type: upper-alpha;
}
ol[type='I' s] {
  list-style-type: upper-roman;
}
ol[type='a' s] {
  list-style-type: lower-alpha;
}
ol[type='i' s] {
  list-style-type: lower-roman;
}
ul {
  list-style-type: disc;
}
textarea {
  border: 1px solid #000;
  padding: 0.25em 0.5em;
}
:root {
  background: #fff;
  color: #000;
  font: normal normal 13px/1.5 Verdana, sans-serif;
  padding: 1em;
}
:target {
  background: #0f0;
}
:where({$b2}) + :where({$b2}) {
  margin-top: 1rem;
}
:where(dt, h1, h2, h3, h4, h5, h6), li::marker {
  color: #900;
}
:where(small, sub, sup) {
  font-size: 0.75em;
}
li:where(:not(:first-child)) > :where({$b1}):where(:first-child) {
  margin-top: 1rem;
}
p img {
  display: inline-block;
  position: relative;
  top: 0.25rem;
}
pre {
  overflow: auto;
  tab-size: 4;
}
pre code {
  background: #000;
  color: #fff;
  display: block;
  overflow: auto;
  padding: 0.5em;
}
table {
  border-collapse: collapse;
  table-layout: fixed;
  width: 100%;
}
td, th {
  border: 1px solid #000;
  padding: 0.5em 0.75em;
  text-align: left;
  vertical-align: top;
}
[role='doc-endnotes'] {
  font-size: 0.85em;
}
CSS;
    $s .= '</style>';
    $s .= '</head>';
    $s .= '<body>';
    $s .= $text;
    $s .= '<script>';
    $s .= <<<JS
const links = document.querySelectorAll('[href^="#"]');
links && links.length && links.forEach(link => {
    link.addEventListener('click', function (e) {
        let target = document.getElementById(this.hash.slice(1));
        if (target) {
            location.hash = this.hash;
            target.scrollIntoView();
        }
        e.preventDefault();
    });
});
JS;
    $s .= '</script>';
    $s .= '</body>';
    $s .= '</html>';
    return $s;
}

function view_source(string $text) {
    $i = 0;
    $limit = strlen($text);
    $s = "";
    while ($i < $limit) {
        $c = $text[$i];
        if ('&' === $c && false !== ($n = strpos($text, ';', $i + 2))) {
            $s .= '<span style="color:#d00;font-weight:bold;">';
            $s .= htmlspecialchars(substr($text, $i, $n += 1 - $i));
            $s .= '</span>';
            $i += $n;
            continue;
        }
        if ('<' === $c) {
            if (0 === substr_compare($text, '<!--', $i, 4) && false !== ($n = strpos($text, '-->', $i))) {
                $s .= '<span style="color:#f80;">';
                $s .= htmlspecialchars(substr($text, $i, $n += 3 - $i));
                $s .= '</span>';
                $i += $n;
                continue;
            }
            if (0 === substr_compare($text, '<![CDATA[', $i, 9) && false !== ($n = strpos($text, ']]>', $i + 9))) {
                $s .= '<span style="color:#f80;">';
                $s .= htmlspecialchars(substr($text, $i, $n += 3 - $i));
                $s .= '</span>';
                $i += $n;
                continue;
            }
            if (0 === substr_compare($text, '<?', $i, 2) && false !== ($n = strpos($text, '?>', $i + 2))) {
                $s .= '<span style="color:#f80;">';
                $s .= htmlspecialchars(substr($text, $i, $n += 2 - $i));
                $s .= '</span>';
                $i += $n;
                continue;
            }
            if (0 === substr_compare($text, '<!', $i, 2) && false !== ($n = strpos($text, '>', $i + 2))) {
                $s .= '<span style="color:#f80;">';
                $s .= htmlspecialchars(substr($text, $i, $n += 1 - $i));
                $s .= '</span>';
                $i += $n;
                continue;
            }
            $q = "";
            for ($n = $i + 1; $n < $limit; ++$n) {
                $c = $text[$n];
                if ($q) {
                    if ($c === $q) {
                        $q = "";
                    }
                    continue;
                }
                if ($c === '"' || $c === "'") {
                    $q = $c;
                    continue;
                }
                if ('>' === $c) {
                    break;
                }
            }
            if ("" === $q && $n < $limit) {
                $s .= '<span style="color:#00b;font-weight:bold;">';
                $part = substr($text, $i, $n += 1 - $i);
                $s .= '&lt;';
                $s .= substr(view_source("\x1a" . substr($part, 1, -1) . "\x1a"), 1, -1);
                $s .= '&gt;';
                $s .= '</span>';
                $i += $n;
                continue;
            }
            $s .= '&lt;';
            ++$i;
            continue;
        }
        if ("\\" === $c) {
            $s .= '<span style="color:#d00;font-weight:bold;">' . $c . '</span>';
            $i += 1;
            continue;
        }
        if ("\t" === $c) {
            $s .= '<span class="c c-t">' . $c . '</span>';
            $i += 1;
            continue;
        }
        $s .= substr($text, $i, $n = strcspn($text, "&<\\\t", $i));
        $i += $n;
    }
    return $s;
}

$s  = '<!DOCTYPE html>';
$s .= '<html dir="ltr">';
$s .= '<head>';
$s .= '<meta content="width=device-width" name="viewport">';
$s .= '<meta charset="utf-8">';
$s .= '<title>';
$s .= 'Try';
$s .= '</title>';
$s .= '<style>';
$s .= <<<CSS
* {
  background: 0 0;
  border: 0;
  box-sizing: border-box;
  color: inherit;
  font: inherit;
  margin: 0;
  padding: 0;
  text-decoration: none;
}
a {
  color: #00f;
}
a:focus {
  color: #f00;
}
b, h1, h2, h3, h4, h5, h6, legend, strong, th {
  font-weight: bold;
}
button, select {
  appearance: none;
  background: #eee none no-repeat 50% 50%;
  border: 1px solid #000;
  cursor: pointer;
  display: inline-block;
  height: calc(1.5em + (0.125em * 2) + 2px);
  line-height: 1.5em;
  padding: 0.125em 0.5em;
}
code, textarea {
  font: normal normal 12px/1.25 'Courier New', monospace;
}
em, i {
  font-style: italic;
}
fieldset {
  border: 1px solid #000;
  min-width: 0;
  padding: 1em;
}
fieldset > p:not([role="group"]):last-of-type {
  font-size: 75%;
  margin-top: 1em;
}
fieldset > p:not(:first-of-type) {
  margin-top: 1em;
}
fieldset + fieldset {
  margin-top: 1em;
}
hr {
  border-top: 1px solid #000;
}
iframe {
  display: block;
  min-height: 50vh;
  outline: 0;
  width: 100%;
}
select {
  background-image: url('data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIwIDAgMjAgMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTQgN0wxMCAxM0wxNiA3IiBmaWxsPSJub25lIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1saW5lY2FwPSJidXR0IiBzdHJva2UtbGluZWpvaW49Im1pdGVyIiBzdHJva2Utd2lkdGg9IjIiLz48L3N2Zz4=');
  background-position: right 0.4em center;
  background-size: 1rem;
  padding-right: 1.75em;
}
textarea {
  background: #ffc;
  border: 1px solid #000;
  display: block;
  min-height: 50vh;
  outline: 0;
  padding: 0.25em 0.45em;
  resize: vertical;
  width: 100%;
}
legend {
  line-height: 0;
  padding: 0 0.25em;
  white-space: nowrap;
}
pre {
  background: #ffc;
  border: 1px solid #000;
  overflow: auto;
  padding: 0.25em 0.45em;
  tab-size: 4;
  white-space: pre-wrap;
  width: 100%;
  word-wrap: break-word;
}
pre code {
  display: block;
}
:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}
:root {
  background: #fff;
  color: #000;
  font: normal normal 13px/1.5 Verdana, sans-serif;
  padding: 1em;
}
[role='alert'] {
  color: #f00;
}
[role="group"] {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 0.25em;
}
@media (min-width: 1200px) {
  :root,
  body,
  body > main,
  body > main > form,
  body > main > form > fieldset {
    height: 100%;
  }
  body > main > form {
    display: flex;
    gap: 1em;
  }
  body > main > form > fieldset {
    display: flex;
    flex-direction: column;
    flex: 1;
    margin-top: 0;
  }
  body > main > form > fieldset > legend + p,
  body > main > form > fieldset > pre {
    flex: 1;
  }
  body > main > form > fieldset > legend + p iframe,
  body > main > form > fieldset > legend + p textarea {
    height: 100%;
    min-height: 100%;
  }
}
CSS;
$s .= '</style>';
$s .= '</head>';
$s .= '<body spellcheck="false">';

$s .= '<main>';

$s .= '<form action="#top" method="post">';
$s .= '<fieldset>';
$s .= '<legend>';
$s .= 'Input';
$s .= '</legend>';
$s .= '<p>';
$s .= '<textarea name="content" placeholder="Markdown goes here&hellip;">';
$s .= htmlspecialchars($_SESSION['r'][0] ?? file_get_contents(PATH . D . '..' . D . 'README.md'));
$s .= '</textarea>';
$s .= '</p>';
$w = $_SESSION['w'][0] ?? "";
$s .= '<p role="group">';
$s .= '<select name="w">';
$s .= '<option disabled' . ("" === $w ? ' selected' : "") . '>';
$s .= 'Compare with&hellip;';
$s .= '</option>';
foreach ([
    'cebe/markdown' => '“cebe” Markdown Extra',
    'league/commonmark' => 'CommonMark PHP',
    'michelf/php-markdown' => 'Markdown Extra',
    'erusev/parsedown-extra' => 'Parsedown Extra',
] as $k => $v) {
    $s .= '<option' . ($k === strtr($w, [D => '/']) ? ' selected' : "") . ' value="' . $k . '">';
    $s .= $v;
    $s .= '</option>';
}
$s .= '</select>';
$s .= ' ';
$s .= '<button type="submit">';
$s .= 'Parse';
$s .= '</button>';
$s .= ' ';
$s .= '<label role="group" style="margin-left: auto;">';
$s .= '<input' . (!empty($_SESSION['v']) ? ' checked' : "") . ' name="v" type="checkbox">';
$s .= ' ';
$s .= '<span>';
$s .= 'Render HTML';
$s .= '</span>';
$s .= '</p>';

if ($alert = $_SESSION['alert'] ?? "") {
    $s .= '<p role="alert">';
    $s .= $alert;
    $s .= '</p>';
}

$s .= '</fieldset>';
if ("" !== trim($_SESSION['r'][1] ?? "")) {
    $s .= '<fieldset>';
    $s .= '<legend>';
    $s .= 'Output';
    $s .= '</legend>';
    if (!empty($_SESSION['v'])) {
        $s .= '<p>';
        $s .= '<iframe sandbox srcdoc="' . htmlspecialchars(view_result($_SESSION['r'][1] ?? "")) . '" tabindex="0"></iframe>';
        $s .= '</p>';
    } else {
        $s .= '<pre>';
        $s .= '<code>';
        $s .= view_source($_SESSION['r'][1] ?? "");
        $s .= '</code>';
        $s .= '</pre>';
    }
    if ($time = $_SESSION['t'][1] ?? 0) {
        $s .= '<p style="color:#' . ($time > ($_SESSION['t'][2] ?? PHP_INT_MAX) ? '900' : '090') . ';">Parsed in ' . round($time, 2) . ' ms.</p>';
    }
    $s .= '</fieldset>';
    if ("" !== trim($_SESSION['r'][2] ?? "")) {
        $with = $_SESSION['w'][1] ?? $w;
        $s .= '<fieldset>';
        $s .= '<legend>';
        $s .= 'Output by <a href="https://packagist.org/packages/' . strtr($w, [D => '/']) . '" rel="nofollow" target="_blank">' . $with . '</a>';
        $s .= '</legend>';
        if (!empty($_SESSION['v'])) {
            $s .= '<p>';
            $s .= '<iframe sandbox srcdoc="' . htmlspecialchars(view_result($_SESSION['r'][2] ?? "")) . '" tabindex="0"></iframe>';
            $s .= '</p>';
        } else {
            $s .= '<pre>';
            $s .= '<code>';
            $s .= view_source($_SESSION['r'][2] ?? "");
            $s .= '</code>';
            $s .= '</pre>';
        }
        if ($time = $_SESSION['t'][2] ?? 0) {
            $s .= '<p style="color:#' . ($time > ($_SESSION['t'][1] ?? 0) ? '900' : '090') . ';">Parsed in ' . round($time, 2) . ' ms.</p>';
        }
        $s .= '</fieldset>';
    }
}
$s .= '<input name="token" type="hidden" value="' . ($_SESSION['token'] = bin2hex(random_bytes(16))) . '">';
$s .= '</form>';

$s .= '</main>';

$s .= '</body>';
$s .= '</html>';

unset($_SESSION['alert'], $_SESSION['r'], $_SESSION['t'], $_SESSION['w']);

echo $s;