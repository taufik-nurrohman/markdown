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

function view(string $text) {
    $i = 0;
    $limit = strlen($text);
    $s = "";
    while ($i < $limit) {
        $c = $text[$i];
        if ("\n" === $c) {
            $s .= '<mark class="c c-n">' . $c . '</mark>';
            ++$i;
            continue;
        }
        if ("\r" === $c) {
            $s .= '<mark class="c c-r">' . ("\n" === ($text[$i + 1] ?? "") ? "" : $c) . '</mark>';
            ++$i;
            continue;
        }
        if ("\t" === $c) {
            $s .= '<mark class="c c-t">' . $c . '</mark>';
            ++$i;
            continue;
        }
        if (' ' === $c) {
            for ($w = $i; $w < $limit && ' ' === $text[$w]; ++$w);
            if (
                // More than one space in a row
                ($w - $i) > 1 ||
                // Space at the start of the line
                ($w === $limit || "\n" === $text[$w] || "\r" === $text[$w]) ||
                // Space at the end of the line
                (0 === $i || "\n" === $text[$i - 1] || "\r" === $text[$i - 1])
            ) {
                $s .= str_repeat('<mark class="c c-s"> </mark>', $w - $i);
            } else {
                $s .= ' ';
            }
            $i = $w;
            continue;
        }
        if ('&' === $c || '<' === $c || '>' === $c) {
            $s .= htmlspecialchars($c);
            ++$i;
            continue;
        }
        $s .= $c;
        ++$i;
    }
    return $s;
}

function view_result(string $text) {
    return false !== stripos($text, '</script>') ? preg_replace('~(<script>)([\s\S]*?)(</script>)~i', '$1$3', $text) : $text;
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
            if (false !== ($n = strpos($text, '>', $i))) {
                $s .= '<span style="color:#00b;font-weight:bold;">';
                $part = substr($text, $i, $n += 1 - $i);
                $s .= '&lt;';
                $s .= view_source(substr($part, 1, -1));
                $s .= '&gt;';
                $s .= '</span>';
                $i += $n;
                continue;
            }
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
        if (' ' === $c) {
            for ($w = $i; $w < $limit && ' ' === $text[$w]; ++$w);
            if (
                // More than one space in a row
                ($w - $i) > 1 ||
                // Space at the start of the line
                ($w === $limit || "\n" === $text[$w] || "\r" === $text[$w]) ||
                // Space at the end of the line
                (0 === $i || "\n" === $text[$i - 1] || "\r" === $text[$i - 1])
            ) {
                $s .= str_repeat('<mark class="c c-s"> </mark>', $w - $i);
            } else {
                $s .= ' ';
            }
            $i = $w;
            continue;
        }
        $s .= substr($text, $i, $n = strcspn($text, " &<\\\t", $i));
        $i += $n;
    }
    return $s;
}

function view_tree(string $text) {
    $s = "";
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
            $s .= '<span style="color:#' . $color . ';">' . strtr(htmlspecialchars($t[1]), [
                "\t" => "<span class=\"c c-t\">\t</span>",
                "\x1e" => "<span class=\"c\">\x1e</span>",
                "\x2" => "<span class=\"c\">\x2</span>",
                "\x3" => "<span class=\"c\">\x3</span>"
            ]) . '</span>';
            continue;
        }
        $s .= '<span style="color:#070;">' . strtr(htmlspecialchars($t), [
            "\t" => "<span class=\"c c-t\">\t</span>",
            "\x1e" => "<span class=\"c\">\x1e</span>",
            "\x2" => "<span class=\"c\">\x2</span>",
            "\x3" => "<span class=\"c\">\x3</span>"
        ]) . '</span>';
    }
    return $s;
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

$where = 'blockquote, dd, div, dl, dt, figure, h1, h2, h3, h4, h5, h6, hgroup, hr, ol, p, pre, table, ul';

$s  = '<!DOCTYPE html>';
$s .= '<html dir="ltr">';
$s .= '<head>';
$s .= '<meta content="width=device-width" name="viewport">';
$s .= '<meta charset="utf-8">';
$s .= '<title>';
$s .= 'Markdown to HTML';
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
article :where({$where}) + :where({$where}) {
  margin-top: 1rem;
}
article :where(sub, sup) {
  font-size: 0.8em;
}
article abbr {
  border-bottom: 1px dotted #000;
  cursor: help;
}
article blockquote {
  border-left: 4px solid #eee;
  color: #666;
  font-size: 100%;
  padding: 0 0 0 0.75em;
}
article details:open > summary {
  margin-bottom: 1rem;
}
article del {
  text-decoration: line-through;
}
article li:where(:not(:first-child)) > :where({$where}):where(:first-child) {
  margin-top: 1rem;
}
article p img {
  display: inline-block;
  position: relative;
  top: 0.25rem;
}
article pre code {
  background: #000;
  color: #fff;
  padding: 0.5em 0.75em;
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
  min-width: 0;
  padding: 0.75em;
  word-wrap: break-word;
}
body > main > div > article {
  border-width: 2px;
}
body > main > div > p {
  flex: 1;
  font-size: 75%;
  margin-top: -0.5rem;
  min-width: 0;
}
body > main > div > pre {
  background: #ffc;
  padding: 0.25em 0.35em;
  white-space: pre-wrap;
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
code {
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
  content: '\\22c5';
}
.c-t::after {
  content: '\\5c t';
  text-align: left;
}
:where(.c-n, .c-r, .c-t)::before {
  content: '  ';
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
@media (max-width: 600px) {
  body > main > div {
    flex-direction: column;
  }
}
CSS;
$s .= '</style>';
$s .= '</head>';
$s .= '<body>';

$s .= '<form action="#top" method="get">';
$s .= '<fieldset>';
$s .= '<legend>';
$s .= 'Navigation';
$s .= '</legend>';
$s .= '<p>';
$s .= '<span role="group" style="gap: 0.75em;">';
$s .= '<b>Block Mode:</b>';
foreach (['Yes' => true, 'No' => false] as $k => $v) {
    $s .= ' ';
    $s .= '<label role="group">';
    $s .= '<input' . ($v === $block ? ' checked' : "") . ' name="block" type="radio" value="' . ((int) $v) . '">';
    $s .= ' ';
    $s .= '<span>';
    $s .= $k;
    $s .= '</span>';
    $s .= '</label>';
}
$s .= '</span>';
$s .= ' ';
$s .= '<span role="group" style="gap: 0.75em;">';
$s .= '<b>Line Ending Preference:</b>';
foreach (['CR', 'LF', 'CRLF'] as $v) {
    $s .= ' ';
    $s .= '<label role="group">';
    $s .= '<input' . ($v === $line ? ' checked' : "") . ' name="line" type="radio" value="' . $v . '">';
    $s .= ' ';
    $s .= '<span>';
    $s .= $v;
    $s .= '</span>';
    $s .= '</label>';
}
$s .= '</span>';
$s .= '</p>';
$s .= '<p role="group">';
foreach (array_merge(glob(PATH . D . 'from' . D . '*', GLOB_ONLYDIR), ['LICENSE', 'README']) as $v) {
    $s .= '<button' . ($test === ($v = basename($v)) ? ' disabled' : "") . ' name="test" type="submit" value="' . htmlspecialchars($v) . '">' . htmlspecialchars($v) . '</button> ';
}
$s  = substr($s, 0, -1) . '</p>';
$s .= '<p role="group">';
$s .= '<select name="view">';
foreach (['result', 'source', 'tree'] as $v) {
    $s .= '<option' . ($v === $view ? ' selected' : "") . ' value="' . htmlspecialchars($v) . '">';
    $s .= ucfirst($v);
    $s .= '</option>';
}
$s .= '</select>';
$s .= ' ';
$s .= '<button name="test" type="submit" value="' . htmlspecialchars($test) . '">';
$s .= 'View';
$s .= '</button>';
$s .= '</p>';
$s .= '</fieldset>';
$s .= '<input name="batch" type="hidden" value="1">';
$s .= '</form>';

$s .= '<form action="#top" method="get">';
$s .= '<fieldset>';
$s .= '<legend>';
$s .= 'Batch';
$s .= '</legend>';
$s .= '<p role="group">';
$s .= '<button' . ($batch === '*' ? ' disabled' : "") . ' name="batch" type="submit" value="*">*</button>';
$folders = glob(PATH . D . 'from' . D . $test . D . '*', GLOB_ONLYDIR);
usort($folders, 'strnatcmp');
foreach ($folders as $v) {
    $s .= ' <button' . ($batch === ($v = basename($v)) ? ' disabled' : "") . ' name="batch" type="submit" value="' . htmlspecialchars($v) . '">' . htmlspecialchars($v) . '</button>';
}
$s .= '</p>';
$s .= '</fieldset>';
$s .= '<input name="block" type="hidden" value="' . ((int) $block) . '">';
$s .= '<input name="line" type="hidden" value="' . htmlspecialchars($line) . '">';
$s .= '<input name="test" type="hidden" value="' . htmlspecialchars($test) . '">';
$s .= '<input name="view" type="hidden" value="' . htmlspecialchars($view) . '">';
$s .= '</form>';

$s .= '<main>';

$current = "";
$error_count = 0;

foreach ($files as $file) {
    $n = basename($v = ".\\" . substr(strtr(dirname($file), ['/' => "\\"]), strlen(PATH . D . 'from' . D)));
    if ($v !== $current || "" === $current) {
        $s .= '<h1 id="to:' . htmlspecialchars($n) . '"><a aria-hidden="true" href="#to:' . $n . '">#</a> ' . $v . "\\*" . '</h1>';
        $current = $v;
    }
    $raws = file_get_contents($file);
    $raws = strtr($raws, ["\n" => 'CR' === $line ? "\r" : ('CRLF' === $line ? "\r\n" : "\n")]);
    $size = strlen($raws);
    $s .= '<div>';
    $s .= '<pre>';
    $s .= '<code>';
    $s .= view($raws);
    $s .= '</code>';
    $s .= '</pre>';
    $end = $start = 0;
    if ('result' === $view) {
        $s .= '<article>';
        $start = hrtime(true);
        $r = x\markdown\from($raws, ['block' => $block]) ?? "";
        $end = (hrtime(true) - $start) / 1e6;
        $s .= view_result($r);
        $s .= '</article>';
    } else if ('source' === $view) {
        $s .= '<pre>';
        $s .= '<code>';
        $start = hrtime(true);
        $r = x\markdown\from($raws, ['block' => $block]) ?? "";
        $end = (hrtime(true) - $start) / 1e6;
        $s .= view_source($r);
        $s .= '</code>';
        $s .= '</pre>';
    } else {
        $s .= '<pre>';
        $s .= '<code>';
        $start = hrtime(true);
        $lot = [];
        if (!$block) {
            $r = x\markdown\from\row($raws, $lot, 25, 0, strlen($raws));
        } else {
            $r = x\markdown\from\rows($raws, $lot, 25, 0, strlen($raws));
        }
        $end = (hrtime(true) - $start) / 1e6;
        $s .= view_tree("<?php\n\nreturn " . export($r) . ';');
        $s .= '</code>';
        $s .= '</pre>';
    }
    $s .= '</div>';
    $s .= '<div>';
    $s .= '<p style="color:#' . ($size > 1024 ? '900' : '090') . ';">Input size ' . $size . ' bytes.</p>';
    $s .= '<p style="color:#' . ($end > 1 ? '900' : '090') . ';">Parsed in ' . $end . ' ms.</p>';
    $s .= '</div>';
}

$s .= '</main>';

$s .= '</body>';
$s .= '</html>';

echo $s;