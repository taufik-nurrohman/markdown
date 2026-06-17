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
                    $color = '00b';
                    break;
                case T_INLINE_HTML:
                    $color = '000';
                    break;
                case T_RETURN:
                    $color = '070';
                    break;
                case T_CONSTANT_ENCAPSED_STRING:
                case T_ENCAPSED_AND_WHITESPACE:
                    $color = 'd00';
                    break;
            }
            $r .= '<span style="color:#' . $color . ';">' . strtr(htmlspecialchars($t[1]), [
                "\t" => "<span style=\"color:#400;\">\\t</span>",
                "\x1e" => "<span style=\"color:#400;\">\\x1e</span>",
                "\x2" => "<span style=\"color:#400;\">\\x2</span>",
                "\x3" => "<span style=\"color:#400;\">\\x3</span>"
            ]) . '</span>';
            continue;
        }
        $r .= '<span style="color:#070;">' . strtr(htmlspecialchars($t), [
            "\t" => "<span style=\"color:#400;\">\\t</span>",
            "\x1e" => "<span style=\"color:#400;\">\\x1e</span>",
            "\x2" => "<span style=\"color:#400;\">\\x2</span>",
            "\x3" => "<span style=\"color:#400;\">\\x3</span>"
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

function dent($value, $dent) {
    $r = "";
    foreach (explode("\n", $value) as $k => $v) {
        $r .= "\n" . (0 !== $k && "" !== $v ? $dent . $v : $v);
    }
    return substr($r, 1);
}

// <https://github.com/mecha-cms/mecha/blob/v3.2.0/engine/f.php#L1606-L1671>
function export($value, $dent = "", $key_as_string = false, $is_object = null) {
    if (is_object($value)) {
        if ($value instanceof stdClass) {
            return '(object) ' . export((array) $value, $dent, true, true);
        }
        return '(object) []';
    }
    if (is_array($value)) {
        $r = [];
        if (!$is_object && array_is_list($value)) {
            foreach ($value as $k => $v) {
                $r[] = export($v, $dent . '  ', $key_as_string);
            }
        } else {
            foreach ($value as $k => $v) {
                $k = export($k);
                if ($key_as_string && is_numeric($k)) {
                    $k = "'" . $k . "'";
                }
                $r[] = $k . ' => ' . export($v, $dent . '  ', $key_as_string);
            }
        }
        if (!$r) {
            return '[]';
        }
        return "[\n  " . $dent . implode(",\n" . $dent . '  ', $r) . "\n" . $dent . ']';
    }
    $value = var_export($value, true);
    if ("''" === $value) {
        return '""';
    }
    if ('NULL' === $value) {
        return 'null';
    }
    if (false !== strpos($value, "\n") || false !== strpos(substr($value, 1, -1), "'")) {
        $value = "<<<TEXT\n" . implode("\n", array_map(function ($v) use ($dent) {
            if ("" !== $v) {
                return $dent . strtr($v, ["\\'" => "'"]);
            }
            return "";
        }, explode("\n", substr($value, 1, -1)))) . "\n" . $dent . 'TEXT';
    }
    return $value;
}

function b(string $value, int $i, int $limit) {
    if (($m = \strspn($value, '#', $i)) && $m < 7 && ($w = \strspn($value, " \n\r\t", $i + $m))) {
        $r = ['h' . $m, $i + $m + $w]; // Block type and block’s content start
        // Case for empty header block(s)
        if ($r[1] > $i + $m && ("\n" === $value[$i + $m] || "\r" === $value[$i + $m])) {
            $r[1] = $i + $m;
        }
        // Block’s content end
        $r[] = $i += \strcspn($value, "\n\r", $i);
        // Next line after current block
        if ($i < $limit) {
            $i += "\r" === $value[$i] && "\n" === ($value[$i + 1] ?? 0) ? 2 : 1;
        }
        $r[] = $i;
        return $r;
    }
    return false;
}

function rows(string $value, array &$lot = [], int $deep = 0) {
    $lot = \array_replace([[], [], []], $lot);
    if ("" === \trim($value)) {
        return [[], $lot, 0];
    }
    $i = $start = \strspn($value, "\r\n");
    $limit = \strlen($value);
    $rows = [];
    $s = "";
    $void = 0;
    while ($i < $limit) {
        $c = $value[$i];
        if ($i === $start || ("\n" === $value[$i - 1] || "\r" === $value[$i - 1])) {
            $d = \strspn($value, ' ', $i);
            if ($d >= 4) {
                $s .= \substr($value, $i, $d);
                $i += $d;
                continue;
            }
            if ("" !== $s && \strspn($s, ' ') >= 4) {
                $rows[] = ['pre', \substr(\strtr($s, ["\n   " => "\n"]), 4, -1), []];
                $s = "";
                continue;
            }
            $i += $d;
            if ($b = b($value, $i, $limit)) {
                "" !== $s && ($rows[] = \substr($s, 0, -1)) && ($s = "");
                $rows[] = [$b[0], \substr($value, $b[1], $b[2] - $b[1]), []];
                $i = $b[3];
                continue;
            }
            if (('-' === $c || '=' === $c) && "" !== $s) {
                $n = ($i + ($m = \strspn($value, $c, $i)));
                if ($n < $limit && "\n" !== $value[$i + $m] && "\r" !== $value[$i + $m]) {
                    $s .= \substr($value, $i, $m);
                    $i = $n;
                    continue;
                }
                $i = $n;
                $rows[] = ['h' . ('-' === $c ? 2 : 1), \substr($s, 0, -1), []];
                $s = "";
                continue;
            }
        }
        if ("\n" === $c || "\r" === $c) {
            if ("\r" === $c && $i + 1 < $limit && "\n" === $value[$i + 1]) {
                ++$i;
            }
            ++$i;
            // Blank line
            if ($i >= $limit || "\n" === $value[$i] || "\r" === $value[$i]) {
                if (\strspn($s, ' ') >= 4) {
                    $s = \substr(\strtr($s, ["\n    " => "\n"]), 4);
                    $rows[] = ['pre', $s, []];
                    $s = "";
                    continue;
                }
                "" !== $s && ($rows[] = $s) && ($s = "");
                continue;
            }
            "" !== $s && ($s .= "\n");
            continue;
        }
        $s .= $c;
        ++$i;
    }
    if ("" !== $s) {
        if (\strspn($s, ' ') >= 4) {
            $rows[] = ['pre', \substr(\strtr($s, ["\n    " => "\n"]), 4), []];
        } else {
            $rows[] = $s;
        }
    }
    return [$rows, $lot, $void];
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
:root {
  background: #fff;
  color: #000;
  font: normal normal 13px/1.5 Verdana, sans-serif;
  padding: 1em;
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
body > main > div > div,
body > main > div > pre {
  border: 2px solid #000;
  flex: 1;
  padding: 0.25em 0.35em;
}
body > main > div > pre {
  background: #ffc;
  border-width: 1px;
  font: normal normal 12px/1.25 'Courier New', monospace;
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
:disabled {
  cursor: not-allowed;
  opacity: 0.5;
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
foreach (glob(PATH . D . 'from' . D . $test . D . '*', GLOB_ONLYDIR) as $v) {
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
    $r .= '<div>';
    $r .= '<pre>';
    $r .= htmlspecialchars($raws);
    $r .= '</pre>';
    if ('result' === $view) {
        $r .= '<div>';
        $r .= view_result(x\markdown\from($raws));
        $r .= '</div>';
    } else if ('source' === $view) {
        $r .= '<pre>';
        // $r .= view_source(x\markdown\from($raws));
        $r .= view_raw("<?php\n\nreturn " . export(rows($raws)[0]) . ';');
        $r .= '</pre>';
    } else {
        $r .= '<pre>';
        $r .= view_raw("<?php\n\nreturn " . export(x\markdown\from\raws($raws)) . ';');
        $r .= '</pre>';
    }
    $r .= '</div>';
}

$r .= '</main>';

$r .= '</body>';
$r .= '</html>';

echo $r;