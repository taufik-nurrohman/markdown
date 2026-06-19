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
        break; // Stop at character that is not a white-space
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
                "" !== $s && ($rows[] = ['p', \substr($s, 0, -1), []]) && ($s = "");
                $i += $n;
                ++$void;
                continue;
            }
            $d = d($value, $i, $limit)[0];
            // <https://spec.commonmark.org/0.31.2#indented-code-block>
            if ($d >= 4) {
                // <https://spec.commonmark.org/0.31.2#example-113>
                if ("" !== $s) {
                    $s .= $c;
                    continue;
                }
                $n = 0;
                while ($i + $n < $limit) {
                    $prefix = "";
                    $shift = 1;
                    $text = \strcspn($value, "\n\r", $i + $n);
                    if ("\t" !== $value[$i + $n]) {
                        $w = \strspn($value, ' ', $i + $n);
                        if ($w >= 4) {
                            $shift = 4;
                        } else {
                            $shift = $w;
                            if ("\t" === ($value[$i + $n + $shift] ?? 0)) {
                                if (($j = $w + (4 - ($w % 4))) > 4) {
                                    $prefix = \str_repeat(' ', $j - 4);
                                }
                                $shift += 1;
                            }
                        }
                    }
                    if ($text > $shift) {
                        $s .= $prefix . \substr($value, $i + $n + $shift, $text - $shift);
                    }
                    $n += $text;
                    if ($i + $n >= $limit) {
                        break;
                    }
                    if ($r = r($value, $i + $n, $limit)) {
                        $n += $r;
                        $s .= "\n";
                        // Next line is a blank line
                        if ($r = r($value, $i + $n, $limit)) {
                            $n += $r;
                            $s .= "\n";
                            if (d($value, $i + $n, $limit)[0] < 4) {
                                $rows[] = ['pre', h(\substr($s, 0, -2)), [], [4, "\t"]];
                                $s = "";
                                ++$void;
                                --$n;
                                break;
                            }
                        }
                        if (d($value, $i + $n, $limit)[0] < 4) {
                            $rows[] = ['pre', h(\substr($s, 0, -1)), [4, "\t"]];
                            $s = "";
                            --$n;
                            break;
                        }
                    }
                }
                $i += $n;
                "" !== $s && ($rows[] = ['pre', h($s), [], [4, "\t"]]) && ($s = "");
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#html-block>
            // I am so sorry about the order in case you have ADHD. This parser doesn’t start from HTML block type 1
            // through 7. It starts from a block type that’s easier to spot.
            if ('<' === $value[$d + $i]) {
                // Type 2
                if ('<!--' === \substr($value, $d + $i, 4)) {
                    "" !== $s && ($rows[] = ['p', \substr($s, 0, -1), []]) && ($s = "");
                    if (false !== ($n = \strpos($value, '-->', $d + $i + 1))) {
                        $n += \strcspn($value, "\n\r", $n);
                    }
                    $rows[] = [false, \substr($value, $i, $n ?: null), [], [2]];
                    $i += $n;
                    continue;
                }
                // Type 3
                if ('<?' === \substr($value, $d + $i, 2)) {
                    "" !== $s && ($rows[] = ['p', \substr($s, 0, -1), []]) && ($s = "");
                    if (false !== ($n = \strpos($value, '?>', $d + $i + 1))) {
                        $n += \strcspn($value, "\n\r", $n);
                    }
                    $rows[] = [false, \substr($value, $i, $n ?: null), [], [3]];
                    $i += $n;
                    continue;
                }
                // Type 5
                if ('<![CDATA[' === \substr($value, $d + $i, 9)) {
                    "" !== $s && ($rows[] = ['p', \substr($s, 0, -1), []]) && ($s = "");
                    if (false !== ($n = \strpos($value, ']]>', $d + $i + 1))) {
                        $n += \strcspn($value, "\n\r", $n);
                    }
                    $rows[] = [false, \substr($value, $i, $n ?: null), [], [5]];
                    $i += $n;
                    continue;
                }
                // Type 4
                if ('<!' === \substr($value, $d + $i, 2) && \strspn($value, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $d + $i + 2)) {
                    "" !== $s && ($rows[] = ['p', \substr($s, 0, -1), []]) && ($s = "");
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
                    "" !== $s && ($rows[] = ['p', \substr($s, 0, -1), []]) && ($s = "");
                    if (false !== ($n = \stripos($value, '</' . $b . '>', $d + $i + 1))) {
                        $n += \strcspn($value, "\n\r", $n);
                    }
                    $rows[] = [false, \substr($value, $i, $n ?: null), [], [1, $b]];
                    $i += $n;
                    continue;
                }
                // Type 6
                // HTML block type 6 does not differentiate between open and close tag(s). The initial tag does not need
                // to be a valid HTML tag. As long as it starts like one, it is still valid. Even a start tag that looks
                // like `<div <!-- <?asdf`, is still considered a valid HTML block type 6.
                if (isset(b6[$b = \trim($b, '/')])) {
                    "" !== $s && ($rows[] = ['p', \substr($s, 0, -1), []]) && ($s = "");
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
                            $s .= "\n";
                            $test = \strspn($value, " \t", $i + $n);
                            if ($i + $n + $test >= $limit || ($test = r($value, $i + $n + $test, $limit))) {
                                $n += $test;
                                $rows[] = [false, \substr($s, 0, -1), [], [6, $b]];
                                $s = "";
                                ++$void;
                                break;
                            }
                        }
                    }
                    $i += $n;
                    "" !== $s && ($rows[] = [false, $s, [], [6, $b]]) && ($s = "");
                    continue;
                }
                // Type 7
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
                    if ($test = \strspn($value, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $n)) {
                        $n += $test + \strspn($value, '-0123456789', $n);
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
                                    $s .= "\n";
                                    $test = \strspn($value, " \t", $i + $n);
                                    if ($i + $n + $test >= $limit || ($test = r($value, $i + $n + $test))) {
                                        $n += $test;
                                        $rows[] = [false, \substr($s, 0, -1), [], [7]];
                                        $s = "";
                                        ++$void;
                                        break;
                                    }
                                }
                            }
                            $i += $n;
                            "" !== $s && ($rows[] = [false, $s, [], [7]]) && ($s = "");
                            continue;
                        }
                    }
                // <https://spec.commonmark.org/0.31.2#open-tag>
                } else {
                    // <https://spec.commonmark.org/0.31.2#tag-name>
                    if ($test = \strspn($value, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $n)) {
                        $n += $test + \strspn($value, '-0123456789', $n);
                        if ($test = \strspn($value, " \t", $n)) {
                            $n += $test;
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
                                    $s .= "\n";
                                    $test = \strspn($value, " \t", $i + $n);
                                    if ($i + $n + $test >= $limit || ($test = r($value, $i + $n + $test))) {
                                        $n += $test;
                                        $rows[] = [false, \substr($s, 0, -1), [], [7]];
                                        $s = "";
                                        ++$void;
                                        break;
                                    }
                                }
                            }
                            $i += $n;
                            "" !== $s && ($rows[] = [false, $s, [], [7]]) && ($s = "");
                            continue;
                        }
                    }
                }
                $s .= $c;
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#block-quote-marker>
            if ('>' === $value[$d + $i]) {
                "" !== $s && ($rows[] = ['p', \substr($s, 0, -1), []]) && ($s = "");
                $i += ($dd = $d + 1); // Start after `>`
                if (' ' === ($value[$i] ?? 0)) {
                    ++$dd;
                    ++$i;
                }
                if ("\t" === ($value[$i] ?? 0)) {
                    if ($tab = 4 - ($dd % 4)) {
                        $s .= \str_repeat(' ', $tab - 1);
                        ++$i;
                    }
                }
                if ($i >= $limit) {
                    $rows[] = ['blockquote', "", []]; // An empty block quote
                    break;
                }
                $n = 0;
                while ($i + $n < $limit) {
                    $bar = \strcspn($value, "\n\r", $i + $n);
                    $s .= \substr($value, $i + $n, $bar);
                    $n += $bar;
                    if ($i + $n >= $limit) {
                        break;
                    }
                    if ($r = r($value, $i + $n, $limit)) {
                        $ii = $i + $n + $r + \strspn($value, " \t", $i + $n + $r);
                        $s .= "\n";
                        $dd = d($value, $i + $n + $r, $limit)[0];
                        if ($dd < 4 && '>' === $value[$ii]) {
                            $ddd = $dd + 1;
                            ++$ii;
                            if (' ' === ($value[$ii] ?? 0)) {
                                ++$ddd;
                                ++$ii;
                            }
                            if ("\t" === ($value[$ii] ?? 0)) {
                                if ($tab = 4 - ($ddd % 4)) {
                                    $s .= \str_repeat(' ', $tab - 1);
                                    ++$ii;
                                }
                            }
                            $bar = \strcspn($value, "\n\r", $ii);
                            $n += ($ii - ($i + $n)) + $bar;
                            $s .= \substr($value, $ii, $bar);
                            continue;
                        }
                        $b = rows($value, $lot, 0, $i + $n, $i + $n + $r + \strcspn($value, "\n\r", $i + $n + $r))[0][0] ?? 0;
                        // <https://spec.commonmark.org/0.31.2#paragraph-continuation-text>
                        if ($b && ('p' === $b[0] || 'pre' === $b[0] && "\t" === $b[3][1] || false === $b[0] && 7 === $b[3][0])) {
                            $bar = \strcspn($value, "\n\r", $i + $n);
                            $s .= \substr($value, $i + $n, $bar);
                            $n += $bar + 1;
                            continue;
                        }
                        // A blank line or other block that is not a paragraph continuation text ends the block quote
                        $rows[] = ['blockquote', \substr($s, 0, -1), []];
                        $s = "";
                        break;
                    }
                }
                $i += $n;
                "" !== $s && ($rows[] = ['blockquote', $s, []]) && ($s = "");
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#setext-heading>
            // This must come before the list and the thematic break parser because it uses `-` for heading level 2.
            // Since `-` can also be used as a list or thematic break marker, it is necessary to verify that the
            // previously identified block is a paragraph that is not followed by any blank line(s). Any other case is
            // considered invalid and will therefore fall through the list or thematic break parser.
            if (false !== \strpos('-=', $m = $value[$d + $i]) && "" !== $s && \strspn($value, $m, $d + $i) === ($bar = \strcspn($value, "\n\r", $d + $i))) {
                $rows[] = ['h' . ('-' === $m ? 2 : 1), \substr($s, 0, -1), [], ['-' === $m ? 2 : 1, $m]];
                $i += $bar;
                $s = "";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#thematic-break>
            // This must come before the list parser. Since `-` can also be used as a thematic break marker where the
            // next character is allowed to be a white-space, it is necessary to verify that the current line contains
            // more than two `-`, and consists solely of `-` and white-space(s). Any other combination is considered
            // invalid and will therefore fall through the list parser.
            if (false !== \strpos('*-_', $m = $value[$d + $i]) && \strspn($value, $m . " \t", $d + $i) === ($bar = \strcspn($value, "\n\r", $d + $i)) && ($n = \substr_count($value, $m, $d + $i, $bar)) >= 3) {
                "" !== $s && ($rows[] = ['p', \substr($s, 0, -1), []]) && ($s = "");
                $rows[] = ['hr', false, [], [$n, $m]];
                $i += $n;
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
                $i += $n;
                "" !== $s && ($rows[] = ['ul', $s, [], [1, $m]]) && ($s = "");
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#ordered-list-marker>
            if (($n = \strspn($value, '0123456789', $d + $i)) && $n < 10 && false !== \strpos(').', $m1 = $value[$d + $i + $n]) && ($w = \strspn($value, " \n\r\t", $d + $i + $n + 1))) {
                $start = (int) ($m = \substr($value, $d + $i, $n));
                if (1 !== $start && "" !== $s) {
                    $s .= $c;
                    continue;
                }
                "" !== $s && ($rows[] = ['p', \substr($s, 0, -1), []]) && ($s = "");
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
                $i += $n;
                "" !== $s && ($rows[] = ['ol', $s, ['start' => $start]]) && ($s = "");
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#atx-heading>
            if (($n = \strspn($value, '#', $d + $i)) && $n < 7) {
                if (false !== \strpos(" \n\r\t", $value[$d + $i + $n] ?? "\n")) {
                    "" !== $s && ($rows[] = ['p', \substr($s, 0, -1), []]) && ($s = "");
                    $rows[] = ['h' . $n, \substr($value, $i += $d + $n + \strspn($value, " \t", $d + $i + $n), $j = \strcspn($value, "\n\r", $i)), [], [$n, '#']];
                    $i += $j;
                    continue;
                }
                $s .= $c;
                continue;
            }
        }
        $s .= $c;
    }
    if ("" !== $s) {
        $rows[] = ['p', $s, []];
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
pre {
  tab-size: 4;
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
    $r .= strtr(htmlspecialchars($raws), [
        "\n" => "<span class=\"c c-n\">\n</span>",
        "\r" => "<span class=\"c c-r\"></span>",
        "\t" => "<span class=\"c c-t\">\t</span>",
        ' ' => "<span class=\"c c-s\"> </span>"
    ]);
    $r .= '</pre>';
    if ('result' === $view) {
        $r .= '<div>';
        $r .= view_result(x\markdown\from($raws));
        $r .= '</div>';
    } else if ('source' === $view) {
        $r .= '<pre>';
        // $r .= view_source(x\markdown\from($raws));
        $lot = [];
        $r .= view_raw("<?php\n\nreturn " . export(rows($raws, $lot, 25, 0, \strlen($raws))) . ';');
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