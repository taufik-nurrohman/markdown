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

// Currently, there is no official attribute syntax specification in CommonMark except for raw HTML attribute(s). To
// make it as close as possible to the CommonMark specification or to prepare for the possibility of such specification
// in the future, I will make the attribute syntax rule(s) as close as possible to the raw HTML attribute specification.
function a(string $value, int $i, int $limit, $raw = false, string $f = "") {
    if ($i >= $limit) {
        return [];
    }
    if (!$raw && '{' !== $value[$i]) {
        return [];
    }
    $m = $raw ? 0 : 1;
    $n = $m + \strspn($value, c3, $m + $i, $limit - ($m + $i));
    $not = c3 . '"#.<=>`{}' . "'\\";
    $r = [];
    while ($i + $n < $limit) {
        $c = $value[$i + $n];
        if (!$raw && '}' === $c) {
            if (\is_array($a = $r['class'] ?? 0)) {
                \ksort($a);
                $r['class'] = \implode(' ', \array_keys($a));
            }
            return [$r, $n + 1];
        }
        if ('#' === $c) {
            ++$n; // Move past `#`
            $eat = \strcspn($value, $not, $i + $n, $limit - ($i + $n));
            if (isset($r['id'])) {
                $n += $eat;
                continue;
            }
            if ("" === ($s = \substr($value, $i + $n, $eat))) {
                return [];
            }
            $r['id'] = $s;
            $n += $eat;
            continue;
        }
        if ('.' === $c) {
            ++$n; // Move past `.`
            $eat = \strcspn($value, $not, $i + $n, $limit - ($i + $n));
            if (\is_string($r['class'] ?? 0)) {
                $n += $eat;
                continue;
            }
            if ("" === ($s = \substr($value, $i + $n, $eat))) {
                return [];
            }
            $r['class'][$s] = 1;
            $n += $eat;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#attribute-name>
        if ($eat = \strspn($value, c12, $i + $n, $limit - ($i + $n))) {
            $eat += \strspn($value, c13, $eat + $i + $n);
            $exist = isset($r[$k = \substr($value, $i + $n, $eat)]);
            $n += $eat;
            // <https://spec.commonmark.org/0.31.2#attribute-value-specification>
            $n += \strspn($value, c3, $i + $n, $limit - ($i + $n));
            if ('=' === ($value[$i + $n] ?? 0)) {
                ++$n; // Move past `=`
                $exist || ($r[$k] = "");
                $n += \strspn($value, c3, $i + $n, $limit - ($i + $n));
                $q = ($value[$i + $n] ?? 0);
                // <https://spec.commonmark.org/0.31.2#attribute-value>
                // <https://spec.commonmark.org/0.31.2#double-quoted-attribute-value>
                // <https://spec.commonmark.org/0.31.2#single-quoted-attribute-value>
                if ('"' === $q || "'" === $q) {
                    // Unlike the raw HTML attribute value specification, the attribute syntax allows for escaped
                    // character(s) within quoted attribute value(s), just like the link title specification. This
                    // decision was made because there is currently no official specification for attribute syntax in
                    // CommonMark. I will redo this part once an official attribute syntax specification is available.
                    $eat = ++$n;
                    while ($i + $n < $limit) {
                        $n += \strcspn($value, "\\" . $q, $i + $n);
                        if ($i + $n >= $limit || "\\" !== $value[$i + $n]) {
                            break;
                        }
                        $n += 2;
                    }
                    if ($i + $n >= $limit || $q !== $value[$i + $n]) {
                        return [];
                    }
                    $exist || ($r[$k] = v(\substr($value, $i + $eat, $n - $eat)));
                    ++$n;
                    continue;
                }
                $n += \strspn($value, c3, $i + $n, $limit - ($i + $n));
                // <https://spec.commonmark.org/0.31.2#unquoted-attribute-value>
                if ($eat = \strcspn($value, $not, $i + $n)) {
                    $exist || ($r[$k] = \substr($value, $i + $n, $eat));
                    $n += $eat;
                }
                continue;
            }
            if ($raw && "" === $f) {
                return [];
            }
            // Boolean attribute(s)
            if (!$exist) {
                $r[$k] = true;
            } else if ($raw) {
                $r['class'][\sprintf($f, $k)] = 1;
            }
            continue;
        }
        if ($eat = \strcspn($value, c3 . ($raw ? "" : '}'), $i + $n)) {
            // If there is an invalid attribute name found in the wrapped attribute syntax, or in the raw attribute
            // syntax where no class format is provided, the entire attribute syntax must be marked as invalid.
            if (!$raw || "" === $f) {
                return [];
            }
            // If a class format is provided, treat it as part of the class name and put it in the class queue.
            if (!\is_string($r['class'] ?? 0)) {
                $r['class'][\sprintf($f, \substr($value, $i + $n, $eat))] = 1;
            }
            $n += $eat;
        }
        $n += \strspn($value, c3, $i + $n, $limit - ($i + $n));
    }
    if (!$raw) {
        return [];
    }
    if (\is_array($a = $r['class'] ?? 0)) {
        \ksort($a);
        $r['class'] = \implode(' ', \array_keys($a));
    }
    return [$r, $n];
}

function d(string $value, int $i, int $limit) {
    if (false === \strpos(c1, $value[$i])) {
        return [0, 0];
    }
    $d = $n = 0;
    while ($i + $n < $limit) {
        $c = $value[$i + $n];
        if ("\t" === $c) {
            $d += 4 - ($d % 4);
            ++$n;
            continue;
        }
        if (' ' === $c) {
            $d += 1;
            ++$n;
            continue;
        }
        break; // Stop at the first character that is not a white-space
    }
    return [$d, $n];
}

function h(string $text) {
    return \strtr($text, ['&' => '&amp;', '<' => '&lt;', '>' => '&gt;']);
}

const b1 = ['pre' => 1, 'script' => 1, 'style' => 1, 'textarea' => 1];
const b6 = [
    'address' => 1, 'article' => 1, 'aside' => 1, 'base' => 1, 'basefont' => 1, 'blockquote' => 1, 'body' => 1,
    'caption' => 1, 'center' => 1, 'col' => 1, 'colgroup' => 1, 'dd' => 1, 'details' => 1, 'dialog' => 1,
    'dir' => 1, 'div' => 1, 'dl' => 1, 'dt' => 1, 'fieldset' => 1, 'figcaption' => 1, 'figure' => 1, 'footer' => 1,
    'form' => 1, 'frame' => 1, 'frameset' => 1, 'h1' => 1, 'h2' => 1, 'h3' => 1, 'h4' => 1, 'h5' => 1, 'h6' => 1,
    'head' => 1, 'header' => 1, 'hr' => 1, 'html' => 1, 'iframe' => 1, 'legend' => 1, 'li' => 1, 'link' => 1,
    'main' => 1, 'menu' => 1, 'menuitem' => 1, 'nav' => 1, 'noframes' => 1, 'ol' => 1, 'optgroup' => 1,
    'option' => 1, 'p' => 1, 'param' => 1, 'search' => 1, 'section' => 1, 'summary' => 1, 'table' => 1,
    'tbody' => 1, 'td' => 1, 'tfoot' => 1, 'th' => 1, 'thead' => 1, 'title' => 1, 'tr' => 1, 'track' => 1, 'ul' => 1
];

// <https://spec.commonmark.org/0.31.2#line-ending>
// <https://spec.commonmark.org/0.31.2#space>
// <https://spec.commonmark.org/0.31.2#tab>
const c1 = " \t";
const c2 = "\r\n";
const c3 = c1 . c2;

const c4 = '0123456789'; // Digit
const c5 = 'ABCDEF';
const c6 = 'abcdef';
const c7 = c4 . c5 . c6; // Hex
const c8 = c5 . 'GHIJKLMNOPQRSTUVWXYZ';
const c9 = c6 . 'ghijklmnopqrstuvwxyz';
const c10 = c8 . c9; // Alpha
const c11 = c4 . c10 . '-'; // Alpha + Digit + `-`

// <https://spec.commonmark.org/0.31.2#attribute-name>
const c12 = c10 . ':_';
const c13 = c11 . ':_.';

// <https://www.rfc-editor.org/rfc/rfc3986.html#section-2.2>
const c14 = '!#$&()*+,/:;=?@[]' . "'";

// <https://www.rfc-editor.org/rfc/rfc3986.html#section-2.3>
const c15 = c11 . '._~';

// <https://spec.commonmark.org/0.31.2#ascii-punctuation-character>
const c16 = c14 . '"%-.<>^_`{|}~' . "\\";

// <https://spec.commonmark.org/0.31.2#ascii-control-character>
const c17 = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0a\x0b\x0c\x0d\x0e\x0f\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1a\x1b\x1c\x1d\x1e\x1f\x7f";

const x1a = "\x1a";

// <https://spec.commonmark.org/0.31.2#link-label>
function k(string $value, int $i, int $limit, int $deep = 0) {
    if ($i >= $limit || '[' !== $value[$i]) {
        return [];
    }
    $n = 1;
    $s = "";
    while ($i + $n < $limit) {
        $c = $value[$i + $n];
        if ("\\" === $c && $i + $n + 1 < $limit && false !== \strpos(c16, $value[$i + $n + 1])) {
            $s .= $value[$i + ($n += 2)];
            continue;
        }
        if ($r = r($value, $i + $n, $limit)) {
            $n += $r;
            $n += \strspn($value, c1, $i + $n);
            // A blank line invalidates the current label
            if (r($value, $i + $n, $limit)) {
                return [];
            }
            $s .= $c;
            continue;
        }
        if ('[' === $c) {
            if ($deep < 1 || !($k = k($value, $i + $n, $limit, $deep - 1))) {
                return [];
            }
            $s .= \substr($value, $i + $n, $k[1]);
            $n += $k[1];
            continue;
        }
        if (']' === $c) {
            // Link label can have at most 999 character(s) inside the `[` and `]` character(s). It originally considers
            // line(s) to be composed of character(s) rather than byte(s), but this one counts byte(s) for simplicity.
            if (\strspn($s, c3) === ($size = \strlen($s)) || $size > 999) {
                return [];
            }
            return [$s = s($s, ' '), $n + 1, \strlen($s)];
        }
        $s .= $c;
        ++$n;
    }
    return [];
}

function m(string $value, int $i, int $limit) {
    return [$i, $n = \strcspn($value, c2, $i, $limit - $i), r($value, $i + $n, $limit)];
}

function r(string $value, int $i, int $limit) {
    if ($i >= $limit) {
        return 0;
    }
    if ("\n" === $value[$i]) {
        return 1;
    }
    if ("\r" === $value[$i]) {
        if ($i + 1 < $limit && "\n" === $value[$i + 1]) {
            return 2;
        }
        return 1;
    }
    return 0;
}

function row(string $value, array &$lot = [], int $deep = 0, int $i, int $limit) {
    if ($deep < 1) {
        return substr($value, $i, $limit - $i);
    }
    $last = null;
    $row = [];
    $s = "";
    $stack = []; // <https://spec.commonmark.org/0.31.2#delimiter-stack>
    while ($i < $limit) {
        $c = $value[$i];
        if ("\\" === $c && $i + 1 < $limit && false !== \strpos(c16, $value[$i + 1])) {
            ++$i;
            continue;
        }
        if ($r = r($value, $i, $limit)) {
            // <https://spec.commonmark.org/0.31.2#hard-line-break>
            if ("\\" === ($value[$i - 1] ?? 0)) {
                "" !== $s && ($row[] = h(\substr($s, 0, -1))) && ($s = "");
                $row[] = ['br', false, []];
                $i += $r;
                $i += \strspn($value, c1, $i, $limit - $i); // <https://spec.commonmark.org/0.31.2#example-637>
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#hard-line-break>
            if ("\t" === ($value[$i - 1] ?? 0) || (' ' === ($value[$i - 1] ?? 0) && ' ' === ($value[$i - 2] ?? 0))) {
                "" !== $s && ($row[] = h(\rtrim($s))) && ($s = "");
                $row[] = ['br', false, []];
                $i += $r;
                $i += \strspn($value, c1, $i, $limit - $i); // <https://spec.commonmark.org/0.31.2#example-636>
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#softbreak>
            $i += \strspn($value, c1, $i, $limit - $i) + $r;
            $s .= ' ';
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#entity-and-numeric-character-references>
        if ('&' === $c && false !== ($end = \strpos($value, ';', $i + 2))) {
            static $e;
            if ('#' === ($value[$n = $i + 1] ?? 0)) {
                if (false !== \strpos('Xx', $value[$i + 2] ?? x1a)) {
                    // <https://spec.commonmark.org/0.31.2#hexadecimal-numeric-character-references>
                    $n += \strspn($value, c7, $n + 2) + 2;
                    if ($end === $n && $n - $i - 3 < 7) {
                        $e ??= [];
                        $e[$k = \substr($value, $i, ++$end - $i)] ??= $k !== ($y = \html_entity_decode($k, \ENT_HTML5 | \ENT_QUOTES)) ? $y : "";
                        if ("" !== ($e[$k] ?? "")) {
                            "" !== $s && ($row[] = h($s)) && ($s = "");
                            $row[] = [false, $k, [], [3, $e[$k]]];
                            $i = $end;
                            continue;
                        }
                    }
                    $s .= $c;
                    ++$i;
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#decimal-numeric-character-references>
                $n += \strspn($value, c4, $n + 1) + 1;
                if ($end === $n && $n - $i - 2 < 8) {
                    $e ??= [];
                    $e[$k = \substr($value, $i, ++$end - $i)] ??= $k !== ($y = \html_entity_decode($m, \ENT_HTML5 | \ENT_QUOTES)) ? $y : "";
                    if ("" !== ($e[$k] ?? "")) {
                        "" !== $s && ($row[] = h($s)) && ($s = "");
                        $row[] = [false, $k, [], [2, $e[$k]]];
                        $i = $end;
                        continue;
                    }
                }
                $s .= $c;
                ++$i;
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#entity-references>
            $n += \strspn($value, c4 . c10, $n);
            if ($end === $n) {
                // Load a list of known entity reference(s) supported by your PHP environment to validate the current
                // HTML entity pattern. This step is necessary to reject unknown entity name(s), such as `&123;`
                $e ??= \array_flip(\get_html_translation_table(\HTML_ENTITIES, \ENT_HTML5 | \ENT_QUOTES));
                // If the entity is not present in the list, try to validate it using a more expensive method: pass the
                // string to the `html_entity_decode()` function and compare the result. If they are the same, the
                // matching entity pattern is not valid.
                $e[$k = \substr($value, $i, ++$end - $i)] ??= $k !== ($y = \html_entity_decode($k, \ENT_HTML5 | \ENT_QUOTES)) ? $y : "";
                if ("" !== ($e[$k] ?? "")) {
                    "" !== $s && ($row[] = h($s)) && ($s = "");
                    $row[] = [false, $k, [], [1, $e[$k]]];
                    $i = $end;
                    continue;
                }
            }
            $s .= $c;
            ++$i;
            continue;
        }
        if ('<' === $c) {
            // <https://spec.commonmark.org/0.31.2#processing-instruction>
            if ('?' === ($value[$i + 1] ?? 0) && false !== ($n = \strpos($value, '?>', $i + 2))) {
                "" !== $s && ($row[] = h($s)) && ($s = "");
                $row[] = [false, \substr($value, $i, $n += 2 - $i), [], [3]];
                $i += $n;
                continue;
            }
            if ('!' === ($value[$i + 1] ?? 0)) {
                // <https://spec.commonmark.org/0.31.2#html-comment>
                if ($i + 2 === \strpos($value, '--', $i + 2) && false !== ($n = \strpos($value, '-->', $i + 2))) {
                    "" !== $s && ($row[] = h($s)) && ($s = "");
                    $row[] = [false, \substr($value, $i, $n += 3 - $i), [], [2]];
                    $i += $n;
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#cdata-section>
                if ($i + 2 === \strpos($value, '[CDATA[', $i + 2) && false !== ($n = \strpos($value, ']]>', $i + 2))) {
                    "" !== $s && ($row[] = h($s)) && ($s = "");
                    $row[] = [false, \substr($value, $i, $n += 3 - $i), [], [5]];
                    $i += $n;
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#declaration>
                if (\strspn($value, c10, $i + 2, 1) && false !== ($n = \strpos($value, '>', $i + 3))) {
                    "" !== $s && ($row[] = h($s)) && ($s = "");
                    $row[] = [false, \substr($value, $i, $n += 1 - $i), [], [4]];
                    $i += $n;
                    continue;
                }
                $s .= $c;
                ++$i;
                continue;
            }
            if (false !== ($end = \strpos($value, '>', $i + 2))) {
                // <https://spec.commonmark.org/0.31.2#uri-autolink>
                if (false !== \strpos($value, ':', $i + 3) && ($m = \strspn($value, c10, $n = $i + 1))) {
                    $m += \strspn($value, c11 . '+.', $m + $n);
                    if ($m >= 2 && $m <= 32) { // <https://spec.commonmark.org/0.31.2#scheme>
                        if (':' === ($value[$m + $n] ?? 0)) {
                            $m += \strcspn($value, c17 . ' <>', $m + $n + 1) + 1;
                            if ($end === $m + $n) {
                                "" !== $s && ($row[] = h($s)) && ($s = "");
                                $row[] = ['a', h($u = \substr($value, $n, $m)), ['href' => u($u)], [3]];
                                // Check for attribute syntax after link
                                if ('{' === ($value[$i = $end + 1] ?? 0) && ($a = a($value, $i, $limit))) {
                                    $row[$k = \array_key_last($row)][2] = $a[0] + $row[$k][2];
                                    $i += $a[1];
                                }
                                continue;
                            }
                        }
                    }
                }
                // <https://spec.commonmark.org/0.31.2#email-autolink>
                if (false !== \strpos($value, '@', $i + 2) && ($m = \strspn($value, c11 . '!#$%&*+./=?^`{|}~' . "'", $n = $i + 1))) {
                    if ('@' === ($value[$m + $n] ?? 0)) {
                        $m += \strspn($value, c11 . '.', $m + $n + 1) + 1;
                        if ($end === $m + $n) {
                            "" !== $s && ($row[] = h($s)) && ($s = "");
                            $row[] = ['a', h($u = \substr($value, $n, $m)), ['href' => u('mailto:' . $u)], [3]];
                            // Check for attribute syntax after link
                            if ('{' === ($value[$i = $end + 1] ?? 0) && ($a = a($value, $i, $limit))) {
                                $row[$k = \array_key_last($row)][2] = $a[0] + $row[$k][2];
                                $i += $a[1];
                            }
                            continue;
                        }
                    }
                }
                // <https://spec.commonmark.org/0.31.2#html-tag>
                if ($i + 2 >= $limit) {
                    $s .= $c;
                    ++$i;
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#closing-tag>
                if ($end = '/' === $value[$n = $i + 1]) {
                    ++$n;
                }
                // <https://spec.commonmark.org/0.31.2#tag-name>
                if ($m = \strspn($value, c10, $n)) {
                    $n += \strspn($value, c11, $m + $n) + $m;
                    // <https://spec.commonmark.org/0.31.2#closing-tag>
                    if ($end) {
                        $n += \strspn($value, c3, $n);
                    // <https://spec.commonmark.org/0.31.2#open-tag>
                    } else {
                        if ($n < $limit && '>' !== $value[$n]) {
                            // <https://spec.commonmark.org/0.31.2#attribute>
                            while ($n < $limit) {
                                if (!$m = \strspn($value, c3, $n)) {
                                    break;
                                }
                                // <https://spec.commonmark.org/0.31.2#attribute-name>
                                if (!$m = \strspn($value, c12, $n += $m)) {
                                    break;
                                }
                                $n += \strspn($value, c13, $n + $m) + $m;
                                // <https://spec.commonmark.org/0.31.2#attribute-value-specification>
                                $n += \strspn($value, c3, $n);
                                if ('=' === ($value[$n] ?? 0)) {
                                    $q = $value[$n += \strspn($value, c3, ++$n)] ?? 0;
                                    // <https://spec.commonmark.org/0.31.2#double-quoted-attribute-value>
                                    // <https://spec.commonmark.org/0.31.2#single-quoted-attribute-value>
                                    if ('"' === $q || "'" === $q) {
                                        if (false === ($m = \strpos($value, $q, ++$n))) {
                                            break;
                                        }
                                        $n = $m + 1;
                                        continue;
                                    }
                                    // <https://spec.commonmark.org/0.31.2#unquoted-attribute-value>
                                    $n += \strcspn($value, c3 . '"<=>`' . "'", $n);
                                    continue;
                                }
                            }
                            if ('/' === ($value[$n] ?? 0)) {
                                ++$n;
                            }
                        }
                    }
                    if ('>' === ($value[$n] ?? 0)) {
                        "" !== $s && ($row[] = h($s)) && ($s = "");
                        $row[] = [false, \substr($value, $i, ($n += 1) - $i), [], [7]];
                        $i = $n;
                        continue;
                    }
                }
            }
            $s .= $c;
            ++$i;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#code-span>
        if ('`' === $c) {
            $eat = $i + ($n = \strspn($value, $c, $i));
            while (false !== ($eat = \strpos($value, $c, $eat))) {
                if ($n === \strspn($value, $c, $eat) && $c !== ($value[$eat + $n] ?? 0)) {
                    $text = \substr($value, $i + $n, $eat - ($i + $n));
                    // Line break(s) are converted to space(s)
                    $text = \strtr($text, [
                        "\n" => ' ',
                        "\r\n" => ' ',
                        "\r" => ' '
                    ]);
                    // If the resulting string both begins and ends with a space character, but does not consist
                    // entirely of space character(s), a single space character is removed from the front and back.
                    if (\strlen($text) > 1 && ' ' === $text[0] && ' ' === \substr($text, -1) && "" !== \trim($text, ' ')) {
                        $text = \substr($text, 1, -1);
                    }
                    "" !== $s && ($row[] = h($s)) && ($s = "");
                    $row[] = ['code', h($text), []];
                    $i = $eat + $n;
                    break;
                }
                $eat += \strspn($value, $c, $eat);
            }
            if (false === $eat) {
                $s .= $c;
                ++$i;
                continue;
            }
            // Check for attribute syntax after code
            if ('{' === ($value[$i] ?? 0) && ($a = a($value, $i, $limit))) {
                $row[\array_key_last($row)][2] = $a[0];
                $i += $a[1];
            }
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#image-description>
        if ('!' === $c && '[' === ($value[$i + 1] ?? 0)) {
            "" !== $s && ($row[] = h($s)) && ($s = "");
            $current = \count($stack);
            $i += 2;
            $row[] = $c .= '[';
            $stack[] = [$c, [1, true, true, false], [\array_key_last($row), $last], false];
            if (null !== $last) {
                $stack[$last][2][2] = $current;
            }
            $last = $current;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#link-text>
        if ('[' === $c) {
            "" !== $s && ($row[] = h($s)) && ($s = "");
            $current = \count($stack);
            $row[] = $c;
            $stack[] = [$c, [1, true, true, false], [\array_key_last($row), $last], false];
            if (null !== $last) {
                $stack[$last][2][2] = $current;
            }
            $last = $current;
            ++$i;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#delimiter-run>
        if ('*' === $c) {
            "" !== $s && ($row[] = h($s)) && ($s = "");
            $can_close = $can_open = true;
            $current = \count($stack);
            $row[] = $c;
            $stack[] = [$c, [\strspn($value, $c, $i, $limit - $i), true, $can_open, $can_close], [\array_key_last($row), $last], false];
            if (null !== $last) {
                $stack[$last][2][2] = $current;
            }
            $last = $current;
            ++$i;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#delimiter-run>
        if ('_' === $c) {
            "" !== $s && ($row[] = h($s)) && ($s = "");
            $can_close = $can_open = true;
            $current = \count($stack);
            $row[] = $c;
            $stack[] = [$c, [\strspn($value, $c, $i, $limit - $i), true, $can_open, $can_close], [\array_key_last($row), $last], false];
            if (null !== $last) {
                $stack[$last][2][2] = $current;
            }
            $last = $current;
            ++$i;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#look-for-link-or-image>
        if (']' === $c) {
            "" !== $s && ($row[] = h($s)) && ($s = "");
            // No opening `[`
            if (null === $last) {
                $s .= ']';
                ++$i;
                continue;
            }
            // Find nearest `[`
            for ($z = $last; null !== $z; $z = $stack[$z][2][1]) {
                if (('![' === $stack[$z][0] || '[' === $stack[$z][0]) && $stack[$z][1][1]) {
                    break;
                }
            }
            if (null === $z) {
                $s .= ']';
                ++$i;
                continue;
            }
            // Not an inline link
            if ('(' !== ($value[$i + 1] ?? 0)) {
                $s .= ']';
                ++$i;
                continue;
            }
            // Parse destination (simplified)
            $j = $i + 2;
            $n = \strpos($value, ')', $j);
            if (false === $n) {
                $s .= ']';
                ++$i;
                continue;
            }
            $url = \substr($value, $j, $n - $j);
            // Build children
            $from = $stack[$z][2][0];
            $children = [];
            foreach ($row as $k => $v) {
                if ($k <= $from) {
                    continue;
                }
                $children[] = $v;
                unset($row[$k]);
            }
            // Replace `![` and `[` node
            $row[$from] = '[' === $stack[$z][0] ? ['a', $children, ['href' => $url]] : ['img', false, [
                'alt' => $children,
                'src' => $url
            ]];
            $stack[$z][1][1] = false;
            $i = $n + 1;
            continue;
        }
        $s .= $c;
        ++$i;
    }
    if ("" !== $s) {
        $row[] = h($s);
    }
    $row = \array_values($row);
    if (1 === \count($row) && \is_string($row[0])) {
        return $row[0];
    }
    return $row ?: "";
}

function rows(string $value, array &$lot = [], int $deep = 0, int $i, int $limit) {
    $lot = \array_replace([[], [], []], $lot);
    if ("" === \trim($value)) {
        return [[], $lot, 0];
    }
    $rows = [];
    $s = "";
    $void = 0;
    while ($i < $limit) {
        $m = m($value, $i, $limit);
        // <https://spec.commonmark.org/0.31.2#blank-line>
        if ($m[1] === \strspn($value, c1, $i, $limit - $i)) {
            "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
            $i += $m[1] + $m[2];
            ++$void;
            continue;
        }
        $d = d($value, $i, $limit);
        // A tab, 4 space(s), or less than 4 of space(s) followed by a tab should occupy at least 4 character(s)
        // <https://spec.commonmark.org/0.31.2#indented-code-block>
        if ($d[0] >= 4) {
            // <https://spec.commonmark.org/0.31.2#example-113>
            if ("" !== $s) {
                $s .= \substr($value, $i, $m[1]) . "\n";
                $i += $m[1] + $m[2];
                continue;
            }
            $s = \substr($value, $i + ($w = w($value, $i))[0], $m[1] - $w[0]);
            $i += $m[1] + $m[2];
            while ($i < $limit) {
                $m = m($value, $i, $limit);
                if ($m[1] !== \strspn($value, c1, $i, $m[1]) && d($value, $i, $limit)[0] < 4) {
                    // Previous line was a blank line
                    if ("" !== $s && "\n" === $s[\strlen($s) - 1]) {
                        $s = \substr($s, 0, -1);
                        ++$void;
                    }
                    break;
                }
                $s .= "\n" . \substr($value, $i + ($w = w($value, $i))[0], $m[1] - $w[0]);
                $i += $m[1] + $m[2];
                // End of the stream
                if (0 === $m[2]) {
                    break;
                }
            }
            $rows[] = ['pre', [['code', h($s . "\n"), []]], [], [0, ""]];
            $s = "";
            continue;
        }
        // At this point, the number of character(s) occupied by the indentation, which is made up by a mix of
        // space(s) and tab(s), should be the same, because an indentation less than 4 character(s) would never be
        // made by a mix of space(s) and tab(s). A tab already covers at most 4 column(s), so any indentation less
        // than 4 character(s) must be made up of space(s) only. This variable can then be used to jump past the
        // first few space(s) that precede the actual block marker.
        $d = $d[1];
        // I am so sorry about the order, especially for those of you with ADHD. This parser does not process HTML
        // block type 1 through 7 in order. It instead starts with a type of block that’s easier to spot.
        // <https://spec.commonmark.org/0.31.2#html-block>
        if ('<' === $value[$d + $i]) {
            // <https://spec.commonmark.org/0.31.2#processing-instruction>
            if ('?' === ($value[$n = $d + $i + 1] ?? 0) && false !== ($to = \strpos($value, '?>', $n + 1))) {
                "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                $rows[] = [false, \substr($value, $i, ($to += \strcspn($value, c2, $to)) - $i), [], [3]];
                $i = $to + r($value, $to, $limit);
                continue;
            }
            if ('!' === ($value[$n = $d + $i + 1] ?? 0)) {
                // <https://spec.commonmark.org/0.31.2#html-comment>
                if ($n + 1 === \strpos($value, '--', $n) && false !== ($to = \strpos($value, '-->', $n + 1))) {
                    "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                    $rows[] = [false, \substr($value, $i, ($to += \strcspn($value, c2, $to)) - $i), [], [2]];
                    $i = $to + r($value, $to, $limit);
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#cdata-section>
                if ($n + 1 === \strpos($value, '[CDATA[', $n) && false !== ($to = \strpos($value, ']]>', $n + 1))) {
                    "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                    $rows[] = [false, \substr($value, $i, ($to += \strcspn($value, c2, $to)) - $i), [], [5]];
                    $i = $to + r($value, $to, $limit);
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#declaration>
                if (\strspn($value, c10, $n + 1, 1) && false !== ($to = \strpos($value, '>', $n + 1))) {
                    "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                    $rows[] = [false, \substr($value, $i, ($to += \strcspn($value, c2, $to)) - $i), [], [4]];
                    $i = $to + r($value, $to, $limit);
                    continue;
                }
                $s .= \substr($value, $i, $m[1]) . "\n";
                $i += $m[1] + $m[2];
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#html-tag>
            $b = \strtolower(\substr($value, $n = $d + $i + 1, \strcspn($value, c3 . '>', $n)));
            if (isset(b1[$b]) && false !== ($to = \stripos($value, '</' . $b . '>', $n + \strlen($b) + 1))) {
                "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                $rows[] = [false, \substr($value, $i, ($to += \strcspn($value, c2, $to)) - $i), [], [1, $b]];
                $i = $to + r($value, $to, $limit);
                continue;
            }
            // HTML block type 6 does not treat open and close tag(s) differently. The initial tag does not need to
            // be a valid HTML tag. As long as it starts like one, it will be interpreted as such. Even a start tag
            // that looks like `<div <!— <?asdf` still counts as a valid HTML block type 6.
            if (isset(b6[$b = \trim($b, '/')])) {
                "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                $s = \substr($value, $i, $m[1]);
                $i += $m[1] + $m[2];
                while ($i < $limit) {
                    $m = m($value, $i, $limit);
                    // A blank line ends the current block
                    if ($m[1] === \strspn($value, c1, $i, $m[1])) {
                        break;
                    }
                    $s .= "\n" . \substr($value, $i, $m[1]);
                    $i += $m[1] + $m[2];
                    // End of the stream
                    if (0 === $m[2]) {
                        break;
                    }
                }
                $rows[] = [false, $s, [], [6, $b]];
                $s = "";
                continue;
            }
            // HTML block type 7 cannot interrupt a paragraph
            if ("" === $s && $d + $i + 2 < $limit) {
                for ($n = $i + $m[1]; $n > $i && false !== \strpos(c1, $value[$n - 1]); --$n);
                // HTML block type 7 must be “complete”
                if ($n > $i && '>' === $value[$n - 1]) {
                    $row = row($value, $lot, 1, $d + $i, $n);
                    if (\is_array($row) && 1 === \count($row) && \is_array($row = \reset($row)) && false === $row[0] && 7 === $row[3][0]) {
                        $s = \substr($value, $i, $m[1]);
                        $i += $m[1] + $m[2];
                        while ($i < $limit) {
                            $m = m($value, $i, $limit);
                            // A blank line ends the current block
                            if ($m[1] === \strspn($value, c1, $i, $m[1])) {
                                break;
                            }
                            $s .= "\n" . \substr($value, $i, $m[1]);
                            $i += $m[1] + $m[2];
                            // End of the stream
                            if (0 === $m[2]) {
                                break;
                            }
                        }
                        $rows[] = [false, $s, [], [7]];
                        $s = "";
                        continue;
                    }
                }
            }
            $s .= \substr($value, $i, $m[1]) . "\n";
            $i += $m[1] + $m[2];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#block-quote-marker>
        if ('>' === $value[$d + $i]) {
            "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
            $w = w($value, $i + ($n = $d + 1), 1, $n);
            $s .= $w[1] . \substr($value, $i + ($n += $w[0]), $m[1] - $n);
            $i += $m[1] + $m[2];
            while ($i < $limit) {
                $d = d($value, $i, $limit);
                $m = m($value, $i, $limit);
                if ($d[0] < 4 && '>' === ($value[$d[1] + $i] ?? 0)) {
                    $w = w($value, $i + ($n = $d[1] + 1), 1, $n);
                    $s .= "\n" . $w[1] . \substr($value, $i + ($n += $w[0]), $m[1] - $n);
                    $i += $m[1] + $m[2];
                    continue;
                }
                // A blank line ends the current block
                if ($m[1] === \strspn($value, c1, $i, $m[1])) {
                    break;
                }
                $b = rows($value, $lot, 0, $i, $i + $m[1])[0][0] ?? 0;
                // Not a paragraph continuation text
                if (!$b || !('p' === $b[0] || 'pre' === $b[0] && "" === $b[3][1] || false === $b[0] && 7 === $b[3][0])) {
                    break;
                }
                // TODO
                if ('pre' === $b[0]) {
                    $s .= "\n" . \substr($value, $i, $m[1]);
                    $i += $m[1] + $m[2];
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#paragraph-continuation-text>
                $s .= "\n" . ($w = w($value, $i))[1];
                // Special case
                if (false !== \strpos('-=', $value[$i + $w[0]] ?? x1a)) {
                    $s .= "\\";
                }
                $s .= \substr($value, $i + $w[0], $m[1] - $w[0]);
                $i += $m[1] + $m[2];
                // End of the stream
                if (0 === $m[2]) {
                    break;
                }
            }
            $rows[] = ['blockquote', $s, []];
            $s = "";
            continue;
        }
        // There is no formal specification for the abbreviation block in CommonMark, so I will treat it similarly
        // to the link reference definition block. It acts as a leaf block that cannot interrupt a paragraph. It can
        // span multiple line(s), but it cannot contain any blank line(s).
        if ("" === $s && '*' === $value[$n = $d + $i] && '[' === ($value[++$n] ?? 0) && ($k = k($value, $n, $limit)) && ':' === ($value[$n + $k[1]] ?? 0)) {
            $n += $k[1] + 1;
            $n += \strspn($value, c1, $n);
            // Refresh the cursor in case the label spans multiple line(s)
            $m = m($value, $i = $n, $limit);
            $s = \substr($value, $i, $m[1]);
            $i += $m[1] + $m[2];
            while ($i < $limit) {
                $m = m($value, $i, $limit);
                // A blank line ends the current block
                if ($m[1] === \strspn($value, c1, $i, $m[1])) {
                    break;
                }
                // Current line is an indented line
                if (d($value, $i, $limit)[0]) {
                    $s .= "\n" . \substr($value, $i, $m[1]);
                    $i += $m[1] + $m[2];
                    continue;
                }
                $b = rows($value, $lot, 0, $i, $i + $m[1])[0][0] ?? 0;
                // Not a paragraph continuation text
                if (!$b || !('p' === $b[0] || 'pre' === $b[0] && "" === $b[3][1] || false === $b[0] && 7 === $b[3][0])) {
                    break;
                }
                // <https://spec.commonmark.org/0.31.2#paragraph-continuation-text>
                $s .= "\n" . \substr($value, $i, $m[1]);
                $i += $m[1] + $m[2];
                // End of the stream
                if (0 === $m[2]) {
                    break;
                }
            }
            $deep > 0 && !isset($lot[1][$k[0]]) && ($lot[1][$k[0]] = [$k[2], s($s, ' ')]);
            $s = "";
            continue;
        }
        if ("" === $s && '[' === $value[$n = $d + $i] && '^' === ($value[$n + 1] ?? 0) && ($k = k($value, $n, $limit)) && ':' === ($value[$n + $k[1]] ?? 0)) {
            // <https://spec.commonmark.org/0.31.2#matches>
            $k[0] = \defined("\\MB_CASE_FOLD") ? \mb_convert_case($k[0], \MB_CASE_FOLD, 'UTF-8') : \strtolower($k[0]);
            $n += $k[1] + 1;
            $n += \strspn($value, c1, $n);
            // Refresh the cursor in case the label spans multiple line(s)
            $m = m($value, $i = $n, $limit);
            $s = \substr($value, $i, $m[1]);
            $i += $m[1] + $m[2];
            while ($i < $limit) {
                $m = m($value, $i, $limit);
                if ($m[1] === \strspn($value, c1, $i, $m[1])) {
                    $s .= "\n";
                    $i += $m[1] + $m[2];
                    continue;
                }
                // Current line is an indented line
                if (d($value, $i, $limit)[0]) {
                    $s .= "\n" . \substr($value, $i, $m[1]);
                    $i += $m[1] + $m[2];
                    continue;
                }
                // Previous line was a blank line
                if ("" !== $s && "\n" === $s[\strlen($s) - 1]) {
                    $s = \substr($s, 0, -1);
                    ++$void;
                    break;
                }
                $b = rows($value, $lot, 0, $i, $i + $m[1])[0][0] ?? 0;
                // Not a paragraph continuation text
                if (!$b || !('p' === $b[0] || 'pre' === $b[0] && "" === $b[3][1] || false === $b[0] && 7 === $b[3][0])) {
                    break;
                }
                // <https://spec.commonmark.org/0.31.2#paragraph-continuation-text>
                $s .= "\n" . \substr($value, $i, $m[1]);
                $i += $m[1] + $m[2];
                // End of the stream
                if (0 === $m[2]) {
                    break;
                }
            }
            $deep > 0 && !isset($lot[2][$k[0]]) && ($lot[2][$k[0]] = $s);
            $s = "";
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#link-reference-definition>
        if ("" === $s && '[' === $value[$n = $d + $i] && ($k = k($value, $n, $limit)) && ':' === ($value[$n + $k[1]] ?? 0)) {
            // <https://spec.commonmark.org/0.31.2#matches>
            $k[0] = \defined("\\MB_CASE_FOLD") ? \mb_convert_case($k[0], \MB_CASE_FOLD, 'UTF-8') : \strtolower($k[0]);
            $n += $k[1] + 1;
            $n += \strspn($value, c1, $n);
            if ($r = r($value, $n, $limit)) {
                $n += $r;
            }
            $v = [1 => null];
            $w = \strspn($value, c1, $n);
            // <https://spec.commonmark.org/0.31.2#link-destination>
            if ('<' === ($value[$n + $w] ?? 0)) {
                // Make sure it is not an HTML block other than type 7
                if ($r && ($b = rows($value, $lot, 0, $n, $n + \strcspn($value, c2, $n))[0][0] ?? 0)) {
                    if (false === $b[0] && 7 !== $b[3][0]) {
                        $s .= \substr($value, $i, $m[1]) . "\n";
                        $i += $m[1] + $m[2];
                        continue;
                    }
                }
                $eat = ++$n + $w;
                while ($n + $w < $limit) {
                    $n += \strcspn($value, c2 . "\\>", $n + $w);
                    if ($n + $w >= $limit || "\\" !== $value[$n + $w]) {
                        break;
                    }
                    $n += 2;
                }
                if ('>' === ($value[$n + $w] ?? 0)) {
                    $v[0] = u(v(\substr($value, $eat, ($n += $w) - $eat)));
                    ++$n;
                }
            } else if ($eat = \strcspn($value, c3 . c17, $n + $w)) {
                $v[0] = u(\substr($value, $n + $w, $eat));
                $n += $eat + $w;
            }
            if (isset($v[0]) && ($r = r($value, $n, $limit))) {
                $n += $r;
                // A blank line ends the current block
                if (r($value, $n + \strspn($value, c1, $n), $limit)) {
                    $deep > 0 && ($lot[0][$k[0]] = $v);
                    $i = $n;
                    continue;
                }
            }
            // <https://spec.commonmark.org/0.31.2#link-title>
            $w = \strspn($value, c1, $n);
            if (isset($v[0]) && ($r || $w)) {
                $q = $value[$n + $w] ?? 0;
                if ('"' === $q || "'" === $q || '(' === $q) {
                    $eat = ++$n + $w;
                    $q = '(' === $q ? ')' : $q;
                    while ($n + $w < $limit) {
                        $n += \strcspn($value, "\\" . $q, $n + $w);
                        if ($n + $w >= $limit || "\\" !== $value[$n + $w]) {
                            break;
                        }
                        $n += 2;
                    }
                    if ($n + $w >= $limit || $q !== $value[$n + $w]) {
                        break;
                    }
                    $v[1] = v(\substr($value, $eat, ($n += $w) - $eat));
                    ++$n;
                }
                if (isset($v[1]) && ($r = r($value, $n, $limit))) {
                    $n += $r;
                    // A blank line ends the current block
                    if (r($value, $n + \strspn($value, c1, $n), $limit)) {
                        $deep > 0 && ($lot[0][$k[0]] = $v);
                        $i = $n;
                        continue;
                    }
                }
            }
            // Check for attribute syntax after link reference definition
            $w = \strspn($value, c1, $n);
            if (isset($v[0]) && ($r || $w)) {
                if ('{' === ($value[$n + $w] ?? 0) && ($a = a($value, $n + $w, $limit))) {
                    $v[2] = $a[0];
                    $n += $a[1] + $w;
                }
                if (isset($v[2]) && ($r = r($value, $n, $limit))) {
                    $n += $r;
                    // A blank line ends the current block
                    if (r($value, $n + \strspn($value, c1, $n), $limit)) {
                        $deep > 0 && ($lot[0][$k[0]] = $v);
                        $i = $n;
                        continue;
                    }
                }
            }
            if (isset($v[0]) && ($r || $n >= $limit)) {
                $deep > 0 && !isset($lot[0][$k[0]]) && ($lot[0][$k[0]] = $v);
                $i = $n;
                continue;
            }
            $s .= \substr($value, $i, $m[1]) . "\n";
            $i += $m[1] + $m[2];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#code-fence>
        if (false !== \strpos('`~', $c = $value[$d + $i]) && ($min = \strspn($value, $c, $d + $i)) >= 3) {
            // <https://spec.commonmark.org/0.31.2#info-string>
            $info = \trim(\substr($value, $d + $i + $min, $m[1] - $d - $min));
            // <https://spec.commonmark.org/0.31.2#example-145>
            if ('`' === $c && false !== \strpos($info, $c)) {
                $s .= \substr($value, $i, $m[1]) . "\n";
                $i += $m[1] + $m[2];
                continue;
            }
            "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
            $i += $m[1] + $m[2];
            while ($i < $limit) {
                $m = m($value, $i, $limit);
                $w = \strspn($value, ' ', $i);
                // End of the block
                if ($w < 4 && \strspn($value, $c, $i + $w) >= $min) {
                    $i += $m[1] + $m[2];
                    break;
                }
                // <https://spec.commonmark.org/0.31.2#example-131>
                // <https://spec.commonmark.org/0.31.2#example-132>
                // <https://spec.commonmark.org/0.31.2#example-133>
                $w = w($value, $i, $d);
                $s .= $w[1] . \substr($value, $i + $w[0], $m[1] - $w[0]) . "\n";
                $i += $m[1] + $m[2];
                // End of the stream
                if (0 === $m[2]) {
                    break;
                }
            }
            $rows[] = ['pre', [['code', h($s), a($info, 0, \strlen($info), '{' !== ($info[0] ?? 0), 'language-%s')[0] ?? []]], [], [$min, $c]];
            $s = "";
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#setext-heading>
        // This must come before the list and the thematic break parser because it uses `-` for heading level 2.
        // Since `-` can also be used as a list or thematic break marker, it is necessary to verify that the
        // previously identified block is a paragraph that is not followed by any blank line(s). Any other case is
        // considered invalid and will therefore fall through the list or thematic break parser.
        if (false !== \strpos('-=', $c = $value[$n = $d + $i]) && "" !== $s) {
            $a = [];
            $eat = $n + \strspn($value, $c, $n);
            $eat += \strspn($value, c1, $eat);
            if (!r($value, $eat, $limit) && ($a = a($value, $eat, $eat + \strcspn($value, c2, $eat), '{' !== ($value[$eat] ?? 0)))) {
                $eat += $a[1];
            }
            $eat += \strspn($value, c1, $eat);
            if ($eat !== $i + $m[1]) {
                $s .= \substr($value, $i, $m[1]) . "\n";
                $i += $m[1] + $m[2];
                continue;
            }
            $s = \trim($s);
            if (!$a && ($start = \strrpos($s, '{'))) {
                for ($x = 0; $start - 1 - $x >= 0 && "\\" === $s[$start - 1 - $x]; ++$x);
                $n = $start - 1 - $x;
                if ($n >= 0 && 0 === $x % 2 && false !== \strpos(c1, $s[$n])) {
                    if ($a = a($s, $start, $max = \strlen($s))) {
                        if ($max === $start + $a[1]) {
                            $s = \substr($s, 0, $start - 1);
                        } else {
                            $a = []; // Broken attribute syntax
                        }
                    }
                }
            }
            $rows[] = ['h' . ('-' === $c ? 2 : 1), $s, $a[0] ?? [], ['-' === $c ? 2 : 1, $c]];
            $i += $m[1] + $m[2];
            $s = "";
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#thematic-break>
        // This must come before the list parser. Since `-` can also be used as a thematic break marker where the
        // next character is allowed to be a white-space, it is necessary to verify that the current line contains
        // more than 2 `-`, and consists solely of `-` and white-space(s). Any other combination is considered
        // invalid and will therefore fall through the list parser.
        if (false !== \strpos('*-_', $c = $value[$d + $i]) && \strspn($value, c1 . $c, $i, $limit - $i) === $m[1] && ($n = \substr_count($value, $c, $i, $m[1])) >= 3) {
            "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
            $rows[] = ['hr', false, [], [$n, $c]];
            $i += $m[1] + $m[2];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#atx-heading>
        if (($level = \strspn($value, '#', $d + $i)) && $level < 7 && false !== \strpos(c3, $value[$n = $d + $i + $level] ?? c2[0])) {
            "" !== $s && ($rows[] = ['p', \trim($s), []]);
            $s = \trim(\substr($value, $n + \strspn($value, c1, $n), $m[1] - $level));
            if ($max = \strlen($s)) {
                $a = [];
                if (false !== ($start = \strrpos($s, '{'))) {
                    for ($n = $start - 1, $o = 0; $n >= 0 && "\\" === $s[$n]; --$n, ++$o);
                    if ($n >= 0 && 0 === $o % 2 && false !== \strpos(c1 . '#', $s[$n])) {
                        if ('#' === $s[$n]) {
                            for ($h = 0; $n >= 0 && '#' === $s[$n]; ++$h, --$n);
                            for ($x = 0; $n >= 0 && "\\" === $s[$n]; --$n, ++$x);
                            if (0 !== $x % 2 || ($n >= 0 && false === \strpos(c1, $s[$n]))) {
                                $n = -1; // Invalid trailing hash(es)
                            }
                        }
                        if ($n >= 0 && ($a = a($s, $start, $max))) {
                            if ($max === $start + $a[1]) {
                                $max = \strlen($s = \substr($s, 0, $start - 1));
                            }
                        }
                    }
                }
                if ('#' === $s[$max - 1]) {
                    for ($n = $max - 1; $n >= 0 && '#' === $s[$n]; --$n);
                    for ($x = 0; $n >= 0 && "\\" === $s[$n]; --$n, ++$x);
                    if (0 === $x % 2 && ($n < 0 || false !== \strpos(c1, $s[$n]))) {
                        $s = \trim(\substr($s, 0, $n + 1));
                    }
                }
            }
            $rows[] = ['h' . $level, \trim($s), $a[0] ?? [], [$level, '#']];
            $i += $m[1] + $m[2];
            $s = "";
            continue;
        }
        $s .= \substr($value, $i, $m[1]) . "\n";
        $i += $m[1] + $m[2];
    }
    if ("" !== $s) {
        $rows[] = ['p', \trim($s), []];
    }
    if ($deep > 0 && $rows) {
        foreach ($rows as &$row) {
            if ('blockquote' === $row[0]) {
                $row[1] = rows($row[1], $lot, $deep - 1, 0, \strlen($row[1]))[0] ?: "";
                continue;
            }
            if ('dl' === $row[0]) {}
            if (\in_array($row[0], ['ol', 'ul'], true)) {}
            if (false !== $row[0] && \is_string($row[1])) {
                $row[1] = row($row[1], $lot, $deep - 1, 0, \strlen($row[1]));
            }
        }
        unset($row);
    }
    return [$rows, $lot, $void];
}

function s(string $text, $join = false) {
    $i = 0;
    $limit = \strlen($text);
    $r = [];
    while ($i < $limit) {
        $i += \strspn($text, c3, $i);
        if ($i >= $limit) {
            break;
        }
        $r[] = \substr($text, $i, $n = \strcspn($text, c3, $i));
        $i += $n;
    }
    return false !== $join ? \implode($join, $r) : $r;
}

function u(string $text) {
    $limit = \strlen($text);
    $raw = c14 . c15;
    $s = "";
    for ($i = 0; $i < $limit; ++$i) {
        $c = $text[$i];
        if ('%' === $c && $i + 2 < $limit && false !== \strpos(c7, $text[$i + 1]) && false !== \strpos(c7, $text[$i + 2])) {
            $s .= $c . $text[$i + 1] . $text[$i + 2];
            $i += 2;
            continue;
        }
        $s .= false !== \strpos($raw, $c) ? $c : '%' . \strtoupper(\bin2hex($c));
    }
    return $s;
}

function v(string $text) {
    // <https://spec.commonmark.org/0.31.2#ascii-punctuation-character>
    // <https://spec.commonmark.org/0.31.2#example-12>
    static $r = [
        "\\'" => "'", "\\\\" => "\\",
        '\!' => '!', '\"' => '"', '\#' => '#', '\$' => '$', '\%' => '%', '\&' => '&', '\(' => '(', '\)' => ')',
        '\*' => '*', '\+' => '+', '\,' => ',', '\-' => '-', '\.' => '.', '\/' => '/', '\:' => ':', '\;' => ';',
        '\<' => '<', '\=' => '=', '\>' => '>', '\?' => '?', '\@' => '@', '\[' => '[', '\]' => ']', '\^' => '^',
        '\_' => '_', '\`' => '`', '\{' => '{', '\|' => '|', '\}' => '}', '\~' => '~'
    ];
    return \strtr($text, $r);
}

function w(string $value, int $i, int $max = 4, int $d = 0) {
    $n = 0;
    $start = $i;
    while ($n < $max) {
        $c = $value[$i] ?? 0;
        if (' ' === $c) {
            ++$d;
            ++$i;
            ++$n;
            continue;
        }
        if ("\t" === $c) {
            $w = 4 - ($d % 4);
            if ($n + $w > $max) {
                ++$i;
                return [$i - $start, \str_repeat(' ', ($n + $w) - $max), 0];
            }
            $d += $w;
            $n += $w;
            ++$i;
            continue;
        }
        break;
    }
    return [$i - $start, "", $max - $n];
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
body > main > div > div,
body > main > div > pre {
  border: 2px solid #000;
  flex: 1;
  padding: 0.25em 0.35em;
}
body > main > div > pre {
  background: #ffc;
  border-width: 1px;
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
        $r .= '<div>';
        $r .= view_result(x\markdown\from($raws));
        $r .= '</div>';
    } else if ('source' === $view) {
        $r .= '<pre>';
        $r .= '<code>';
        // $r .= view_source(x\markdown\from($raws));
        $lot = [];
        $r .= view_raw("<?php\n\nreturn " . export(rows($raws, $lot, 25, 0, \strlen($raws))) . ';');
        $r .= '</code>';
        $r .= '</pre>';
    } else {
        $r .= '<pre>';
        $r .= '<code>';
        $r .= view_raw("<?php\n\nreturn " . export(x\markdown\from\raws($raws)) . ';');
        $r .= '</code>';
        $r .= '</pre>';
    }
    $r .= '</div>';
}

$r .= '</main>';

$r .= '</body>';
$r .= '</html>';

echo $r;