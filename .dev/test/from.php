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

function dent($value, $dent) {
    $r = "";
    foreach (explode("\n", $value) as $k => $v) {
        $r .= "\n" . (0 !== $k && "" !== $v ? $dent . $v : $v);
    }
    return substr($r, 1);
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
function a(string $value, int $i, int $limit, int $z = 1) {
    if ($i >= $limit) {
        return [];
    }
    if ($z && '{' !== $value[$i]) {
        return [];
    }
    $n = $z + \strspn($value, " \t", $i + $z);
    $r = [];
    while ($i + $n < $limit) {
        $c = $value[$i + $n];
        if ($z && '}' === $c) {
            if (\is_array($a = $r['class'] ?? 0)) {
                \ksort($a);
                $r['class'] = \implode(' ', \array_keys($a));
            }
            return [$r, $n + 1];
        }
        if ('#' === $c) {
            ++$n; // Move past `#`
            $peek = \strcspn($value, " \t#'.=`{}" . '"', $i + $n);
            if (isset($r['id'])) {
                $n += $peek;
                continue;
            }
            if ("" === ($s = \substr($value, $i + $n, $peek))) {
                return [];
            }
            $r['id'] = $s;
            $n += $peek;
            continue;
        }
        if ('.' === $c) {
            ++$n; // Move past `#`
            $peek = \strcspn($value, " \t#'.=`{}" . '"', $i + $n);
            if (\is_string($r['class'] ?? 0)) {
                $n += $peek;
                continue;
            }
            if ("" === ($s = \substr($value, $i + $n, $peek))) {
                return [];
            }
            $r['class'][$s] = 1;
            $n += $peek;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#attribute-name>
        if ($peek = \strspn($value, ':_ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $i + $n)) {
            $peek += \strspn($value, '.:_ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-0123456789', $i + $n + $peek);
            $k = \substr($value, $i + $n, $peek);
            if ($z) {
                if (!$exist = ('class' === $k || 'id' === $k) && isset($r[$k])) {
                    $r[$k] = true;
                }
            } else {
                $r['class']['language-' . $k] = true;
            }
            $n += $peek;
            // <https://spec.commonmark.org/0.31.2#attribute-value-specification>
            $n += \strspn($value, " \t", $i + $n);
            if ('=' === ($value[$i + $n] ?? 0)) {
                ++$n; // Move past `=`
                $exist || ($r[$k] = "");
                $n += \strspn($value, " \t", $i + $n);
                $q = ($value[$i + $n] ?? 0);
                // <https://spec.commonmark.org/0.31.2#attribute-value>
                // <https://spec.commonmark.org/0.31.2#double-quoted-attribute-value>
                // <https://spec.commonmark.org/0.31.2#single-quoted-attribute-value>
                if ('"' === $q || "'" === $q) {
                    ++$n; // Enter value
                    if (false === ($peek = \strpos($value, $q, $i + $n)) || $peek >= $limit - 1) {
                        return [];
                    }
                    $exist || ($r[$k] = \substr($value, $i + $n, $peek - ($i + $n)));
                    $n += $peek - ($i + $n) + 1; // Exit value
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#unquoted-attribute-value>
                // With the exception that `<` and `>` are allowed but `{` and `}` are not, because attribute syntax is
                // surrounded by `{` and `}`, unlike HTML attribute(s), which are always written inside of `<` and `>`.
                if ($peek = \strcspn($value, " \t'=`{}" . '"', $i + $n)) {
                    $exist || ($r[$k] = \substr($value, $i + $n, $peek));
                    $n += $peek;
                }
                continue;
            }
            $i += $n;
            $n = \strspn($value, " \t", $i);
            continue;
        }
        ++$n;
    }
    if ($z && '}' !== ($value[$i + $n - 1] ?? 0)) {
        return [];
    }
    if (\is_array($a = $r['class'] ?? 0)) {
        \ksort($a);
        $r['class'] = \implode(' ', \array_keys($a));
    }
    return [$r, $n];
}

function d(string $value, int $i, int $limit) {
    if (' ' !== $value[$i] && "\t" !== $value[$i]) {
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
        break; // Stop at the first non-white-space character
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

function row(string $value, array &$lot = [], int $deep = 0, int $at, int $limit) {
    if ($deep < 1) {
        return $value;
    }
    $row = [];
    $s = "";
    for ($i = $at; $i < $limit; ++$i) {
        $c = $value[$i];
        if ("\\" === $c && $i + 1 < $limit && false !== \strpos('!"#$%&()*+,-./:;<=>?@[]^_`{|}~' . "'\\", $value[$i + 1])) {
            continue;
        }
        if ($r = r($value, $i, $limit)) {
            // <https://spec.commonmark.org/0.31.2#hard-line-break>
            if ("\\" === ($value[$i - 1] ?? 0)) {
                $i += \strspn($value, " \t", $i + $r); // <https://spec.commonmark.org/0.31.2#example-637>
                $row[] = h(\substr($s, 0, -1));
                $row[] = ['br', false, []];
                $s = "";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#hard-line-break>
            if ("\t" === ($value[$i - 1] ?? 0) || (' ' === ($value[$i - 1] ?? 0) && ' ' === ($value[$i - 2] ?? 0))) {
                $i += \strspn($value, " \t", $i + $r); // <https://spec.commonmark.org/0.31.2#example-636>
                $row[] = h(\rtrim($s));
                $row[] = ['br', false, []];
                $s = "";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#softbreak>
            $i += \strspn($value, " \t", $i + $r);
            $s .= ' ';
            continue;
        }
        $s .= $c;
    }
    if ("" !== $s) {
        $row[] = h($s);
    }
    if (1 === \count($row) && \is_string($row[0])) {
        return $row[0];
    }
    return $row ?: "";
}

function rows(string $value, array &$lot = [], int $deep = 0, int $at, int $limit) {
    $lot = \array_replace([[], [], []], $lot);
    if ("" === \trim($value)) {
        return [[], $lot, 0];
    }
    $rows = [];
    $s = "";
    $void = 0;
    for ($i = $at; $i < $limit; ++$i) {
        $c = $value[$i];
        if ($at === $i || r($value, $i - 1, $limit)) {
            // <https://spec.commonmark.org/0.31.2#blank-line>
            $n = \strspn($value, " \t", $i);
            if ($i + $n >= $limit || r($value, $i + $n, $limit)) {
                "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                $i += $n;
                ++$void;
                continue;
            }
            $d = d($value, $i, $limit);
            // A tab, 4 space(s), or less than 4 of space(s) followed by a tab should occupy at minimum 4 character(s)
            // <https://spec.commonmark.org/0.31.2#indented-code-block>
            if ($d[0] >= 4) {
                // An indented code block cannot interrupt a paragraph
                // <https://spec.commonmark.org/0.31.2#example-113>
                if ("" !== $s) {
                    $s .= $c;
                    continue;
                }
                if ("\t" !== $value[$i]) {
                    $x = \min($w = \strspn($value, ' ', $i), 4);
                    if ($x < 4 && "\t" === ($value[$i + $x] ?? 0)) {
                        ++$x;
                        if ($tab = $w + (4 - ($w % 4)) - 4) {
                            $s .= \str_repeat(' ', $tab);
                        }
                    }
                } else {
                    $x = 1; // A tab
                }
                $bar = $n = \strcspn($value, "\n\r", $i);
                $s .= \substr($value, $i + $x, $bar - $x);
                if ($i + $n >= $limit) {
                    $rows[] = ['pre', [['code', h($s . "\n"), []]], [], [0, ""]];
                    $s = "";
                    break;
                }
                while ($r = r($value, $i + $n, $limit)) {
                    $n += $r;
                    $bar = \strcspn($value, "\n\r", $i + $n);
                    if ($bar === \strspn($value, " \t", $i + $n)) {
                        if ("\t" !== $value[$i + $n]) {
                            $x = \min($w = \strspn($value, ' ', $i + $n), 4);
                            if ($x < 4 && "\t" === ($value[$i + $n + $x] ?? 0)) {
                                ++$x;
                                if ($tab = $w + (4 - ($w % 4)) - 4) {
                                    $s .= \str_repeat(' ', $tab);
                                }
                            }
                        } else {
                            $x = 1;
                        }
                        $s .= "\n" . \substr($value, $i + $n + $x, $bar - $x);
                        $n += $bar;
                        continue;
                    }
                    if (d($value, $i + $n, $limit)[0] < 4) {
                        $rows[] = ['pre', [['code', h(\rtrim($s, "\n") . "\n"), []]], [], [0, ""]];
                        $s = "";
                        break;
                    }
                    if ("\t" !== $value[$i + $n]) {
                        $x = \min($w = \strspn($value, ' ', $i + $n), 4);
                        if ($x < 4 && "\t" === ($value[$i + $n + $x] ?? 0)) {
                            ++$x;
                            if ($tab = $w + (4 - ($w % 4)) - 4) {
                                $s .= \str_repeat(' ', $tab);
                            }
                        }
                    } else {
                        $x = 1;
                    }
                    $s .= "\n" . \substr($value, $i + $n + $x, $bar - $x);
                    $n += $bar;
                    continue;
                }
                "" !== $s && ($rows[] = ['pre', [['code', h($s . "\n"), []]], [], [0, ""]]) && ($s = "");
                $i += $n - $r;
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
                // Type 2
                if ('<!--' === \substr($value, $d + $i, 4)) {
                    "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                    if (false !== ($n = \strpos($value, '-->', $d + $i + 1))) {
                        $n += \strcspn($value, "\n\r", $n);
                    }
                    $rows[] = [false, \substr($value, $i, $n ?: null), [], [2]];
                    $i += $n;
                    continue;
                }
                // Type 3
                if ('<?' === \substr($value, $d + $i, 2)) {
                    "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                    if (false !== ($n = \strpos($value, '?>', $d + $i + 1))) {
                        $n += \strcspn($value, "\n\r", $n);
                    }
                    $rows[] = [false, \substr($value, $i, $n ?: null), [], [3]];
                    $i += $n;
                    continue;
                }
                // Type 5
                if ('<![CDATA[' === \substr($value, $d + $i, 9)) {
                    "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                    if (false !== ($n = \strpos($value, ']]>', $d + $i + 1))) {
                        $n += \strcspn($value, "\n\r", $n);
                    }
                    $rows[] = [false, \substr($value, $i, $n ?: null), [], [5]];
                    $i += $n;
                    continue;
                }
                // Type 4
                if ('<!' === \substr($value, $d + $i, 2) && \strspn($value, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $d + $i + 2)) {
                    "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                    if (false !== ($n = \strpos($value, '>', $d + $i + 1))) {
                        $n += \strcspn($value, "\n\r", $n);
                    }
                    $rows[] = [false, \substr($value, $i, $n ?: null), [], [4]];
                    $i += $n;
                    continue;
                }
                $b = \strtolower(\substr($value, $d + $i + 1, \strcspn($value, " \n\r\t>", $d + $i + 1)));
                // Type 1
                if (isset(b1[$b])) {
                    "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                    if (false !== ($n = \stripos($value, '</' . $b . '>', $d + $i + 1))) {
                        $n += \strcspn($value, "\n\r", $n);
                    }
                    $rows[] = [false, \substr($value, $i, $n ?: null), [], [1, $b]];
                    $i += $n;
                    continue;
                }
                // Type 6
                // HTML block type 6 does not treat open and close tags differently. The initial tag does not need to be
                // a valid HTML tag. As long as it starts like one, it will be interpreted as such. Even a start tag
                // that looks like `<div <!— <?asdf` still counts as a valid HTML block type 6.
                if (isset(b6[$b = \trim($b, '/')])) {
                    "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                    $n = 0;
                    while ($i + $n < $limit) {
                        $bar = \strcspn($value, "\n\r", $i + $n);
                        $s .= \substr($value, $i + $n, $bar);
                        $n += $bar;
                        if ($i + $n >= $limit) {
                            break;
                        }
                        if ($r = r($value, $i + $n, $limit)) {
                            $n += $r;
                            $peek = \strspn($value, " \t", $i + $n);
                            $s .= "\n";
                            if ($i + $n + $peek >= $limit) {
                                $rows[] = [false, \substr($s, 0, -1), [], [6, $b]];
                                $s = "";
                                break;
                            }
                            // A blank line ends the current block
                            if (r($value, $i + $n + $peek, $limit)) {
                                $n -= $r;
                                $rows[] = [false, \substr($s, 0, -1), [], [6, $b]];
                                $s = "";
                                break;
                            }
                        }
                    }
                    "" !== $s && ($rows[] = [false, $s, [], [6, $b]]) && ($s = "");
                    $i += $n;
                    continue;
                }
                // Type 7
                // HTML block type 7 cannot interrupt a paragraph
                // <https://spec.commonmark.org/0.31.2#example-187>
                if ("" !== $s) {
                    $s .= $c;
                    continue;
                }
                $n = $d + $i + 1; // Start after `<`
                // <https://spec.commonmark.org/0.31.2#closing-tag>
                if ('/' === $value[$n]) {
                    ++$n; // Start after `/`
                    // <https://spec.commonmark.org/0.31.2#tag-name>
                    if ($peek = \strspn($value, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $n)) {
                        $n += $peek + \strspn($value, '-0123456789', $n);
                        $n += \strspn($value, " \t", $n);
                        if ('>' === $value[$n] && \strspn($value, " \t", $n + 1) === \strcspn($value, "\n\r", $n + 1)) {
                            $n = 0;
                            while ($i + $n < $limit) {
                                $bar = \strcspn($value, "\n\r", $i + $n);
                                $s .= \substr($value, $i + $n, $bar);
                                $n += $bar;
                                if ($i + $n >= $limit) {
                                    break;
                                }
                                if ($r = r($value, $i + $n, $limit)) {
                                    $n += $r;
                                    $peek = \strspn($value, " \t", $i + $n);
                                    $s .= "\n";
                                    if ($i + $n + $peek >= $limit) {
                                        $rows[] = [false, \substr($s, 0, -1), [], [7]];
                                        $s = "";
                                        break;
                                    }
                                    // A blank line ends the current block
                                    if (r($value, $i + $n + $peek, $limit)) {
                                        $n -= $r;
                                        $rows[] = [false, \substr($s, 0, -1), [], [7]];
                                        $s = "";
                                        break;
                                    }
                                }
                            }
                            "" !== $s && ($rows[] = [false, $s, [], [7]]) && ($s = "");
                            $i += $n;
                            continue;
                        }
                    }
                // <https://spec.commonmark.org/0.31.2#open-tag>
                } else {
                    // <https://spec.commonmark.org/0.31.2#tag-name>
                    if ($peek = \strspn($value, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $n)) {
                        $n += $peek + \strspn($value, '-0123456789', $n);
                        if ($peek = \strspn($value, " \t", $n)) {
                            $n += $peek;
                            // TODO: Capture attribute(s)
                        }
                        if ('>' === $value[$n] && \strspn($value, " \t", $n + 1) === \strcspn($value, "\n\r", $n + 1)) {
                            $n = 0;
                            while ($i + $n < $limit) {
                                $bar = \strcspn($value, "\n\r", $i + $n);
                                $s .= \substr($value, $i + $n, $bar);
                                $n += $bar;
                                if ($i + $n >= $limit) {
                                    break;
                                }
                                if ($r = r($value, $i + $n, $limit)) {
                                    $n += $r;
                                    $peek = \strspn($value, " \t", $i + $n);
                                    $s .= "\n";
                                    if ($i + $n + $peek >= $limit) {
                                        $rows[] = [false, \substr($s, 0, -1), [], [7]];
                                        $s = "";
                                        break;
                                    }
                                    // A blank line ends the current block
                                    if (r($value, $i + $n + $peek, $limit)) {
                                        $n -= $r;
                                        $rows[] = [false, \substr($s, 0, -1), [], [7]];
                                        $s = "";
                                        break;
                                    }
                                }
                            }
                            "" !== $s && ($rows[] = [false, $s, [], [7]]) && ($s = "");
                            $i += $n;
                            continue;
                        }
                    }
                }
                $s .= $c;
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#block-quote-marker>
            if ('>' === $value[$d + $i]) {
                "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                $bar = $n = \strcspn($value, "\n\r", $i);
                $x = $d + 1; // Start after `>`
                if (' ' === ($value[$i + $x] ?? 0)) {
                    ++$x;
                }
                if ("\t" === ($value[$i + $x] ?? 0)) {
                    $s .= \str_repeat(' ', (4 - ($x % 4)) - 1);
                    ++$x;
                }
                $s .= \substr($value, $i + $x, $bar - $x);
                if ($i + $n >= $limit) {
                    $rows[] = ['blockquote', $s, []];
                    $s = "";
                    break;
                }
                while ($r = r($value, $i + $n, $limit)) {
                    $n += $r;
                    $bar = \strcspn($value, "\n\r", $i + $n);
                    // A blank line ends the current block
                    if ($bar === \strspn($value, " \t", $i + $n)) {
                        $n -= $r;
                        $rows[] = ['blockquote', $s, []];
                        $s = "";
                        break;
                    }
                    $d = d($value, $i + $n, $limit)[1];
                    if ($d < 4 && '>' === $value[$d + $i + $n]) {
                        $s .= "\n";
                        $x = ($peek = $d + 1); // Start after `>`
                        if (' ' === ($value[$i + $n + $x] ?? 0)) {
                            ++$peek;
                            ++$x;
                        }
                        if ("\t" === ($value[$i + $n + $x] ?? 0)) {
                            $s .= \str_repeat(' ', (4 - ($peek % 4)) - 1);
                            ++$x;
                        }
                        $s .= \substr($value, $i + $n + $x, $bar - $x);
                        $n += $bar;
                        continue;
                    }
                    // <https://spec.commonmark.org/0.31.2#paragraph-continuation-text>
                    $b = rows($value, $lot, 0, $i + $n, $i + $n + \strcspn($value, "\n\r", $i + $n))[0][0] ?? 0;
                    if (!$b || !('p' === $b[0] || 'pre' === $b[0] && "" === $b[3][1] || false === $b[0] && 7 === $b[3][0])) {
                        $n -= $r;
                        break;
                    }
                    // <https://spec.commonmark.org/0.31.2#example-93>
                    // <https://spec.commonmark.org/0.31.2#example-106>
                    if ($d < 4 && ($peek = $d + \strspn($value, '=', $d + $i + $n))) {
                        if ($bar === ($peek += \strspn($value, " \t", $i + $n + $peek))) {
                            $s .= "\n" . \str_repeat(' ', $d) . "\\" . \substr($value, $d + $i + $n, $bar);
                            $n += $bar;
                            continue;
                        }
                    }
                    // <https://spec.commonmark.org/0.31.2#example-235>
                    // <https://spec.commonmark.org/0.31.2#example-236>
                    // <https://spec.commonmark.org/0.31.2#example-250>
                    $b = \end(rows($s, $lot, 0, 0, \strlen($s))[0]) ?: [];
                    if ('blockquote' !== $b[0] && 'p' !== $b[0]) {
                        $n -= $r;
                        break;
                    }
                    $s .= "\n" . \substr($value, $i + $n, $bar);
                    $n += $bar;
                    continue;
                }
                "" !== $s && ($rows[] = ['blockquote', $s, []]) && ($s = "");
                $i += $n;
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#code-fence>
            if (false !== \strpos('`~', $m = $value[$d + $i]) && ($f = \strspn($value, $m, $d + $i)) >= 3) {
                $bar = $n = \strcspn($value, "\n\r", $i);
                // <https://spec.commonmark.org/0.31.2#info-string>
                $rest = \trim(\substr($value, $d + $f + $i, $n - $f));
                $a = $rest ? (a($rest, 0, \strlen($rest), '{' === $rest[0] ? 1 : 0)[0] ?? []) : [];
                // <https://spec.commonmark.org/0.31.2#example-145>
                if ('`' === $m && false !== \strpos($rest, $m)) {
                    $s .= $c;
                    continue;
                }
                "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                while ($r = r($value, $i + $n, $limit)) {
                    $n += $r;
                    $bar = \strcspn($value, "\n\r", $i + $n);
                    if (($w = d($value, $i + $n, $limit)[1]) < 4) {
                        if ($f === ($peek = \strspn($value, $m, $i + $n + $w))) {
                            if ($bar === ($peek += $w + \strspn($value, " \t", $i + $n + $w + $peek))) {
                                $i += $n + $peek;
                                $rows[] = ['pre', [['code', h($s), []]], $a, [$f, $m]];
                                $s = "";
                                continue 2;
                            }
                        }
                    }
                    // <https://spec.commonmark.org/0.31.2#example-131>
                    // <https://spec.commonmark.org/0.31.2#example-132>
                    // <https://spec.commonmark.org/0.31.2#example-133>
                    $peek = $d;
                    while ($peek) {
                        if (' ' === ($value[$i + $n] ?? 0)) {
                            ++$n;
                            --$bar;
                            --$peek;
                            continue;
                        }
                        if ("\t" === ($value[$i + $n] ?? 0)) {
                            ++$n;
                            --$bar;
                            $s .= \str_repeat(' ', 4 - $d);
                            // A tab at the start of a line immediately satisfies the “preceded by up to 3 space(s)
                            // of indentation” rule because it already occupies 4 character(s).
                            break;
                        }
                        // Not a white-space, stop!
                        break;
                    }
                    $s .= \substr($value, $i + $n, $bar) . "\n";
                    $n += $bar;
                    continue;
                }
                $i += $n;
                $rows[] = ['pre', [['code', h($s), []]], $a, [$f, $m]];
                $s = "";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#setext-heading>
            // This must come before the list and the thematic break parser because it uses `-` for heading level 2.
            // Since `-` can also be used as a list or thematic break marker, it is necessary to verify that the
            // previously identified block is a paragraph that is not followed by any blank line(s). Any other case is
            // considered invalid and will therefore fall through the list or thematic break parser.
            if (false !== \strpos('-=', $m = $value[$d + $i]) && "" !== $s) {
                $s = \trim($s);
                if ($peek = \strspn($value, $m, $d + $i)) {
                    $peek += \strspn($value, " \t", $d + $i + $peek);
                    if ($a = a($value, $d + $i + $peek, $limit, '{' === ($value[$d + $i + $peek] ?? 0) ? 1 : 0)) {
                        $peek += $a[1];
                    }
                    $peek += \strspn($value, " \t", $d + $i + $peek);
                    if ($peek === ($bar = \strcspn($value, "\n\r", $d + $i))) {
                        if (!$a && ($start = \strrpos($s, '{'))) {
                            $x = 0;
                            while ($start - 1 - $x >= 0 && "\\" === $s[$start - 1 - $x]) {
                                ++$x;
                            }
                            $n = $start - 1 - $x;
                            if ($n >= 0 && (' ' === $s[$n] || "\t" === $s[$n]) && 0 === $x % 2) {
                                if ($a = a($s, $start, $max = \strlen($s), 1)) {
                                    if ($max === $start + $a[1]) {
                                        $s = \substr($s, 0, $start - 1);
                                    } else {
                                        $a = []; // It has trailing character(s) that are not white-space(s), drop it!
                                    }
                                }
                            }
                        }
                        $rows[] = ['h' . ('-' === $m ? 2 : 1), $s, $a[0] ?? [], ['-' === $m ? 2 : 1, $m]];
                        $i += $bar;
                        $s = "";
                        continue;
                    }
                }
            }
            // <https://spec.commonmark.org/0.31.2#thematic-break>
            // This must come before the list parser. Since `-` can also be used as a thematic break marker where the
            // next character is allowed to be a white-space, it is necessary to verify that the current line contains
            // more than 2 `-`, and consists solely of `-` and white-space(s). Any other combination is considered
            // invalid and will therefore fall through the list parser.
            if (false !== \strpos('*-_', $m = $value[$d + $i]) && \strspn($value, $m . " \t", $i) === ($bar = \strcspn($value, "\n\r", $i)) && ($n = \substr_count($value, $m, $i, $bar)) >= 3) {
                "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                $rows[] = ['hr', false, [], [$n, $m]];
                $i += $bar;
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#bullet-list-marker>
            if (false !== \strpos('*+-', $m = $value[$d + $i]) && ($w = \strspn($value, " \n\r\t", $d + $i + 1))) {
                // The minimum indentation to continue the list is 0 to 3 column(s) of white-space(s), followed by the
                // bullet list marker and a column of white-space.
                $i += ($min = $d + 2);
                $n = 0;
                while ($i + $n < $limit) {
                    $bar = \strcspn($value, "\n\r", $i + $n);
                    $s .= \substr($value, $i + $n, $bar);
                    $n += $bar;
                    if ($i + $n >= $limit) {
                        break;
                    }
                    if ($r = r($value, $i + $n, $limit)) {
                        $n += $r;
                        // A blank line
                        if ($r = r($value, $i + $n + ($w = \strspn($value, " \t", $i + $n)), $limit)) {
                            $n += $r;
                            $s .= "\n";
                            continue;
                        }
                        if (d($value, $i + $n, $limit)[0] >= $min) {
                            $bar = \strcspn($value, "\n\r", $i + $min + $n);
                            $s .= "\n" . \substr($value, $i + $min + $n, $bar);
                            $n += $bar + $min;
                            continue;
                        }
                        $b = rows($value, $lot, 0, $i + $n, $i + $n + \strcspn($value, "\n\r", $i + $n))[0][0] ?? 0;
                        echo json_encode($b);
                        echo '<br>';
                    }
                    "" !== $s && ($rows[] = ['ul', \substr($s, 0, -1), [], [1, $m]]) && ($s = "");
                    --$n; // :(
                    break;
                }
                "" !== $s && ($rows[] = ['ul', $s, [], [1, $m]]) && ($s = "");
                $i += $n;
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#ordered-list-marker>
            if (($n = \strspn($value, '0123456789', $d + $i)) && $n < 10 && false !== \strpos(').', $m1 = $value[$d + $i + $n]) && ($w = \strspn($value, " \n\r\t", $d + $i + $n + 1))) {
                $start = (int) ($m = \substr($value, $d + $i, $n));
                if (1 !== $start && "" !== $s) {
                    $s .= $c;
                    continue;
                }
                "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                $dent = $d + $n + 1 + 1; // TODO: Check for `\t`
                $n = $dent;
                while ($i + $n < $limit) {
                    $text = \strcspn($value, "\n\r", $i + $n);
                    $s .= \substr($value, $i + $n, $text);
                    $n += $text;
                    if ($i + $n >= $limit) {
                        break;
                    }
                    if ($r = r($value, $i + $n, $limit)) {
                        $next_line_start = $i + $n + $r;
                        $next_line_length = \strcspn($value, "\n\r", $next_line_start);
                        $spaces = \strspn($value, " \t", $next_line_start);
                        if ($next_line_length === $spaces) {
                            $s .= "\n";
                            $n += 1 + $next_line_length;
                            continue;
                        }
                        $n2 = \strspn($value, '0123456789', $next_line_start);
                        if ($n2 && $n2 < 10 && $m === ($value[$next_line_start + $n2] ?? 0)) {
                            $next_start = (int) \substr($value, $next_line_start, $n2);
                            if ($next_start >= $start) {
                                $marker_spaces = \strspn($value, ' ', $next_line_start + $n2 + 1); // TODO: Check for `\t`
                                $prefix_length = $n2 + 1 + $marker_spaces;
                                $content_start = $next_line_start + $prefix_length;
                                $content_length = $next_line_length - $prefix_length;
                                $line_content = $content_length > 0 ? \substr($value, $content_start, $content_length) : "";
                                $s .= "\n\x1e" . $line_content;
                                $n += 1 + $next_line_length;
                                continue;
                            }
                        }
                        if (($n2 = \strspn($value, '0123456789', $next_line_start)) && $n2 < 10 && $m === $value[$i + $n2] && ((int) \substr($value, $next_line_start, $n2)) >= $start) {
                            $s .= "\n" . \substr($value, $next_line_start + $n2 + 1 + 1, $next_line_start + $n2 + 1 + 1 + $next_line_length);
                            $n += 1 + $next_line_length;
                            continue;
                        }
                        $next_line_start = $i + $n + 1;
                        $next_line_length = \strcspn($value, "\n\r", $next_line_start);
                        $test = rows($value, $lot, 0, $next_line_start, $next_line_start + $next_line_length);
                        if ('p' === ($test[0][0][0] ?? 0)) {
                            $s .= "\n" . \substr($value, $next_line_start, $next_line_length);
                            $n += 1 + $next_line_length;
                            continue;
                        }
                        $rows[] = ['ol', $s, ['start' => $start]];
                        $s = "";
                        break;
                    }
                    $s .= $c;
                }
                "" !== $s && ($rows[] = ['ol', $s, ['start' => $start]]) && ($s = "");
                $i += $n;
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#atx-heading>
            if (($n = \strspn($value, '#', $d + $i)) && $n < 7 && false !== \strpos(" \n\r\t", $value[$d + $i + $n] ?? "\n")) {
                "" !== $s && ($rows[] = ['p', \trim($s), []]);
                $s = \trim(\substr($value, $i += $d + $n + \strspn($value, " \t", $d + $i + $n), $bar = \strcspn($value, "\n\r", $i)));
                // [$s, $a1] = a1();
                $rows[] = ['h' . $n, \trim($s), [], [$n, '#']];
                $i += $bar;
                $s = "";
                continue;
            }
        }
        $s .= $c;
    }
    if ("" !== $s) {
        $rows[] = ['p', \trim($s), []];
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
:disabled {
  cursor: not-allowed;
  opacity: 0.5;
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