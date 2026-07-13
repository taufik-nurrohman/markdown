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
$test = basename($_GET['test'] ?? 'p');
$view = basename($_GET['view'] ?? 'source');

// <https://github.com/mecha-cms/mecha/blob/v3.2.0/engine/f.php#L20-L35>
if (!function_exists('array_is_list')) {
    // PHP < 8.1
    function array_is_list(array $array): bool {
        if (!$array) {
            return true;
        }
        $key = -1;
        foreach ($array as $k => $v) {
            if ($k !== ++$key) {
                return false;
            }
        }
        return true;
    }
}

function view_raw(string $text) {
    $r = "";
    foreach (token_get_all($text) as $t) {
        if (is_array($t)) {
            $color = '00b';
            switch ($t[0]) {
                case T_OPEN_TAG:
                    // $color = 'f70';
                    $color = '00b';
                    break;
                case T_INLINE_HTML:
                    $color = '000';
                    break;
                case T_DOUBLE_ARROW:
                case T_END_HEREDOC:
                case T_RETURN:
                case T_START_HEREDOC:
                    $color = '070';
                    break;
                case T_CONSTANT_ENCAPSED_STRING:
                case T_ENCAPSED_AND_WHITESPACE:
                    $color = 'd00';
                    break;
            }
            $r .= '<span style="color:#' . $color . ';">' . strtr(htmlspecialchars($t[1]), [
                "\t" => "<span class=\"c c-t\">\t</span>",
                "\x1e" => "<span class=\"c\">\x1e</span>",
                "\x2" => "<span class=\"c\">\x2</span>",
                "\x3" => "<span class=\"c\">\x3</span>"
            ]) . '</span>';
            continue;
        }
        $r .= '<span style="color:#070;">' . strtr(htmlspecialchars($t), [
            "\t" => "<span class=\"c c-t\">\t</span>",
            "\x1e" => "<span class=\"c\">\x1e</span>",
            "\x2" => "<span class=\"c\">\x2</span>",
            "\x3" => "<span class=\"c\">\x3</span>"
        ]) . '</span>';
    }
    return $r;
}

function view_result(string $text) {
    // TODO
    return $text;
}

function view_source(string $text) {
    return htmlspecialchars($text);
}

// <https://github.com/mecha-cms/mecha/blob/v3.2.0/engine/f.php#L1606-L1671>
function export($value, $dent = "", $r = "\n", $key_as_string = false, $is_object = null) {
    if (is_object($value)) {
        if ($value instanceof stdClass) {
            return '(object) ' . export((array) $value, $dent, $r, true, true);
        }
        return '(object) []';
    }
    if (is_array($value)) {
        $values = [];
        if (!$is_object && array_is_list($value)) {
            foreach ($value as $k => $v) {
                $values[] = export($v, $dent . '  ', $r, $key_as_string);
            }
        } else {
            foreach ($value as $k => $v) {
                $k = export($k);
                if ($key_as_string && is_numeric($k)) {
                    $k = "'" . $k . "'";
                }
                $values[] = $k . ' => ' . export($v, $dent . '  ', $r, $key_as_string);
            }
        }
        if (!$values) {
            return '[]';
        }
        return '[' . $r . '  ' . $dent . implode(',' . $r . $dent . '  ', $values) . $r . $dent . ']';
    }
    $value = var_export($value, true);
    if ("''" === $value) {
        return '""';
    }
    if ('NULL' === $value) {
        return 'null';
    }
    if (false !== strpos($value, $r) || false !== strpos(substr($value, 1, -1), "'")) {
        $value = '<<<TEXT' . $r . implode($r, array_map(function ($v) use ($dent) {
            if ("" !== $v) {
                return $dent . strtr($v, ["\\'" => "'"]);
            }
            return "";
        }, explode($r, substr($value, 1, -1)))) . $r . $dent . 'TEXT';
    }
    return $value;
}

if ('LICENSE' === $test) {
    $files = [PATH . D . '..' . D . 'LICENSE'];
} else if ('README' === $test) {
    $files = [PATH . D . '..' . D . 'README.md'];
} else {
    $files = glob(PATH . D . 'from' . D . $test . D . $batch . D . '*.md', GLOB_NOSORT);
}

usort($files, function ($a, $b) {
    $a = strtr(substr($a, 0, -3), ['/' => '-', "\\" => '-']);
    $b = strtr(substr($b, 0, -3), ['/' => '-', "\\" => '-']);
    return strnatcmp($a, $b);
});

$r  = '<!DOCTYPE html>';
$r .= '<html dir="ltr">';
$r .= '<head>';
$r .= '<meta charset="utf-8">';
$r .= '<title>';
$r .= 'Markdown to HTML';
$r .= '</title>';
$r .= '<style>';
$r .= <<<CSS
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
body, html {
  scroll-behavior: smooth;
}
body > form {
  margin-bottom: 0.5em;
}
body > form:first-child {
  margin-top: -0.5em;
}
body > form > fieldset > p + p {
  margin-top: 1em;
}
body > main > div {
  display: flex;
  gap: 1em;
}
body > main > div > article,
body > main > div > pre {
  border: 1px solid #000;
  flex: 1;
  padding: 0.5em 0.75em;
}
body > main > div > article {
  border-width: 2px;
}
body > main > div > pre {
  background: #ffc;
  padding: 0.25em 0.35em;
}
body > main > div + div {
  margin-top: 1em;
}
body > main > h1 {
  background: #000;
  color: #fff;
  font-size: 100%;
  margin: 1em 0;
  padding: 0.5em 0.75em;
  scroll-margin: 1em;
}
body > main > h1:target {
  background: #c00;
}
body > main > h1 > a {
  color: #0f0;
}
b, h1, h2, h3, h4, h5, h6, legend, strong {
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
code {
  font: normal normal 12px/1.25 'Courier New', monospace;
}
select {
  background-image: url('data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIwIDAgMjAgMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTQgN0wxMCAxM0wxNiA3IiBmaWxsPSJub25lIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1saW5lY2FwPSJidXR0IiBzdHJva2UtbGluZWpvaW49Im1pdGVyIiBzdHJva2Utd2lkdGg9IjIiLz48L3N2Zz4=');
  background-position: right 0.4em center;
  background-size: 1rem;
  padding-right: 1.75em;
}
fieldset {
  border: 1px solid #000;
  padding: 1em;
}
/* <https://www.modularscale.com/?16&px&1.25> */
h1,
h2,
h3,
h4,
h5,
h6,
dt {
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
h6,
dt {
  font-size: 1.25em;
}
legend {
  line-height: 1;
  padding: 0 0.25em;
}
pre {
  overflow: auto;
  tab-size: 4;
}
pre code {
  display: block;
}
.c {
  position: relative;
}
.c::after {
  background: rgb(0 0 0 / 0.15);
  bottom: 0;
  color: rgb(0 0 0 / 0.5);
  content: "";
  left: 0;
  position: absolute;
  right: 0;
  text-align: center;
  top: 0;
}
.c-n::after {
  content: '\\5c n';
}
.c-r::after {
  content: '\\5c r';
}
.c-s::after {
  content: '⋅';
}
.c-t::after {
  content: '\\5c t';
  text-align: left;
}
.c-n::before {
  content: '  ';
}
.c-r::before {
  content: '  ';
}
.c-t::before {
  content: ' ';
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
[role="group"] {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25em;
}
CSS;
$r .= '</style>';
$r .= '</head>';
$r .= '<body>';

$r .= '<form action="#top" method="get">';
$r .= '<fieldset>';
$r .= '<legend>';
$r .= 'Navigation';
$r .= '</legend>';
$r .= '<p role="group">';
foreach (glob(PATH . D . 'from' . D . '*', GLOB_ONLYDIR) as $v) {
    $r .= '<button' . ($test === ($v = basename($v)) ? ' disabled' : "") . ' name="test" type="submit" value="' . htmlspecialchars($v) . '">' . htmlspecialchars($v) . '</button> ';
}
$r  = substr($r, 0, -1) . '</p>';
$r .= '<p role="group">';
$r .= '<select name="view">';
foreach (['raw', 'result', 'source'] as $v) {
    $r .= '<option' . ($v === $view ? ' selected' : "") . ' value="' . htmlspecialchars($v) . '">';
    $r .= ucfirst($v);
    $r .= '</option>';
}
$r .= '</select>';
$r .= ' ';
$r .= '<button name="test" type="submit" value="' . htmlspecialchars($test) . '">';
$r .= 'View';
$r .= '</button>';
$r .= '</p>';
$r .= '</fieldset>';
$r .= '<input name="batch" type="hidden" value="1">';
$r .= '</form>';

$r .= '<form action="#top" method="get">';
$r .= '<fieldset>';
$r .= '<legend>';
$r .= 'Batch';
$r .= '</legend>';
$r .= '<p role="group">';
$r .= '<button' . ($batch === '*' ? ' disabled' : "") . ' name="batch" type="submit" value="*">*</button>';
$folders = glob(PATH . D . 'from' . D . $test . D . '*', GLOB_ONLYDIR);
usort($folders, 'strnatcmp');
foreach ($folders as $v) {
    $r .= ' <button' . ($batch === ($v = basename($v)) ? ' disabled' : "") . ' name="batch" type="submit" value="' . htmlspecialchars($v) . '">' . htmlspecialchars($v) . '</button>';
}
$r .= '</p>';
$r .= '</fieldset>';
$r .= '<input name="test" type="hidden" value="' . htmlspecialchars($test) . '">';
$r .= '<input name="view" type="hidden" value="' . htmlspecialchars($view) . '">';
$r .= '</form>';

$r .= '<main>';

$current = "";
$error_count = 0;

foreach ($files as $file) {
    $n = basename($v = ".\\" . substr(strtr(dirname($file), ['/' => "\\"]), strlen(PATH . D . 'from' . D)));
    if ($v !== $current || "" === $current) {
        $r .= '<h1 id="to:' . htmlspecialchars($n) . '"><a aria-hidden="true" href="#to:' . $n . '">#</a> ' . $v . "\\*" . '</h1>';
        $current = $v;
    }
    $raws = file_get_contents($file);
    // $raws = strtr($raws, ["\n" => "\r\n"]);
    $r .= '<div>';
    $r .= '<pre>';
    $r .= '<code>';
    $r .= strtr(htmlspecialchars($raws), [
        "\n" => "<span class=\"c c-n\">\n</span>",
        "\r" => "<span class=\"c c-r\"></span>",
        "\t" => "<span class=\"c c-t\">\t</span>",
        ' ' => "<span class=\"c c-s\"> </span>"
    ]);
    $r .= '</code>';
    $r .= '</pre>';
    if ('result' === $view) {
        $r .= '<article>';
        $r .= view_result(x\markdown\from($raws) ?? "");
        $r .= '</article>';
    } else if ('source' === $view) {
        $r .= '<pre>';
        $r .= '<code>';
        $r .= view_source(x\markdown\from($raws) ?? "");
        $r .= '</code>';
        $r .= '</pre>';
    } else {
        $r .= '<pre>';
        $r .= '<code>';
        $lot = [];
        $r .= view_raw("<?php\n\nreturn " . export(x\markdown\from\rows($raws, $lot, 25, 0, \strlen($raws))) . ';');
        $r .= '</code>';
        $r .= '</pre>';
    }
    $r .= '</div>';
}

$r .= '</main>';

$r .= '</body>';
$r .= '</html>';

echo $r;