<?php namespace x\markdown;

/*

    !
    #
    *
    +
    `
    ~
    -
    1
    :
    <
    =
    >
    [
    _
    |

*/

function a(string $text) {
    $r = [];
    $s = "";
    $size = \strlen($text = \trim($text) . ' ');
    if ($strict = '{' === $text[0] && '}' === \substr($text, -2, 1) && "\\" !== \substr($text, -3, 1)) {
        $size = \strlen($text = \trim(\substr($text, 1, -2)) . ' ');
    }
    for ($i = 0; $i < $size; ++$i) {
        $c = $text[$i];
        if ("\\" === $c && $i + 1 < $size) {
            $s .= $text[++$i];
            continue;
        }
        if ('=' === $c) {
            $k = $s;
            $s = "";
            if (++$i >= $size) {
                $r[$k] = "";
                break;
            }
            $c = $text[$i];
            $quote = 0;
            if ('"' === $c || "'" === $c) {
                $quote = $c;
                ++$i;
            }
            while ($i < $size) {
                $c = $text[$i];
                if ("\\" === $c && $i + 1 < $size) {
                    $s .= $text[++$i];
                    continue;
                }
                if ($quote) {
                    if ($c === $quote) {
                        break;
                    }
                } else if ("\t" === $c || ' ' === $c) {
                    --$i; // Let outer loop to process white-space(s)
                    break;
                }
                $s .= $c;
                ++$i;
            }
            $r[$k] = $s;
            $s = "";
            continue;
        }
        if ("" === $s && false !== \strpos(" \t", $c)) {
            continue;
        }
        if (false !== \strpos(" \t#.", $c) && "" !== $s) {
            if ("" !== $s) {
                if ('#' === $s[0] && "" !== ($s = \substr($s, 1))) {
                    $r['id'] = $s;
                } else if ('.' === $s[0] && "" !== ($s = \substr($s, 1))) {
                    $r['class'][] = $s;
                } else {
                    // With attribute syntax wrapped by `{` and `}`, attribute name(s) immediately followed by a
                    // white-space or by the end of the syntax will be treated as boolean attribute(s), whereas without
                    // such wrapping, attribute name(s) immediately followed by a white-space or by the end of the
                    // syntax will be treated as class name(s) that will be preceded by the `language-` prefix. This
                    // applies, in particular, to attribute sequence(s) in fenced code block syntax.
                    if ($strict) {
                        $r[$s] = true; // `{asdf-1 asdf-2}`
                    } else {
                        $r['class'][] = 'language-' . $s; // `asdf-1 asdf-2`
                    }
                }
            }
            $s = false !== \strpos(" \t", $c) ? "" : $c;
            continue;
        }
        $s .= $c;
    }
    if (!empty($r['class'])) {
        \sort($r['class']);
        $r['class'] = \implode(' ', \array_unique($r['class']));
    }
    \ksort($r);
    return $r;
}

function a1(string $row) {
    $r = [$row, []];
    if (false === \strpos($row, '{')) {
        return $r;
    }
    if ('}' !== \substr($row, -1) || "\\" === \substr($row, -2, 1)) {
        return $r;
    }
    $at = false;
    $level = 1;
    $limit = \strlen($row);
    for ($i = $limit - 2; $i >= 0; --$i) {
        $c = $row[$i];
        $slash = 0;
        for ($j = $i - 1; $j >= 0 && "\\" === $row[$j]; --$j) {
            $slash++;
        }
        if (0 !== $slash % 2) {
            continue;
        }
        if ('}' === $c) {
            ++$level;
        } else if ('{' === $c) {
            --$level;
            if (0 === $level) {
                if (0 === $i || false !== \strpos(" \t", $row[$i - 1])) {
                    $at = $i;
                }
                break;
            }
        }
    }
    if (false !== $at) {
        $r[0] = \rtrim(\substr($row, 0, $at));
        $r[1] = a(\substr($row, $at));
    }
    return $r;
}

function from(?string $value, $block = true): ?string {}

function r(string $text) {
    if ('[' !== ($text[0] ?? 0)) {
        return [];
    }
    $attributes = $label = $link = $title = null;
    $i = 1; // Start after `[`
    while (false !== ($n = \strpos($text, ']:', $i))) {
        if ($n > 1 && "\\" === $text[$n - 1]) {
            $i = $n + 2;
            continue;
        }
        break;
    }
    if (false === $n) {
        return [];
    }
    $label = \trim(\substr($text, 1, $n - 1));
    $text = \trim(\substr($text, $n + 2));
    if ("" === $text) {
        return [];
    }
    if ('<' === $text[0]) {
        if (false === ($n = \strpos($text, '>'))) {
            return [];
        }
        $link = \substr($text, 0, $n + 1);
        $text = \substr($text, $n + 1);
    } else {
        $link = \substr($text, 0, $n = \strcspn($text, " \n\t"));
        $text = \substr($text, $n);
    }
    if ($text && !($n = \strspn($text, " \n\t"))) {
        return [];
    }
    if ("" !== ($text = \substr($text, $n))) {
        $open = $text[0];
        if ("'" === $open || '"' === $open || '(' === $open) {
            $close = '(' === $open ? ')' : $open;
            $p = 1;
            while (false !== ($end = \strpos($text, $close, $p))) {
                if ('\\' === $text[$end - 1]) {
                    $p = $end + 1;
                    continue;
                }
                break;
            }
            if (false === $end) {
                return [];
            }
            $title = v(\substr($text, 1, $end - 1));
            $text = \substr($text, $end + 1);
        }
        if ($text && !($n = \strspn($text, " \n\t"))) {
            return [];
        }
        if ("" !== ($text = \substr($text, $n))) {
            $attributes = \trim($text);
            if ('{' !== $attributes[0] && '}' !== \substr($attributes, -1)) {
                return [];
            }
        }
    }
    return [s1(v($label)), $link, $title, a($attributes ?? "")];
}

function row(string $text, array &$lot = []) {}

function rows(string $text, array &$lot = []) {
    static $blocks, $c1, $c2, $c3, $c4, $c5, $c6;
    $blocks || ($blocks = [
        1 => \array_fill_keys(['pre', 'script', 'style', 'textarea'], 1),
        6 => \array_fill_keys([
            'address', 'article', 'aside', 'base', 'basefont', 'blockquote', 'body', 'caption', 'center', 'col', 'colgroup',
            'dd', 'details', 'dialog', 'dir', 'div', 'dl', 'dt', 'fieldset', 'figcaption', 'figure', 'footer', 'form',
            'frame', 'frameset', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hr', 'html', 'iframe', 'legend',
            'li', 'link', 'main', 'menu', 'menuitem', 'nav', 'noframes', 'ol', 'optgroup', 'option', 'p', 'param', 'search',
            'section', 'summary', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'title', 'tr', 'track', 'ul'
        ], 1)
    ]);
    $c1 || ($c1 = 'abcdefghijklmnopqrstuvwxyz');
    $c2 || ($c2 = '0123456789');
    $c3 || ($c3 = '-' . $c2);
    $c4 || ($c4 = $c1 . $c3);
    $c5 || ($c5 = $c1 . ':_');
    $c6 || ($c6 = $c4 . '.:_');
    // List of reference(s), abbreviation(s), and note(s)
    $lot = \array_replace([[], [], []], $lot);
    $r = null;
    $raws = \explode("\n", \rtrim(\strtr($text, [
        "\r\n" => "\n",
        "\r" => "\n"
    ]), "\n") . "\n");
    $rows = [];
    $x = "\x1a";
    foreach ($raws as $raw) {
        if ($d = \strspn($row = s($raw, 4), ' ')) {
            $row = \substr($row, $d > 4 ? 4 : $d);
        }
        if ($r && 0 === $r[0]) {
            if ("" === ($row = \trim($row))) {
                if ($ref = r($r[1])) {
                    $r[1] = "\n";
                    $r[2]['href'] = $ref[1];
                    $r[2]['src'] = $ref[1];
                    $r[2]['title'] = $ref[2];
                    $r[2] = \array_replace($r[2], $ref[3]);
                    $r[4] = [$ref[0]];
                } else {
                    $r[0] = 'p';
                }
                $rows[] = $r;
                $r = null;
                continue;
            }
            if ($ref = r($r[1] . $row)) {
                $r[1] = "\n";
                $r[2]['href'] = $ref[1];
                $r[2]['src'] = $ref[1];
                $r[2]['title'] = $ref[2];
                $r[2] = \array_replace($r[2], $ref[3]);
                $r[4] = [$ref[0]];
                $rows[] = $r;
                $r = null;
                continue;
            }
            if ($ref = r($r[1])) {
                $r[1] = "\n";
                $r[2]['href'] = $ref[1];
                $r[2]['src'] = $ref[1];
                $r[2]['title'] = $ref[2];
                $r[2] = \array_replace($r[2], $ref[3]);
                $r[4] = [$ref[0]];
                $rows[] = $r;
                $r = rows($raw, $lot)[0][0] ?? null;
                continue;
            }
            $now = rows($raw, $lot)[0][0] ?? null;
            if ($now && 'p' === $now[0] && "" !== $now[1]) {
                $r[1] .= $row . "\n";
                continue;
            }
            $r[0] = 'p';
            $rows[] = $r;
            $r = $now;
            continue;
        }
        if ($r && 1 === $r[0]) {
            $now = rows($raw, $lot)[0][0] ?? null;
            if ($now && 'p' === $now[0] && "" !== $now[1]) {
                $r[1] .= $now[1];
                continue;
            }
            $r[1] = \trim($r[1], "\n") . "\n";
            if ('*' === ($r[1][0] ?? 0) && '[' === ($r[1][1] ?? 0)) {
                $final = false;
                $i = 2; // Start after `*[`
                $row = $r[1];
                while (false !== ($n = \strpos($row, ']:', $i))) {
                    if ($n > 2 && "\\" === $row[$n - 1]) {
                        $i = $n + 2;
                        continue;
                    }
                    $final = true;
                    break;
                }
                if ($final) {
                    $k = \substr($row, 0, $n += 2);
                    $key = \trim(\substr($k, 2, -2)); // Remove `*[` and `]:`
                    $value = \trim(\substr($row, $n));
                    $r[1] = $value . "\n";
                    $r[4] = [s1(v($key)), \strlen($k)];
                }
            }
            $rows[] = $r;
            $r = $now;
            continue;
        }
        if ($r && 2 === $r[0]) {}
        if ($r && false === $r[0]) {
            // HTML block type 1
            if (1 === $r[4][0] && isset($blocks[1][$r[4][1]]) && false !== \stripos($row, '</' . $r[4][1] . '>')) {
                $r[1] .= $raw . "\n";
                $rows[] = $r;
                $r = null;
                continue;
            }
            // HTML block type 2, 3, 4, and 5
            if (
                2 === $r[4][0] && false !== \strpos($row, '-->') ||
                3 === $r[4][0] && false !== \strpos($row, '?>') ||
                4 === $r[4][0] && false !== \strpos($row, '>') ||
                5 === $r[4][0] && false !== \strpos($row, ']]>')
            ) {
                $r[1] .= $raw . "\n";
                $rows[] = $r;
                $r = null;
                continue;
            }
            // HTML block type 6, and 7
            if ((6 === $r[4][0] && isset($blocks[6][$r[4][1]]) || 7 === $r[4][0]) && "" === \trim($row)) {
                $rows[] = $r;
                $r = null;
                continue;
            }
            $r[1] .= $raw . "\n";
            continue;
        }
        if ($r && 'blockquote' === $r[0]) {
            $now = rows($row, $lot)[0][0] ?? null;
            if ($now && 'blockquote' === $now[0]) {
                $r[1] .= $now[1];
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#paragraph-continuation-text>
            if ($now && \in_array($now[0], [0, 1, 2, 'p'], true) && "" !== $now[1]) {
                $r[1] .= $raw . "\n";
                continue;
            }
            $rows[] = $r;
            $r = $now;
            continue;
        }
        if ($r && 'pre' === $r[0]) {
            if ('`' === $r[4][1] || '~' === $r[4][1]) {
                $row = s($raw, $r[3]);
                if ($row === \str_repeat($r[4][1], $r[4][0])) {
                    $r[1] = \substr($r[1], 1);
                    $rows[] = $r;
                    $r = null;
                    continue;
                }
                $r[1] .= $row . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-111>
            if ("" === \trim($row)) {
                // <https://spec.commonmark.org/0.31.2#example-112>
                $r[1] .= s($raw, $r[3]) . "\n";
                continue;
            }
            if ($d >= 4) {
                $r[1] .= s($raw, $r[3]) . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-114>
            $rows[] = $r;
            $r = rows($raw, $lot)[0][0] ?? null;
            continue;
        }
        if ($d >= 4 && (!$r || 'p' !== $r[0])) {
            $r = ['pre', $row . "\n", ['class' => false], $d, [1, "\t"]];
            continue;
        }
        $c = $row[0] ?? $x;
        // <https://spec.commonmark.org/0.31.2#atx-heading>
        if ('#' === $c && ($n = \strspn($row, $c)) && $n < 7 && \strspn($row . ' ', " \t", $n)) {
            if ($r && "" !== $r[1]) {
                $rows[] = $r;
            }
            // <https://spec.commonmark.org/0.31.2#example-67>
            $row = \trim(\substr($row, $n));
            $row_test = \rtrim($row, '#');
            // <https://spec.commonmark.org/0.31.2#example-75>
            // <https://spec.commonmark.org/0.31.2#example-76>
            if ($row_test !== $row && "\\" !== \substr($row_test, -1) && false !== \strpos(" \t", \substr($row_test, -1))) {
                $row = \trim($row_test);
            }
            [$row, $a1] = a1($row);
            $rows[] = ['h' . $n, $row . "\n", $a1, $d, [$n, '#']];
            $r = null;
            continue;
        }
        if ('*' === $c && '[' === ($row[1] ?? 0)) {
            $final = false;
            $i = 2; // Start after `*[`
            while (false !== ($n = \strpos($row, ']:', $i))) {
                if ($n > 2 && "\\" === $row[$n - 1]) {
                    $i = $n + 2;
                    continue;
                }
                $final = true;
                break;
            }
            if ($final) {
                if ($r && "" !== $r[1]) {
                    $rows[] = $r;
                }
                $k = \substr($row, 0, $n += 2);
                $key = \trim(\substr($k, 2, -2)); // Remove `*[` and `]:`
                $value = \trim(\substr($row, $n));
                $r = [1, $value . "\n", [], $d, [s1(v($key)), \strlen($k)]];
                continue;
            }
            $r = [1, $row . "\n", [], $d, []];
            continue;
        }
        if (':' === $c) {}
        // <https://spec.commonmark.org/0.31.2#html-block>
        if ('<' === $c && ($block = \substr($row, 1, \strcspn($row, " \t>", 1)))) {
            if ('!' === ($block[0] ?? $x) && '>' !== ($block[1] ?? 0)) {
                // <https://spec.commonmark.org/0.31.2#example-185>
                if ($r && "" !== $r[1]) {
                    $rows[] = $r;
                }
                // <https://spec.commonmark.org/0.31.2#example-179>
                // <https://spec.commonmark.org/0.31.2#html-comment>
                if ('--' === \substr($block, 1, 2)) {
                    $r = [false, $raw . "\n", [], $d, [2]];
                    if (false !== \strpos($row, '-->')) {
                        // Ends on its own line
                        $rows[] = $r;
                        $r = null;
                    }
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#cdata-section>
                // <https://spec.commonmark.org/0.31.2#example-182>
                if ('[CDATA[' === \substr($block, 1, 7)) {
                    $r = [false, $raw . "\n", [], $d, [5]];
                    if (false !== \strpos($row, ']]>')) {
                        // Ends on its own line
                        $rows[] = $r;
                        $r = null;
                    }
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#declaration>
                // <https://spec.commonmark.org/0.31.2#example-181>
                if (\strspn(\strtolower($block), $c1, 1)) {
                    $r = [false, $raw . "\n", [], $d, [4]];
                    if (false !== \strpos($row, '>')) {
                        // Ends on its own line
                        $rows[] = $r;
                        $r = null;
                    }
                    continue;
                }
                $r[1] .= $row . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-180>
            // <https://spec.commonmark.org/0.31.2#processing-instruction>
            if ('?' === ($block[0] ?? $x) && '>' !== ($block[1] ?? 0)) {
                // <https://spec.commonmark.org/0.31.2#example-185>
                if ($r && "" !== $r[1]) {
                    $rows[] = $r;
                }
                $r = [false, $raw . "\n", [], $d, [3]];
                if (false !== \strpos($row, '?>')) {
                    // Ends on its own line
                    $rows[] = $r;
                    $r = null;
                }
                continue;
            }
            // The initial tag doesn’t need to be a valid tag, as long as it starts like one
            if (isset($blocks[1][$block = \strtolower($block)])) {
                // <https://spec.commonmark.org/0.31.2#example-185>
                if ($r && "" !== $r[1]) {
                    $rows[] = $r;
                }
                $r = [false, $raw . "\n", [], $d, [1, $block]];
                if (false !== \stripos($row, '</' . $block . '>')) {
                    // Ends on its own line
                    $rows[] = $r;
                    $r = null;
                }
                continue;
            }
            // HTML block(s) type 6 does not care whether it is an open or close tag
            if (isset($blocks[6][$block_6 = \trim($block, '/')])) {
                // <https://spec.commonmark.org/0.31.2#example-185>
                if ($r && "" !== $r[1]) {
                    $rows[] = $r;
                }
                $r = [false, $raw . "\n", [], $d, [6, $block_6]];
                continue;
            }
            if ('>' === \substr($test = \trim($row), -1)) {
                // <https://spec.commonmark.org/0.31.2#open-tag>
                $open = 1;
                $test = \rtrim(\substr($test, 1, -1));
                // <https://spec.commonmark.org/0.31.2#closing-tag>
                if ('/' === $test[0] ?? $x) {
                    $open = 0;
                    $test = \substr($test, 1);
                }
                // <https://spec.commonmark.org/0.31.2#open-tag>
                if ('/' === \substr($test, -1)) {
                    $open = 2;
                    $test = \substr($test, 0, -1);
                }
                $test = \trim(\strtolower($test));
                // <https://spec.commonmark.org/0.31.2#tag-name>
                if (\strspn($test, $c1) && ($n = \strspn($test, $c4, 1))) {
                    // An opening or closing tag with no attribute(s)
                    if ("" === ($test = \trim(\substr($test, $n + 1)))) {
                        if ($r && "" !== $r[1]) {
                            // <https://spec.commonmark.org/0.31.2#example-187>
                            if (\in_array($r[0], [0, 1, 2, 'blockquote', 'p'], true)) {
                                $r[1] .= $raw . "\n";
                                continue;
                            }
                            $rows[] = $r;
                        }
                        $r = [false, $raw . "\n", [], $d, [7]];
                        continue;
                    }
                    if ($open) {
                        $final = true;
                        $size = \strlen($test);
                        for ($i = 0; $i < $size; ++$i) {
                            while ($i < $size && false !== \strpos(" \t", $test[$i])) {
                                ++$i;
                            }
                            if ($i >= $size) {
                                break;
                            }
                            // <https://spec.commonmark.org/0.31.2#attribute-name>
                            if (false === \strpos($c5, $test[$i])) {
                                $final = false;
                                break;
                            }
                            while ($i < $size && false !== \strpos($c6, $test[$i])) {
                                ++$i;
                            }
                            // <https://spec.commonmark.org/0.31.2#attribute-value-specification>
                            while ($i < $size && false !== \strpos(" \t", $test[$i])) {
                                ++$i;
                            }
                            // <https://spec.commonmark.org/0.31.2#attribute-value>
                            if ($i < $size && '=' === $test[$i]) {
                                ++$i;
                                // <https://spec.commonmark.org/0.31.2#attribute-value-specification>
                                while ($i < $size && false !== \strpos(" \t", $test[$i])) {
                                    ++$i;
                                }
                                if ($i >= $size) {
                                    $final = false;
                                    break;
                                }
                                $quote = $test[$i];
                                // <https://spec.commonmark.org/0.31.2#single-quoted-attribute-value>
                                // <https://spec.commonmark.org/0.31.2#double-quoted-attribute-value>
                                if ("'" === $quote || '"' === $quote) {
                                    ++$i;
                                    $end = false;
                                    while ($i < $size) {
                                        if ($quote === $test[$i]) {
                                            $end = true;
                                            break;
                                        }
                                        ++$i;
                                    }
                                    if (!$end) {
                                        $final = false;
                                        break;
                                    }
                                } else {
                                    // <https://spec.commonmark.org/0.31.2#unquoted-attribute-value>
                                    if (false !== \strpos(" \t'" . '"<=>`', $test[$i])) {
                                        $final = false;
                                        break;
                                    }
                                    while ($i < $size && false === \strpos(" \t'" . '"<=>`', $test[$i])) {
                                        ++$i;
                                    }
                                    --$i; // Put the cursor back right after the un-quoted attribute value
                                }
                            } else {
                                --$i; // Put the cursor back right after the attribute name if there is no value
                            }
                        }
                        if ($final) {
                            if ($r && "" !== $r[1]) {
                                // <https://spec.commonmark.org/0.31.2#example-187>
                                if (\in_array($r[0], [0, 1, 2, 'blockquote', 'p'], true)) {
                                    $r[1] .= $raw . "\n";
                                    continue;
                                }
                                $rows[] = $r;
                            }
                            $r = [false, $raw . "\n", [], $d, [7]];
                            continue;
                        }
                    }
                }
            }
        }
        // <https://spec.commonmark.org/0.31.2#block-quote-marker>
        if ('>' === $c) {
            if ($r && "" !== $r[1]) {
                $rows[] = $r;
            }
            $row = s(\substr($row, 1), 8, 1);
            if (' ' === ($row[0] ?? 0)) {
                $row = \substr($row, 1);
            }
            $r = ['blockquote', $row . "\n", [], $d];
            continue;
        }
        if ('[' === $c && '^' === ($row[1] ?? 0)) {}
        if ('[' === $c) {
            // <https://spec.commonmark.org/0.31.2#example-213>
            if ($r && "" !== $r[1]) {
                if ('blockquote' === $r[0]) {
                    // TODO
                }
                if ('p' === $r[0]) {
                    $r[1] .= $raw . "\n";
                    continue;
                }
                $rows[] = $r;
            }
            $r = [0, $row . "\n", [], $d, []];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#code-fence>
        if (('`' === $c || '~' === $c) && ($n = \strspn($row, $c)) && $n >= 3) {
            // <https://spec.commonmark.org/0.31.2#info-string>
            $rest = \trim(\substr($row, $n));
            // <https://spec.commonmark.org/0.31.2#example-145>
            if ('`' === $c && false !== \strpos($rest, '`')) {
                $r[1] .= $row . "\n";
                continue;
            }
            if ($r && "" !== $r[1]) {
                // <https://spec.commonmark.org/0.31.2#example-140>
                $rows[] = $r;
            }
            $r = ['pre', "\n", a($rest), $d, [$n, $c]];
            continue;
        }
        if ("" === \trim($row)) {
            if ($r && "" !== $r[1]) {
                $rows[] = $r;
            }
            $r = null;
            continue;
        }
        if ($r && "" !== $r[1]) {
            $r[1] .= $row . "\n";
            continue;
        }
        $r = ['p', $row . "\n", [], $d];
    }
    // if ($r && "" !== $r[1]) {
    //     $rows[] = $r;
    // }
    return [$rows, $lot];

    foreach ($raws as $raw) {
        [$row, $d] = d($raw, 4);
        if (0 === $r[0]) {
            $r[1] = \ltrim($r[1], "\n");
            // <https://spec.commonmark.org/0.31.2#example-197>
            if ("" === \trim($row)) {
                // TODO: Strict pattern without regular expression for link reference definition
                if (\preg_match('/^\S+$/', \trim($r[1]))) {
                    $r[1] = shift($r[1], \strspn($r[1], ' '), $r[4][1]);
                    $rows[] = $r;
                    $r = $p;
                    continue;
                }
                $r[1] .= "\n";
                continue;
            }
            $row_new = rows($raw, $lot)[0][0] ?? $p;
            if ('p' === $row_new[0] && "" !== $row_new[1]) {
                // TODO: Strict pattern without regular expression for link reference definition
                if (\preg_match('/^\S+$/', \trim($r[1]))) {
                    $r[1] = shift($r[1], \strspn($r[1], ' '), $r[4][1]);
                    $rows[] = $r;
                    $r = $row_new;
                    continue;
                }
                $r[1] .= $raw . "\n";
                continue;
            }
            // TODO: Strict pattern without regular expression for link reference definition
            if (\preg_match('/^\S+$/', \trim($r[1]))) {
                $r[1] = shift($r[1], \strspn($r[1], ' '), $r[4][1]);
                $rows[] = $r;
                $r = $row_new;
                continue;
            }
            $r[1] .= $raw . "\n";
            continue;
        }
        if (1 === $r[0] || 2 === $r[0]) {
            $r[1] = \ltrim($r[1], "\n");
            if ("" === \trim($row)) {
                $r[1] .= "\n";
                continue;
            }
            if ($d > 0) {
                $r[1] .= s($raw, $r[4][1]) . "\n";
                continue;
            }
            $row_new = rows($raw, $lot)[0][0] ?? $p;
            // <https://spec.commonmark.org/0.31.2#lazy-continuation-line>
            if ('p' === $row_new[0] && "" !== $row_new[1] && "\n\n" !== \substr($r[1], -2)) {
                $r[1] .= $row_new[1];
                continue;
            }
            $r[1] = \trim($r[1], "\n");
            $r[1] = shift($r[1] . "\n", \strspn($r[1], ' '), $r[4][1]);
            $rows[] = $r;
            $r = $row_new;
            continue;
        }
        if (false === $r[0]) {
            // HTML block type 1
            if (1 === $r[4][0] && isset($blocks[1][$r[4][1]]) && false !== \stripos($row, '</' . $r[4][1] . '>')) {
                $r[1] .= $raw . "\n";
                $rows[] = $r;
                $r = $p;
                continue;
            }
            // HTML block type 2, 3, 4, and 5
            if (
                2 === $r[4][0] && false !== \strpos($row, '-->') ||
                3 === $r[4][0] && false !== \strpos($row, '?>') ||
                4 === $r[4][0] && false !== \strpos($row, '>') ||
                5 === $r[4][0] && false !== \strpos($row, ']]>')
            ) {
                $r[1] .= $raw . "\n";
                $rows[] = $r;
                $r = $p;
                continue;
            }
            // HTML block type 6, and 7
            if ((6 === $r[4][0] && isset($blocks[6][$r[4][1]]) || 7 === $r[4][0]) && "" === \trim($row)) {
                $rows[] = $r;
                $r = $p;
                continue;
            }
            $r[1] .= $raw . "\n";
            continue;
        }
        if ('blockquote' === $r[0]) {
            $row_new = rows($raw, $lot)[0][0] ?? $p;
            if ('blockquote' === $row_new[0]) {
                $r[1] .= $row_new[1];
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#paragraph-continuation-text>
            if (('p' === $row_new[0] || 'pre' === $row_new[0] && "\t" === $row_new[4][1]) && "" !== $row_new[1]) {
                $r[1] .= $raw . "\n";
                continue;
            }
            $rows[] = $r;
            $r = $row_new;
            continue;
        }
        if ('ol' === $r[0]) {
            // <https://spec.commonmark.org/0.31.2#example-306>
            if ("" === \trim($row)) {
                $r[1] .= "\n";
                continue;
            }
            $row_new = rows($raw, $lot)[0][0] ?? $p;
            // <https://spec.commonmark.org/0.31.2#of-the-same-type>
            if ('ol' === $row_new[0] && $row_new[4][2] === $r[4][2] && $d < $r[3] + $r[4][0]) {
                $r[1] .= "\x3" . \substr($row, $row_new[3] + $row_new[4][0]) . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-307>
            if ($d >= $r[3] + $r[4][0]) {
                $r[1] .= \str_repeat(' ', $d) . $row . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#lazy-continuation-line>
            if (('p' === $row_new[0] || 'pre' === $row_new[0] && "\t" === $row_new[4][1]) && "" !== $row_new[1] && $d < $r[3] + $r[4][0] && "\n\n" !== \substr($r[1], -2)) {
                $r[1] .= $row . "\n";
                continue;
            }
            $r[1] = \rtrim($r[1], "\n") . "\n";
            $rows[] = $r;
            $r = $row_new;
            continue;
        }
        if ('pre' === $r[0]) {
            if ('`' === $r[4][1] || '~' === $r[4][1]) {
                $row = d($raw, $r[3])[0];
                if ($row === \str_repeat($r[4][1], $r[4][0])) {
                    $rows[] = $r;
                    $r = $p;
                    continue;
                }
                $r[1] .= $row . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-111>
            if ("" === \trim($row)) {
                // <https://spec.commonmark.org/0.31.2#example-112>
                $r[1] .= d($raw, 4)[0] . "\n";
                continue;
            }
            $row_new = rows($raw, $lot)[0][0] ?? $p;
            if ('pre' === $row_new[0]) {
                $r[1] .= $row_new[1];
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-114>
            if ("\t" === $r[4][1] && $d < $r[3]) {
                $rows[] = $r;
                $r = $row_new;
                continue;
            }
        }
        if ('ul' === $r[0]) {
            // <https://spec.commonmark.org/0.31.2#example-306>
            if ("" === \trim($row)) {
                $r[1] .= "\n";
                continue;
            }
            $row_new = rows($raw, $lot)[0][0] ?? $p;
            // <https://spec.commonmark.org/0.31.2#of-the-same-type>
            if ('ul' === $row_new[0] && $row_new[4][1] === $r[4][1] && $d < $r[3] + $r[4][0]) {
                $r[1] .= "\x3" . \substr($row, $row_new[3] + $row_new[4][0]) . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-307>
            if ($d >= $r[3] + $r[4][0]) {
                $r[1] .= \str_repeat(' ', $d) . $row . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#lazy-continuation-line>
            if (('p' === $row_new[0] || 'pre' === $row_new[0] && "\t" === $row_new[4][1]) && "" !== $row_new[1] && $d < $r[3] + $r[4][0] && "\n\n" !== \substr($r[1], -2)) {
                $r[1] .= $row . "\n";
                continue;
            }
            $r[1] = \rtrim($r[1], "\n") . "\n";
            $rows[] = $r;
            $r = $row_new;
            continue;
        }
        $exit = ('pre' === $r[0] && "\t" !== $r[4][1]) || 'h' === ($r[0] . "")[0] || (false === $r[0] && $r[4][0] >= 1 && $r[4][0] <= 5);
        // <https://spec.commonmark.org/0.31.2#indented-code-block>
        if ($d >= 4) {
            if ("" !== $r[1]) {
                // <https://spec.commonmark.org/0.31.2#example-113>
                if (!$exit) {
                    $r[1] .= $row . "\n";
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#example-115>
                $rows[] = $r;
            }
            $r = ['pre', $row . "\n", ['class' => false], $d, [4, "\t"]];
            continue;
        }
        $c = $row[0] ?? $x;
        // <https://spec.commonmark.org/0.31.2#atx-heading>
        if ('#' === $c && ($n = \strspn($row, $c)) && $n < 7 && \strspn($row . ' ', " \t", $n)) {
            if ("" !== $r[1]) {
                if ($d >= 4 && !$exit) {
                    $r[1] .= $row . "\n";
                    continue;
                }
                $rows[] = $r;
            }
            // <https://spec.commonmark.org/0.31.2#example-67>
            $row = \trim(\substr($row, $n));
            $row_test = \rtrim($row, '#');
            // <https://spec.commonmark.org/0.31.2#example-75>
            // <https://spec.commonmark.org/0.31.2#example-76>
            if ($row_test !== $row && "\\" !== \substr($row_test, -1) && false !== \strpos(" \t", \substr($row_test, -1))) {
                $row = \trim($row_test);
            }
            [$row, $a1] = a1($row);
            $rows[] = ['h' . $n, $row . "\n", $a1, $d, [$n, '#']];
            $r = $p;
            continue;
        }
        if ('*' === $c && '[' === ($row[1] ?? $x)) {
            // <https://spec.commonmark.org/0.31.2#example-213>
            if ("" !== $r[1] && !$exit) {
                $r[1] .= $row . "\n";
                continue;
            }
            $size = \strlen($row);
            for ($i = 2; $i < $size; ++$i) {
                $c = $row[$i];
                if ("\\" === $c && $i + 1 < $size) {
                    ++$i;
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#link-label>
                if ('[' === $c) {
                    break;
                }
                if (']' === $c) {
                    $next = $i + 1; // Go to one character after `]`
                    while ($next < $size && false !== \strpos(" \t", $row[$next])) {
                        ++$next;
                    }
                    // There must be a `:` after the optional white-space(s)
                    if ($next >= $size || ':' !== $row[$next]) {
                        break;
                    }
                    $next += 1; // Go to one character after `:`
                    // There must be a white-space after the `:`, unless it marks the end of the row
                    if ($next >= $size || false !== \strpos(" \t", $row[$next])) {
                        if ("" !== $r[1]) {
                            $rows[] = $r;
                        }
                        $k = \substr($row, 0, $next);
                        $key = \trim(\substr($k, 2)); // Remove `*[`
                        $key = \trim(\substr($key, 0, -1)); // Remove `:`
                        $key = \trim(\substr($key, 0, -1)); // Remove `]`
                        $value = \substr($row, $next);
                        $r = [1, $value . "\n", [], $d, [v($key), \strlen($k), $k]];
                        continue 2;
                    }
                    break;
                }
            }
            $r[1] .= $row . "\n";
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#bullet-list>
        if (('*' === $c || '+' === $c || '-' === $c) && ($s = \strspn($row . ' ', " \t", 1))) {
            $empty = $row[0] === \trim($row);
            if ("" !== $r[1]) {
                // <https://spec.commonmark.org/0.31.2#example-285>
                if ($empty) {
                    $r[1] .= $row . "\n";
                    continue;
                }
                $rows[] = $r;
            }
            $r = ['ul', \substr($row, 1 + $s) . "\n", [], $d, [1 + $s, $row[0], ""]];
            // <https://spec.commonmark.org/0.31.2#example-284>
            if ($empty) {
                $rows[] = $r;
                $r = $p;
            }
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#code-fence>
        if (('`' === $c || '~' === $c) && ($n = \strspn($row, $c)) && $n >= 3) {
            // <https://spec.commonmark.org/0.31.2#info-string>
            $rest = \trim(\substr($row, $n));
            // <https://spec.commonmark.org/0.31.2#example-145>
            if ('`' === $row[0] && false !== \strpos($rest, '`')) {
                $r[1] .= $row . "\n";
                continue;
            }
            if ("" !== $r[1]) {
                // <https://spec.commonmark.org/0.31.2#example-140>
                $rows[] = $r;
            }
            $r = ['pre', "", a($rest), $d, [$n, $row[0]]];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#setext-heading>
        if (('-' === $c || '=' === $c) && \strspn($row, $c) === \strlen($row) && 'p' === $r[0] && "" !== $r[1]) {
            [$row, $a1] = a1(\substr($r[1], 0, -1));
            $r[0] = 'h' . ($n = '-' === $c ? 2 : 1);
            $r[1] = $row . "\n";
            $r[2] = $a1;
            $r[4] = [$n, $c];
            $rows[] = $r;
            $r = $p;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#ordered-list>
        if (($n = \strspn($row, $c3)) && false !== \strpos(').', $row[$n] ?? $x) && ($s = \strspn($row . ' ', " \t", $n + 1))) {
            // <https://spec.commonmark.org/0.31.2#start-number>
            $at = (int) \substr($row, 0, $n);
            $empty = \substr($row, 0, $n + 1) === \trim($row);
            if ("" !== $r[1]) {
                // <https://spec.commonmark.org/0.31.2#example-285>
                // <https://spec.commonmark.org/0.31.2#example-304>
                if (1 !== $at || $empty) {
                    $r[1] .= $row . "\n";
                    continue;
                }
                $rows[] = $r;
            }
            $r = ['ol', \substr($row, $n + 1 + $s) . "\n", ['start' => $at], $d, [$n + 1 + $s, $at, $row[$n]]];
            // <https://spec.commonmark.org/0.31.2#example-284>
            if ($empty) {
                $rows[] = $r;
                $r = $p;
            }
            continue;
        }
        if (':' === $c) {}
        // <https://spec.commonmark.org/0.31.2#html-block>
        if ('<' === $c && ($block = \substr($row, 1, \strcspn($row, " \t>", 1)))) {
            if ('!' === ($block[0] ?? $x) && '>' !== ($block[1] ?? $x)) {
                // <https://spec.commonmark.org/0.31.2#example-185>
                if ("" !== $r[1]) {
                    $rows[] = $r;
                }
                // <https://spec.commonmark.org/0.31.2#example-179>
                // <https://spec.commonmark.org/0.31.2#html-comment>
                if ('--' === \substr($block, 1, 2)) {
                    $r = [false, $raw . "\n", [], $d, [2]];
                    if (false !== \strpos($row, '-->')) {
                        // Ends on its own line
                        $rows[] = $r;
                        $r = $p;
                    }
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#cdata-section>
                // <https://spec.commonmark.org/0.31.2#example-182>
                if ('[CDATA[' === \substr($block, 1, 7)) {
                    $r = [false, $raw . "\n", [], $d, [5]];
                    if (false !== \strpos($row, ']]>')) {
                        // Ends on its own line
                        $rows[] = $r;
                        $r = $p;
                    }
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#declaration>
                // <https://spec.commonmark.org/0.31.2#example-181>
                if (\strspn(\strtolower($block), $c1, 1)) {
                    $r = [false, $raw . "\n", [], $d, [4]];
                    if (false !== \strpos($row, '>')) {
                        // Ends on its own line
                        $rows[] = $r;
                        $r = $p;
                    }
                    continue;
                }
                $r[1] .= $row . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-180>
            // <https://spec.commonmark.org/0.31.2#processing-instruction>
            if ('?' === ($block[0] ?? $x) && '>' !== ($block[1] ?? $x)) {
                // <https://spec.commonmark.org/0.31.2#example-185>
                if ("" !== $r[1]) {
                    $rows[] = $r;
                }
                $r = [false, $raw . "\n", [], $d, [3]];
                if (false !== \strpos($row, '?>')) {
                    // Ends on its own line
                    $rows[] = $r;
                    $r = $p;
                }
                continue;
            }
            // The initial tag doesn’t need to be a valid tag, as long as it starts like one
            if (isset($blocks[1][$block = \strtolower($block)])) {
                // <https://spec.commonmark.org/0.31.2#example-185>
                if ("" !== $r[1]) {
                    $rows[] = $r;
                }
                $r = [false, $raw . "\n", [], $d, [1, $block]];
                if (false !== \stripos($row, '</' . $block . '>')) {
                    // Ends on its own line
                    $rows[] = $r;
                    $r = $p;
                }
                continue;
            }
            // HTML block(s) type 6 does not care whether it is an open or close tag
            if (isset($blocks[6][$block_6 = \trim($block, '/')])) {
                // <https://spec.commonmark.org/0.31.2#example-185>
                if ("" !== $r[1]) {
                    $rows[] = $r;
                }
                $r = [false, $raw . "\n", [], $d, [6, $block_6]];
                continue;
            }
            if ('>' === \substr($test = \trim($row), -1)) {
                // <https://spec.commonmark.org/0.31.2#open-tag>
                $open = 1;
                $test = \rtrim(\substr($test, 1, -1));
                // <https://spec.commonmark.org/0.31.2#closing-tag>
                if ('/' === $test[0] ?? $x) {
                    $open = 0;
                    $test = \substr($test, 1);
                }
                // <https://spec.commonmark.org/0.31.2#open-tag>
                if ('/' === \substr($test, -1)) {
                    $open = 2;
                    $test = \substr($test, 0, -1);
                }
                $test = \trim(\strtolower($test));
                // <https://spec.commonmark.org/0.31.2#tag-name>
                if (\strspn($test, $c1) && ($n = \strspn($test, $c4, 1))) {
                    // An opening or closing tag with no attribute(s)
                    if ("" === ($test = \trim(\substr($test, $n + 1)))) {
                        if ("" !== $r[1]) {
                            if ('p' === $r[0]) {
                                $r[1] .= $row . "\n";
                                continue;
                            }
                            $rows[] = $r;
                        }
                        $r = [false, $raw . "\n", [], $d, [7]];
                        continue;
                    }
                    if ($open) {
                        $limit = \strlen($test);
                        $valid = true;
                        for ($i = 0; $i < $limit; ++$i) {
                            while ($i < $limit && false !== \strpos(" \t", $test[$i])) {
                                ++$i;
                            }
                            if ($i >= $limit) {
                                break;
                            }
                            // <https://spec.commonmark.org/0.31.2#attribute-name>
                            if (false === \strpos($c1 . ':_', $test[$i])) {
                                $valid = false;
                                break;
                            }
                            while ($i < $limit && false !== \strpos($c4 . '.:_', $test[$i])) {
                                ++$i;
                            }
                            // <https://spec.commonmark.org/0.31.2#attribute-value-specification>
                            while ($i < $limit && false !== \strpos(" \t", $test[$i])) {
                                ++$i;
                            }
                            // <https://spec.commonmark.org/0.31.2#attribute-value>
                            if ($i < $limit && '=' === $test[$i]) {
                                ++$i;
                                // <https://spec.commonmark.org/0.31.2#attribute-value-specification>
                                while ($i < $limit && false !== \strpos(" \t", $test[$i])) {
                                    ++$i;
                                }
                                if ($i >= $limit) {
                                    $valid = false;
                                    break;
                                }
                                $quote = $test[$i];
                                // <https://spec.commonmark.org/0.31.2#single-quoted-attribute-value>
                                // <https://spec.commonmark.org/0.31.2#double-quoted-attribute-value>
                                if ("'" === $quote || '"' === $quote) {
                                    ++$i;
                                    $end = false;
                                    while ($i < $limit) {
                                        if ($quote === $test[$i]) {
                                            $end = true;
                                            break;
                                        }
                                        ++$i;
                                    }
                                    if (!$end) {
                                        $valid = false;
                                        break;
                                    }
                                } else {
                                    // <https://spec.commonmark.org/0.31.2#unquoted-attribute-value>
                                    if (false !== \strpos(" \t'" . '"<=>`', $test[$i])) {
                                        $valid = false;
                                        break;
                                    }
                                    while ($i < $limit && false === \strpos(" \t'" . '"<=>`', $test[$i])) {
                                        ++$i;
                                    }
                                    --$i; // Put the cursor back right after the un-quoted attribute value
                                }
                            } else {
                                --$i; // Put the cursor back right after the attribute name if there is no value
                            }
                        }
                        if ($valid) {
                            if ("" !== $r[1]) {
                                // <https://spec.commonmark.org/0.31.2#example-187>
                                if ('p' === $r[0]) {
                                    $r[1] .= $row . "\n";
                                    continue;
                                }
                                $rows[] = $r;
                            }
                            $r = [false, $raw . "\n", [], $d, [7]];
                            continue;
                        }
                    }
                }
            }
            $r[1] .= $row . "\n";
            if (0 === $r[3]) {
                $r[3] = $d;
            }
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#block-quote-marker>
        if ('>' === $c) {
            if ("" !== $r[1]) {
                $rows[] = $r;
            }
            [$a, $b] = d($row = \substr($row, 1), 1);
            if ($w = ($b + 1) - 1) {
                $n = $w - 1;
                $row = \substr($row, 1);
                if ($n > 0) {
                    $row = \str_repeat(' ', $n) . $row;
                }
            }
            $r = ['blockquote', $row . "\n", [], $d];
            continue;
        }
        if ('[' === $c && '^' === ($row[1] ?? $x)) {
            // <https://spec.commonmark.org/0.31.2#example-213>
            if ("" !== $r[1] && !$exit) {
                $r[1] .= $row . "\n";
                continue;
            }
            $size = \strlen($row);
            for ($i = 2; $i < $size; ++$i) {
                $c = $row[$i];
                if ("\\" === $c && $i + 1 < $size) {
                    ++$i;
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#link-label>
                if ('[' === $c) {
                    break;
                }
                if (']' === $c) {
                    $next = $i + 1; // Go to one character after `]`
                    while ($next < $size && false !== \strpos(" \t", $row[$next])) {
                        ++$next;
                    }
                    // There must be a `:` after the optional white-space(s)
                    if ($next >= $size || ':' !== $row[$next]) {
                        break;
                    }
                    $next += 1; // Go to one character after `:`
                    // There must be a white-space after the `:`, unless it marks the end of the row
                    if ($next >= $size || false !== \strpos(" \t", $row[$next])) {
                        if ("" !== $r[1]) {
                            $rows[] = $r;
                        }
                        $k = \substr($row, 0, $next);
                        $key = \trim(\substr($k, 2)); // Remove `[^`
                        $key = \trim(\substr($key, 0, -1)); // Remove `:`
                        $key = \trim(\substr($key, 0, -1)); // Remove `]`
                        $value = \substr($row, $next);
                        $r = [2, $value . "\n", [], $d, [v($key), \strlen($k), $k]];
                        continue 2;
                    }
                    break;
                }
            }
            $r[1] .= $row . "\n";
            continue;
        }
        if ('[' === $c) {
            // <https://spec.commonmark.org/0.31.2#example-213>
            if ("" !== $r[1] && !$exit) {
                $r[1] .= $row . "\n";
                continue;
            }
            $size = \strlen($row);
            for ($i = 1; $i < $size; ++$i) {
                $c = $row[$i];
                if ("\\" === $c && $i + 1 < $size) {
                    ++$i;
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#link-label>
                if ('[' === $c) {
                    break;
                }
                if (']' === $c) {
                    $next = $i + 1; // Go to one character after `]`
                    while ($next < $size && false !== \strpos(" \t", $row[$next])) {
                        ++$next;
                    }
                    // There must be a `:` after the optional white-space(s)
                    if ($next >= $size || ':' !== $row[$next]) {
                        break;
                    }
                    $next += 1; // Go to one character after `:`
                    // There must be a white-space after the `:`, unless it marks the end of the row
                    if ($next >= $size || false !== \strpos(" \t", $row[$next])) {
                        if ("" !== $r[1]) {
                            $rows[] = $r;
                        }
                        $k = \substr($row, 0, $next);
                        $key = \trim(\substr($k, 1)); // Remove `[`
                        $key = \trim(\substr($key, 0, -1)); // Remove `:`
                        $key = \trim(\substr($key, 0, -1)); // Remove `]`
                        $value = \substr($row, $next);
                        $r = [0, $value . "\n", [], $d, [s1(v($key)), \strlen($k), $k]];
                        continue 2;
                    }
                    break;
                }
            }
            $r[1] .= $row . "\n";
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#thematic-break>
        $test = \strtr($row, ["\t" => "", ' ' => ""]);
        if (('*' === $c || '-' === $c || '_' === $c) && \strspn($test, $c) === ($n = \strlen($test)) && $n >= 3) {
            if ("" !== $r[1]) {
                $rows[] = $r;
            }
            $rows[] = ['hr', false, [], $d, [$c]];
            $r = $p;
            continue;
        }
        if (false !== \strpos($row, '|')) {}
        // <https://spec.commonmark.org/0.31.2#paragraph>
        if ("" === \trim($row)) {
            if ('p' !== $r[0] || "" !== $r[1]) {
                $rows[] = $r;
                $r = $p;
            }
            continue;
        }
        $r[1] .= $row . "\n";
        if (0 === $r[3]) {
            $r[3] = $d;
        }
    }
    if (0 === $r[0] || 1 === $r[0] || 2 === $r[0]) {
        $r[1] = shift($r[1], \strspn($r[1], ' '), $r[4][1]);
        // TODO: Strict pattern without regular expression for link reference definition
        if (0 === $r[0] && !\preg_match('/^\S+$/', \trim($r[1]))) {
            $r[0] = 'p';
            // $r[1] = \trim($r[4][2] . "\n" . $r[1], "\n") . "\n";
            $r[1] = $r[4][2];
            unset($r[4]);
        }
    }
    if ("" !== $r[1]) {
        $rows[] = $r;
    }
    return [$rows, $lot];
}

/*
function s(string $text, int $max = 4, int $d = 0) {
    $i = 0;
    while (false !== ($n = \strpos($text, "\t", $i))) {
        $c = $d + $n;
        if ($c >= $max) {
            break;
        }
        $r = 4 - ($c % 4);
        if ($c + $r > $max) {
            $r = $max - $c;
        }
        $text = \substr_replace($text, \str_repeat(' ', $r), $n, 1);
        $i = $n + $r;
    }
    return $text;
}
*/

function s(string $text, int $max = 4, int $n = 0) {
    $limit = \strlen($text);
    $r = "";
    for ($i = 0; $i < $limit; ++$i) {
        $c = $text[$i];
        if (' ' === $c) {
            if ($n < $max) {
                $r .= $c;
            }
            $n += 1;
            continue;
        }
        if ("\t" === $c) {
            $z = 4 - ($n % 4);
            if ($n < $max) {
                $r .= \str_repeat(' ', \min($z, $max - $n));
            }
            $n += $z;
            continue;
        }
        return $r . \substr($text, $i);
    }
    return $r;
}

function s1(string $text) {
    $text = \strtr($text, ["\n" => ' ', "\t" => ' ']);
    return \trim(\implode(' ', \array_filter(\explode(' ', $text), function ($v) {
        return "" !== $v;
    })));
}

function shift(string $text, int $d = 0, int $e = 0) {
    $r = "";
    foreach (\explode("\n", $text) as $v) {
        ($n = \strspn($v, ' ')) >= $d && ($v = \substr($v, $d));
        ($n = \strspn($v, ' ')) >= $e && ($v = \substr($v, $e));
        $r .= $v . "\n";
    }
    return \substr($r, 0, -1);
}

function v(string $text) {
    // <https://spec.commonmark.org/0.31.2#ascii-punctuation-character>
    // <https://spec.commonmark.org/0.31.2#example-12>
    static $r = [
        "\\'" => "'",
        "\\\\" => "\\",
        '\!' => '!',
        '\"' => '"',
        '\#' => '#',
        '\$' => '$',
        '\%' => '%',
        '\&' => '&',
        '\(' => '(',
        '\)' => ')',
        '\*' => '*',
        '\+' => '+',
        '\,' => ',',
        '\-' => '-',
        '\.' => '.',
        '\/' => '/',
        '\:' => ':',
        '\;' => ';',
        '\<' => '<',
        '\=' => '=',
        '\>' => '>',
        '\?' => '?',
        '\@' => '@',
        '\[' => '[',
        '\]' => ']',
        '\^' => '^',
        '\_' => '_',
        '\`' => '`',
        '\{' => '{',
        '\|' => '|',
        '\}' => '}',
        '\~' => '~',
    ];
    return \strtr($text, $r);
}



$files = glob(__DIR__ . '/draft/*/*/*.md', GLOB_NOSORT);

usort($files, function ($a, $b) {
    return strnatcmp(strtr(substr($a, 0, -3), ['/' => '-', "\\" => '-']), strtr(substr($b, 0, -3), ['/' => '-', "\\" => '-']));
});

$current = "";
foreach ($files as $file) {
    if ($current !== ($d = basename(dirname($file, 2)) . '\\' . basename(dirname($file))) || "" === $current) {
        echo '<h2 style="margin:0;">.\\test\\' . $d . '\\*</h2>';
        $current = $d;
    }
    $text = file_get_contents($file);
    echo '<div style="display:flex;gap:1em;margin:1em 0;">';
    echo '<pre style="border:2px solid #f00;flex:1;font:normal normal 12px/1.25 monospace;margin:0;overflow:auto;padding:0 0.25em;">';
    echo \htmlspecialchars($text);
    echo '</pre>';
    echo '<pre style="border:2px solid #00f;flex:1;font:normal normal 12px/1.25 monospace;margin:0;overflow:auto;padding:0 0.25em;">';
    $lot = [];
    echo \htmlspecialchars(\json_encode(rows($text, $lot), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
    echo '</pre>';
    echo '</div>';
}