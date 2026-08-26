<?php

if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    // exit;
}

error_reporting(E_ALL | E_STRICT);

ini_set('display_errors', true);
ini_set('display_startup_errors', true);
ini_set('html_errors', 1);

define('D', DIRECTORY_SEPARATOR);
define('P', "\u{001A}");
define('PATH', __DIR__);

require PATH . D . '..' . D . 'from.php';

$batch = basename($_GET['batch'] ?? '1');
$block = !!basename($_GET['block'] ?? '1');
$line = strtoupper(basename($_GET['line'] ?? 'LF'));
$test = basename($_GET['test'] ?? 'p');
$view = basename($_GET['view'] ?? 'source');

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
body > main {
  height: 100%;
}
body > main > form {
  display: flex;
  flex-wrap: wrap;
  gap: 1em;
  height: calc(100% + 0.5em);
  margin-top: -0.5em;
}
body > main > form > fieldset {
  display: flex;
  flex-direction: column;
  flex: 1;
}
body > main > form > fieldset > legend + p {
  flex: 1;
}
body > main > form > fieldset > legend + p + p {
  margin-top: 1em;
}
body > main > form > fieldset > pre + p {
  font-size: 75%;
  margin-top: 1em;
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
  padding: 1em;
}
hr {
  border-top: 1px solid #000;
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
  display: inline-block;
  height: 100%;
  outline: 0;
  padding: 0.25em 0.35em;
  resize: vertical;
  width: 100%;
}
legend {
  line-height: 1;
  padding: 0 0.25em;
}
pre {
  background: #ffc;
  border: 1px solid #000;
  flex: 1;
  height: 100%;
  min-width: 0;
  overflow: auto;
  padding: 0.25em 0.35em;
  tab-size: 4;
  width: 100%;
  word-wrap: break-word;
}
pre code {
  display: block;
}
.c {
  font-style: normal;
  font-weight: normal;
  position: relative;
}
.c::before {
  background: #dda;
  bottom: 0;
  color: #774;
  content: "";
  text-align: center;
}
.c-n::before {
  content: 'c n';
}
.c-r::before {
  content: 'c r';
}
.c-s::before {
  bottom: 0;
  content: 'c5';
  left: 0;
  position: absolute;
  right: 0;
  top: 0;
}
.c-t::before {
  bottom: 0;
  left: 0;
  overflow: hidden;
  position: absolute;
  right: 0;
  text-align: left;
  top: 0;
}
pre:focus .c-t::before,
pre:hover .c-t::before {
  content: '1234';
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
:root, body {
  height: 100%;
}
[role="group"] {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25em;
}
@media (max-width: 600px) {
  body > main > div {
    flex-direction: column;
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
$s .= '<textarea name="content">';
$s .= '</textarea>';
$s .= '</p>';
$s .= '<p role="group">';
$s .= '<select name="vs">';
$s .= '<option disabled selected>';
$s .= 'Compare with&hellip;';
$s .= '</option>';
foreach ([
    'league/commonmark' => 'CommonMark PHP',
    'michelf/php-markdown' => 'Markdown Extra',
    'erusev/parsedown-extra' => 'Parsedown Extra',
] as $k => $v) {
    $s .= '<option value="' . $k . '">';
    $s .= $v;
    $s .= '</option>';
}
$s .= '</select>';
$s .= ' ';
$s .= '<button type="submit">';
$s .= 'Parse';
$s .= '</button>';
$s .= '</p>';
$s .= '</fieldset>';
$s .= '<fieldset>';
$s .= '<legend>';
$s .= 'Output';
$s .= '</legend>';
$s .= '<pre>';
$s .= '<code>';
$s .= '</code>';
$s .= '</pre>';
$s .= '<p style="color:#090;">Parsed in 0.05 ms.</p>';
$s .= '</fieldset>';
$s .= '<fieldset>';
$s .= '<legend>';
$s .= 'Output by <a href="#">Markdown Extra</a>';
$s .= '</legend>';
$s .= '<pre>';
$s .= '<code>';
$s .= '</code>';
$s .= '</pre>';
$s .= '<p style="color:#090;">Parsed in 0.05 ms.</p>';
$s .= '</fieldset>';
$s .= '</form>';

$s .= '</main>';

$s .= '</body>';
$s .= '</html>';

echo $s;