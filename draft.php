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

function a(string $row) {
    $r = [$row, []];
    if (false === \strpos($row, '{')) {
        return $r;
    }
    if ('}' !== \substr($row, -1) || "\\" === \substr($row, -2, 1)) {
        return $r;
    }
    $at = false;
    $limit = \strlen($row);
    // Start from the character just before the trailing `}`
    for ($i = $limit - 2; $i >= 0; --$i) {
        $c = $row[$i];
        // Check if the current character is escaped by an odd number of back-slash(es)
        $esc = 0;
        for ($j = $i - 1; $j >= 0 && "\\" === $row[$j]; --$j) {
            $esc++;
        }
        if (0 !== $esc % 2) {
            continue;
        }
        // Capture the first unescaped `{` that meets the white-space condition
        if ('{' === $c) {
            if (0 === $i || false !== \strpos(" \t", $row[$i - 1])) {
                $at = $i;
            }
            break;
        }
    }
    if (false !== $at) {
        $r[0] = \rtrim(\substr($row, 0, $at));
        $r[1] = attr(\substr($row, $at));
    }
    return $r;
}

function abbr(string $text) {
    if ('*' !== ($text[0] ?? 0) || '[' !== ($text[1] ?? 0)) {
        return [];
    }
    $i = 2; // Start after `*[`
    while (false !== ($n = \strpos($text, ']:', $i))) {
        if ($n > 2 && "\\" === $text[$n - 1]) {
            $i = $n + 2;
            continue;
        }
        $k = \substr($text, 0, $n += 2);
        $key = \trim(\substr($k, 2, -2)); // Remove `*[` and `]:`
        $value = \trim(\substr($text, $n));
        return [s1(v($key)), $value . "\n"];
    }
    return [];
}

function attr(string $text) {
    // Force a space at the end of the text. This will make processing easier.
    $limit = \strlen($text = \trim($text) . ' ');
    $r = [];
    $s = "";
    // With attribute syntax wrapped by `{` and `}`, an attribute name immediately followed by white-space will be
    // treated as a toggle attribute. Alternatively, without such wrapping, an attribute name immediately followed by
    // white-space will be treated as a class name preceded by the `language-` prefix.
    if ($b = '{' === $text[0] && '}' === \substr($text, -2, 1) && "\\" !== \substr($text, -3, 1)) {
        $limit = \strlen($text = \trim(\substr($text, 1, -2)) . ' ');
    }
    for ($i = 0; $i < $limit; ++$i) {
        $c = $text[$i];
        if ("\\" === $c && $i + 1 < $limit) {
            $s .= $text[++$i];
            continue;
        }
        if ('=' === $c) {
            $k = $s;
            $s = "";
            if (++$i >= $limit) {
                $r[$k] = "";
                break;
            }
            $c = $text[$i];
            $q = 0;
            if ('"' === $c || "'" === $c) {
                $q = $c;
                ++$i;
            }
            while ($i < $limit) {
                $c = $text[$i];
                if ("\\" === $c && $i + 1 < $limit) {
                    $s .= $text[++$i];
                    continue;
                }
                if ($q) {
                    if ($c === $q) {
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
        if ("" !== $s && false !== \strpos(" \t#.", $c)) {
            if ('#' === $s[0] && "" !== ($s = \substr($s, 1))) {
                $r['id'] = $s;
            } else if ('.' === $s[0] && "" !== ($s = \substr($s, 1))) {
                $r['class'][] = $s;
            } else {
                $b ? ($r[$s] = true) : ($r['class'][] = 'language-' . $s);
            }
            $s = false !== \strpos(" \t", $c) ? "" : $c;
            continue;
        }
        $s .= $c;
    }
    $set = \array_unique($r['class'] ?? []);
    if ($set) {
        \sort($set);
        $r['class'] = \implode(' ', $set);
    }
    \ksort($r);
    return $r;
}

function from(?string $value, $block = true): ?string {}

function note(string $text) {
    if ('[' !== ($text[0] ?? 0) || '^' !== ($text[1] ?? 0)) {
        return [];
    }
    $i = 2; // Start after `[^`
    while (false !== ($n = \strpos($text, ']:', $i))) {
        if ($n > 2 && "\\" === $text[$n - 1]) {
            $i = $n + 2;
            continue;
        }
        $k = \substr($text, 0, $n += 2);
        $key = \trim(\substr($k, 2, -2)); // Remove `[^` and `]:`
        $value = \rtrim(\substr($text, $n));
        $n = 0;
        // Case for note content that comes below the note label
        if ("\n" === ($value[0] ?? 0)) {
            // Get the least amount of indentation from the note value to remove
            $n = \strspn($value = \trim($value, "\n"), ' ');
            foreach (\explode("\n", $value) as $v) {
                if ("" !== ($v = \rtrim($v)) && ($w = \strspn($v, ' ')) < $n) {
                    $n = $w;
                }
            }
        // Case for note content that comes next to the note label
        } else {
            // Get the indentation after the first line because it is not indented
            if ("" !== ($next = \trim(\strstr($value = \trim($value), "\n"), "\n"))) {
                $n = \strspn($next, ' ');
                foreach (\explode("\n", $next) as $v) {
                    if ("" !== ($v = \rtrim($v)) && ($w = \strspn($v, ' ')) < $n) {
                        $n = $w;
                    }
                }
            }
        }
        $n && ($value = s2($value, $n));
        if ("" === $key || "" === $value) {
            return []; // Note key and content cannot be empty
        }
        return [s1(v($key)), $value . "\n"];
    }
    return [];
}

// <https://spec.commonmark.org/0.31.2#link-reference-definition>
function ref(string $text) {
    if ('[' !== ($text[0] ?? 0)) {
        return [];
    }
    $i = 1; // Start after `[`
    $r = [null, null, null, (object) []];
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
    // <https://spec.commonmark.org/0.31.2#link-label>
    $r[0] = s1(v(\trim(\substr($text, 1, $n - 1))));
    // <https://spec.commonmark.org/0.31.2#example-551>
    if ("" === $r[0]) {
        return []; // Link label cannot be empty
    }
    $limit = \strlen($r[0]);
    if ($limit > 999) {
        return []; // Link label can have at most 999 character(s) inside the `[` and `]` character(s)
    }
    for ($j = 0; $j < $limit; ++$j) {
        if ("\\" === $r[0][$j]) {
            ++$j;
            continue;
        }
        if ('[' === $r[0][$j] || ']' === $r[0][$j]) {
            return []; // Link label cannot contain unescaped `[` and `]` character(s)
        }
    }
    // <https://spec.commonmark.org/0.31.2#matches>
    if (\defined("\\MB_CASE_FOLD")) {
        $r[0] = \mb_convert_case($r[0], \MB_CASE_FOLD, 'UTF-8');
    } else {
        $r[0] = \strtolower($r[0]);
    }
    $text = \trim(\substr($text, $n + 2));
    // <https://spec.commonmark.org/0.31.2#example-199>
    if ("" === $text) {
        return []; // Link label should be followed by a link destination
    }
    // <https://spec.commonmark.org/0.31.2#link-destination>
    // <https://spec.commonmark.org/0.31.2#example-486>
    if ('<>' === \substr($text, 0, 2)) {
        $r[1] = ""; // Link destination cannot be empty, unless it is `<>`
        $text = \substr($text, 2);
    } else if ('<' === $text[0]) {
        if (false === ($n = \strpos($text, '>'))) {
            return []; // Unclosed :(
        }
        $r[1] = \substr($text, 0, $n + 1);
        // <https://spec.commonmark.org/0.31.2#example-491>
        if (false !== \strpos($r[1], "\n")) {
            return []; // Link destination cannot contain line break
        }
        $limit = \strlen($r[1]) - 1;
        for ($j = 1; $j < $limit; ++$j) {
            if ("\\" === $r[1][$j]) {
                ++$j;
                continue;
            }
            if ('<' === $r[1][$j] || '>' === $r[1][$j]) {
                return []; // Link destination cannot contain unescaped `<` and `>` character(s)
            }
        }
        $text = \substr($text, $n + 1);
    } else {
        if (!$n = \strcspn($text, " \n\t")) {
            return [];
        }
        $r[1] = \substr($text, 0, $n);
        $d = 0;
        $limit = \strlen($r[1]);
        for ($j = 0; $j < $limit; ++$j) {
            if ("\\" === $r[1][$j]) {
                ++$j;
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-496>
            if ('(' === $r[1][$j]) {
                ++$d;
            } else if (')' === $r[1][$j]) {
                if (0 === $d) {
                    return [];
                }
                --$d;
            }
            if ('<' === $r[1][$j]) {
                return [];
            }
        }
        if (0 !== $d) {
            return []; // Unclosed :(
        }
        $text = \substr($text, $n);
    }
    // <https://spec.commonmark.org/0.31.2#example-201>
    if ("" !== $text && !($n = \strspn($text, " \n\t"))) {
        return []; // If it has a title, it needs to be preceded by a white-space
    }
    if ("" !== ($text = \substr($text, $n))) {
        if ('{' !== ($c = $text[0])) {
            // <https://spec.commonmark.org/0.31.2#link-title>
            if ("'" === $c || '"' === $c || '(' === $c) {
                $c = '(' === $c ? ')' : $c;
                $i = 1; // Start after `'`, `"`, or `(`
                while (false !== ($n = \strpos($text, $c, $i))) {
                    if ("\\" === $text[$n - 1]) {
                        $i = $n + 1;
                        continue;
                    }
                    break;
                }
                if (false === $n) {
                    return []; // Unclosed :(
                }
                $r[2] = v(\substr($text, 1, $n - 1));
                $text = \substr($text, $n + 1);
            }
        }
        // It has a title that can be followed by attribute(s), which must be preceded by a white-space
        if ("" !== $text && !($n = \strspn($text, " \n\t"))) {
            if (null !== $r[2]) {
                return [];
            }
        }
        // Attribute(s) exist right after the link destination
        if ("" !== ($text = \substr($text, $n))) {
            $r[3] = \trim($text);
            // <https://spec.commonmark.org/0.31.2#example-209>
            if ('{' !== $r[3][0] || '}' !== \substr($r[3], -1) || "\\" === \substr($r[3], -2, 1)) {
                return []; // This part must be junk text after the link destination or title
            }
            $r[3] = (object) attr($r[3]);
        }
    }
    return $r;
}

function row(string $text, array &$lot = []) {}

function rows(string $text, array &$lot = [], $deep = 0) {
    $lot = \array_replace([(object) [], (object) [], (object) []], $lot);
    if ("" === \trim($text)) {
        return [[], $lot];
    }
    static $blocks, $c1, $c2, $c3, $c4, $c5, $c6;
    $blocks || ($blocks = [
        1 => \array_fill_keys(['pre', 'script', 'style', 'textarea'], 1),
        6 => \array_fill_keys([
            'address', 'article', 'aside', 'base', 'basefont', 'blockquote', 'body', 'caption', 'center', 'col',
            'colgroup', 'dd', 'details', 'dialog', 'dir', 'div', 'dl', 'dt', 'fieldset', 'figcaption', 'figure',
            'footer', 'form', 'frame', 'frameset', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hr', 'html',
            'iframe', 'legend', 'li', 'link', 'main', 'menu', 'menuitem', 'nav', 'noframes', 'ol', 'optgroup', 'option',
            'p', 'param', 'search', 'section', 'summary', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'title', 'tr',
            'track', 'ul'
        ], 1)
    ]);
    $c1 || ($c1 = 'abcdefghijklmnopqrstuvwxyz');
    $c2 || ($c2 = '0123456789');
    $c3 || ($c3 = '-' . $c2);
    $c4 || ($c4 = $c1 . $c3);
    $c5 || ($c5 = $c1 . ':_');
    $c6 || ($c6 = $c4 . '.:_');
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
        if ($deep >= 0 && $r && 0 === $r[0]) {
            // A link reference definition cannot contain blank line(s).
            if ("" === ($row = \trim($row))) {
                if ($ref = ref($r[1])) {
                    $r[4] = $ref;
                    $rows[] = $r;
                    $r = null;
                    continue;
                }
                // A blank line closes the current potential link reference definition. If the potential link reference
                // definition is not valid, treat it as a normal paragraph.
                $r[0] = 'p';
                $rows[] = $r;
                $r = null;
                continue;
            }
            // Current line validates the link reference definition.
            if ($ref = ref($r[1] . $row)) {
                $r[4] = $ref;
                $rows[] = $r;
                $r = null;
                continue;
            }
            // Current line invalidates the link reference definition. Try to validate the previous chunk. If it is a
            // valid link reference definition, then we can assume that the next line can start a new block.
            if ($ref = ref($r[1])) {
                $r[4] = $ref;
                $rows[] = $r;
                // Assume the next line starts a new block
                $r = rows($raw, $lot)[0][0] ?? null;
                continue;
            }
            // At this point, current link reference definition stream is not yet a valid link reference definition. We
            // can assume that the current line may start a new block. However, if the current line is a paragraph,
            // consider it as a continuation of the link reference definition, which is to be validated later.
            $now = rows($raw, $lot)[0][0] ?? null;
            if ($now && 'p' === $now[0] && "" !== $now[1]) {
                $r[1] .= $now[1];
                continue;
            }
            // A new block interrupts the current link reference definition stream, causing it to become invalid.
            if ($now && 0 === $r[0]) {
                $r[0] = 'p';
            }
            $rows[] = $r;
            $r = $now;
            continue;
        }
        if ($deep >= 0 && $r && 1 === $r[0]) {
            // An abbreviation definition cannot contain blank line(s).
            if ("" === \trim($row)) {
                if ($abbr = abbr($r[1])) {
                    $r[4] = $abbr;
                    $rows[] = $r;
                    $r = null;
                    continue;
                }
                $r[0] = 'p';
                $rows[] = $r;
                $r = null;
                continue;
            }
            // Check if the current line can continue the abbreviation definition.
            $now = rows($raw, $lot)[0][0] ?? null;
            if ($now && 'p' === $now[0] && "" !== $now[1]) {
                $r[1] .= $now[1];
                continue;
            }
            if ($now && false === $now[0] && 7 === $now[4][0]) {
                $r[1] .= $now[1];
                continue;
            }
            // At this point, the new block should interrupt the current abbreviation definition stream.
            if ($abbr = abbr($r[1])) {
                $r[4] = $abbr;
                $rows[] = $r;
                $r = $now;
                continue;
            }
            // At this point, the potential abbreviation definition is closed but not valid.
            $r[0] = 'p';
            $rows[] = $r;
            $r = null;
            continue;
        }
        if ($deep >= 0 && $r && 2 === $r[0]) {
            // A note can have blank line(s), just like list(s).
            if ("" === \trim($row)) {
                $r[1] .= "\n";
                continue;
            }
            // Multi-line note(s) don’t have to be indented by four space(s) as required by Markdown Extra. A space or
            // tab is enough to continue the note. Since there is no formal specification for this block, the best
            // approach is probably to use the minimum indent rule for list item continuation where the note label acts
            // as the list item marker. However, since note label(s) can vary in length, a less strict rule would be
            // better for this block. Multiple note label(s) with note content below them would look more organized if
            // they are all lined up at the same indentation level.
            if ($d > 0) {
                if (\strspn($raw = s($raw, $d), ' ') > $r[3]) {
                    $raw = \substr($raw, $r[3]);
                }
                $r[1] .= $raw . "\n";
                continue;
            }
            $now = rows($raw, $lot)[0][0] ?? null;
            if ($now && 'p' === $now[0] && "" !== $now[1] && "\n\n" !== \substr($r[1], -2)) {
                $r[1] .= $now[1];
                continue;
            }
            $r[1] = \trim($r[1], "\n") . "\n";
            if ($note = note($r[1])) {
                $r[4] = $note;
                $rows[] = $r;
                $r = $now;
                continue;
            }
            $r[0] = 'p';
            $rows[] = $r;
            $r = $now;
            continue;
        }
        if ($r && false === $r[0]) {
            // HTML block type 1
            if (1 === $r[4][0] && isset($blocks[1][$r[4][1]]) && false !== \stripos($r[1], '</' . $r[4][1] . '>')) {
                if ("" !== \trim($row)) {
                    $r[1] .= $raw . "\n";
                }
                $rows[] = $r;
                $r = null;
                continue;
            }
            // HTML block type 2, 3, 4, and 5
            if (
                2 === $r[4][0] && false !== \strpos($r[1], '-->') ||
                3 === $r[4][0] && false !== \strpos($r[1], '?>') ||
                4 === $r[4][0] && false !== \strpos($r[1], '>') ||
                5 === $r[4][0] && false !== \strpos($r[1], ']]>')
            ) {
                if ("" !== \trim($row)) {
                    $r[1] .= $raw . "\n";
                }
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
            $now = rows($raw, $lot, -1)[0][0] ?? null; // Do not capture abbreviation(s), reference(s), and note(s)
            if ($now && 'blockquote' === $now[0]) {
                $r[1] .= $now[1];
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#paragraph-continuation-text>
            if ($now && ('p' === $now[0] || 'pre' === $now[0] && "\t" === $now[4][1]) && "" !== $now[1]) {
                // <https://spec.commonmark.org/0.31.2#example-238>
                // Also, check if the last block type in the block quote stream accepts paragraph continuation text. If
                // it does accept it, treat the current potential paragraph continuation text as a new block.
                $test = \end(rows($r[1], $lot)[0]) ?: null;
                if ($test && 'p' !== $test[0]) {
                    $rows[] = $r;
                    $r = rows($raw, $lot)[0][0] ?? null;
                    continue;
                }
                $r[1] .= $raw . "\n";
                continue;
            }
            $rows[] = $r;
            $r = rows($raw, $lot)[0][0] ?? null;
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
                $r[1] .= \substr(s($raw, 4), 4) . "\n";
                continue;
            }
            if ($d >= 4) {
                $r[1] .= \substr(s($raw, 4), 4) . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-114>
            $rows[] = $r;
            $r = rows($raw, $lot)[0][0] ?? null;
            continue;
        }
        if ($d >= 4 && (!$r || 'p' !== $r[0])) {
            $r = ['pre', $row . "\n", (object) ['class' => false], 4, [1, "\t"]];
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
            [$row, $attr] = a($row);
            $rows[] = ['h' . $n, $row . "\n", $attr, $d, [$n, '#']];
            $r = null;
            continue;
        }
        // There is no formal specification for the abbreviation block in CommonMark, so I will treat it similarly to
        // the link reference definition block. It acts as a leaf block that cannot interrupt a paragraph. It can span
        // multiple line(s), but it cannot contain any blank line(s).
        if ($deep >= 0 && '*' === $c && '[' === ($row[1] ?? 0)) {
            // <https://spec.commonmark.org/0.31.2#example-213>
            if ($r && "" !== $r[1]) {
                if ('p' === $r[0]) {
                    $r[1] .= $raw . "\n";
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#example-214>
                $rows[] = $r;
            }
            $r = [1, $row . "\n", (object) [], $d, []];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#ordered-list>
        if (($n = \strspn($row, $c3)) && false !== \strpos(').', $row[$n] ?? $x) && ($w = \strspn($row . ' ', " \t", $n + 1))) {
            // <https://spec.commonmark.org/0.31.2#start-number>
            $at = (int) \substr($row, 0, $n);
            $void = \substr($row, 0, $n + 1) === \trim($row);
            if ($r && "" !== $r[1]) {
                // <https://spec.commonmark.org/0.31.2#example-285>
                // <https://spec.commonmark.org/0.31.2#example-304>
                if (1 !== $at || $void) {
                    $r[1] .= $row . "\n";
                    continue;
                }
                $rows[] = $r;
            }
            $r = ['ol', \substr($row, $n + 1 + $w) . "\n", (object) ['start' => $at], $d, [$n + 1 + $w, $at, $row[$n]]];
            // <https://spec.commonmark.org/0.31.2#example-284>
            if ($void) {
                $rows[] = $r;
                $r = null;
            }
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
                    $r = [false, $raw . "\n", (object) [], $d, [2]];
                    if (false !== \strpos($row, '-->')) {
                        // End on its own line
                        $rows[] = $r;
                        $r = null;
                    }
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#cdata-section>
                // <https://spec.commonmark.org/0.31.2#example-182>
                if ('[CDATA[' === \substr($block, 1, 7)) {
                    $r = [false, $raw . "\n", (object) [], $d, [5]];
                    if (false !== \strpos($row, ']]>')) {
                        // End on its own line
                        $rows[] = $r;
                        $r = null;
                    }
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#declaration>
                // <https://spec.commonmark.org/0.31.2#example-181>
                if (\strspn(\strtolower($block), $c1, 1)) {
                    $r = [false, $raw . "\n", (object) [], $d, [4]];
                    if (false !== \strpos($row, '>')) {
                        // End on its own line
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
                $r = [false, $raw . "\n", (object) [], $d, [3]];
                if (false !== \strpos($row, '?>')) {
                    // End on its own line
                    $rows[] = $r;
                    $r = null;
                }
                continue;
            }
            if (isset($blocks[1][$block = \strtolower($block)])) {
                // <https://spec.commonmark.org/0.31.2#example-185>
                if ($r && "" !== $r[1]) {
                    $rows[] = $r;
                }
                $r = [false, $raw . "\n", (object) [], $d, [1, $block]];
                if (false !== \stripos($row, '</' . $block . '>')) {
                    // End on its own line
                    $rows[] = $r;
                    $r = null;
                }
                continue;
            }
            // HTML block type 6 does not differentiate between open and close tag(s). The initial tag does not need to
            // be a valid HTML tag. As long as it starts like one, it is still valid. Even a start tag that looks like
            // `<div <?asdf [asdf] --`, is still considered a valid HTML block type 6.
            if (isset($blocks[6][$block_6 = \trim($block, '/')])) {
                // <https://spec.commonmark.org/0.31.2#example-185>
                if ($r && "" !== $r[1]) {
                    $rows[] = $r;
                }
                $r = [false, $raw . "\n", (object) [], $d, [6, $block_6]];
                continue;
            }
            if ('>' === \substr($test = \trim($row), -1)) {
                // <https://spec.commonmark.org/0.31.2#open-tag>
                $k = 1;
                $test = \rtrim(\substr($test, 1, -1));
                // <https://spec.commonmark.org/0.31.2#closing-tag>
                if ('/' === ($test[0] ?? 0)) {
                    $k = 0;
                    $test = \substr($test, 1);
                }
                // <https://spec.commonmark.org/0.31.2#open-tag>
                if ('/' === \substr($test, -1)) {
                    $k = 2;
                    $test = \substr($test, 0, -1);
                }
                $test = \trim(\strtolower($test));
                // <https://spec.commonmark.org/0.31.2#tag-name>
                if (\strspn($test, $c1) && ($n = \strspn($test, $c4, 1))) {
                    // An opening or closing tag with no attribute(s)
                    if ("" === ($test = \trim(\substr($test, $n + 1)))) {
                        if ($r && "" !== $r[1]) {
                            // <https://spec.commonmark.org/0.31.2#example-187>
                            if ('p' === $r[0]) {
                                $r[1] .= $raw . "\n";
                                continue;
                            }
                            $rows[] = $r;
                        }
                        $r = [false, $raw . "\n", (object) [], $d, [7]];
                        continue;
                    }
                    if (0 !== $k) {
                        $e = true;
                        $limit = \strlen($test);
                        for ($i = 0; $i < $limit; ++$i) {
                            while ($i < $limit && false !== \strpos(" \t", $test[$i])) {
                                ++$i; // Skip white-space(s) after tag name
                            }
                            if ($i >= $limit) {
                                break;
                            }
                            // <https://spec.commonmark.org/0.31.2#attribute-name>
                            if (false === \strpos($c5, $test[$i])) {
                                $e = false;
                                break;
                            }
                            while ($i < $limit && false !== \strpos($c6, $test[$i])) {
                                ++$i;
                            }
                            // <https://spec.commonmark.org/0.31.2#attribute-value-specification>
                            while ($i < $limit && false !== \strpos(" \t", $test[$i])) {
                                ++$i; // Skip white-space(s) after tag attribute name if any
                            }
                            // <https://spec.commonmark.org/0.31.2#attribute-value>
                            if ($i < $limit && '=' === $test[$i]) {
                                ++$i; // Go to one character after `=`
                                // <https://spec.commonmark.org/0.31.2#attribute-value-specification>
                                while ($i < $limit && false !== \strpos(" \t", $test[$i])) {
                                    ++$i; // Skip white-space(s) after `=` if any
                                }
                                if ($i >= $limit) {
                                    $e = false;
                                    break;
                                }
                                $q = $test[$i];
                                // <https://spec.commonmark.org/0.31.2#single-quoted-attribute-value>
                                // <https://spec.commonmark.org/0.31.2#double-quoted-attribute-value>
                                if ("'" === $q || '"' === $q) {
                                    ++$i; // Go to one character after `'` or `"`
                                    $e = false;
                                    while ($i < $limit) {
                                        if ($q === $test[$i]) {
                                            $e = true;
                                            break;
                                        }
                                        ++$i;
                                    }
                                    if (!$e) {
                                        break;
                                    }
                                } else {
                                    // <https://spec.commonmark.org/0.31.2#unquoted-attribute-value>
                                    if (false !== \strpos(" \t'" . '"<=>`', $test[$i])) {
                                        $e = false;
                                        break;
                                    }
                                    while ($i < $limit && false === \strpos(" \t'" . '"<=>`', $test[$i])) {
                                        ++$i; // Skip white-space(s) after bare attribute value
                                    }
                                    --$i; // Put the cursor back right after the un-quoted attribute value
                                }
                            } else {
                                --$i; // Put the cursor back right after the attribute name if there is no value
                            }
                        }
                        if ($e) {
                            if ($r && "" !== $r[1]) {
                                // <https://spec.commonmark.org/0.31.2#example-187>
                                if ('p' === $r[0]) {
                                    $r[1] .= $raw . "\n";
                                    continue;
                                }
                                $rows[] = $r;
                            }
                            $r = [false, $raw . "\n", (object) [], $d, [7]];
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
            // Expand the `\t` after the block quote marker to 4 column(s), and then normalize the required 4-column(s)
            // indentation of an indented-style code block into space(s).
            $row = s(\substr($row, 1), 8, 1);
            // Now, we can drop the optional white-space (it is a literal space now) after the block quote marker.
            if (' ' === ($row[0] ?? 0)) {
                $row = \substr($row, 1);
            }
            $r = ['blockquote', $row . "\n", (object) [], $d];
            continue;
        }
        // There is no formal specification for note block in CommonMark. For now, the closest fit is to treat it as a
        // container block. It can span multiple line(s) and may contain blank line(s), just like the list block.
        // However, since its label syntax is very similar to that of the link reference definition’s label syntax, it
        // will also be treated in the same way, so that it cannot interrupt a paragraph.
        if ($deep >= 0 && '[' === $c && '^' === ($row[1] ?? 0)) {
            // <https://spec.commonmark.org/0.31.2#example-213>
            if ($r && "" !== $r[1]) {
                if ('p' === $r[0]) {
                    $r[1] .= $raw . "\n";
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#example-214>
                $rows[] = $r;
            }
            $r = [2, $row . "\n", (object) [], $d, []];
            continue;
        }
        if ($deep >= 0 && '[' === $c) {
            // <https://spec.commonmark.org/0.31.2#example-213>
            if ($r && "" !== $r[1]) {
                if ('p' === $r[0]) {
                    $r[1] .= $raw . "\n";
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#example-214>
                $rows[] = $r;
            }
            $r = [0, $row . "\n", (object) [], $d, []];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#bullet-list>
        if (('*' === $c || '+' === $c || '-' === $c) && ($w = \strspn($row . ' ', " \t", 1))) {
            $void = $c === \trim($row);
            if ($r && "" !== $r[1]) {
                // <https://spec.commonmark.org/0.31.2#example-285>
                if ($void) {
                    $r[1] .= $row . "\n";
                    continue;
                }
                $rows[] = $r;
            }
            $r = ['ul', \substr($row, 1 + $n) . "\n", (object) [], $d, [1 + $w, $c, ""]];
            // <https://spec.commonmark.org/0.31.2#example-284>
            if ($void) {
                $rows[] = $r;
                $r = null;
            }
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#code-fence>
        if (('`' === $c || '~' === $c) && ($n = \strspn($row, $c)) && $n >= 3) {
            // <https://spec.commonmark.org/0.31.2#info-string>
            $rest = \trim(\substr($row, $n));
            // <https://spec.commonmark.org/0.31.2#example-145>
            if ('`' === $c && false !== \strpos($rest, $c)) {
                if ($r && "" !== $r[1]) {
                    $r[1] .= $row . "\n";
                }
                $r = ['p', $row . "\n", (object) [], $d];
                continue;
            }
            if ($r && "" !== $r[1]) {
                // <https://spec.commonmark.org/0.31.2#example-140>
                $rows[] = $r;
            }
            $r = ['pre', "\n", (object) attr($rest), $d, [$n, $c]];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#setext-heading>
        if (('-' === $c || '=' === $c) && \strspn($row, $c) === \strlen($row) && $r && 'p' === $r[0] && "" !== $r[1]) {
            [$row, $attr] = a(\substr($r[1], 0, -1));
            $r[0] = 'h' . ($n = '-' === $c ? 2 : 1);
            $r[1] = $row . "\n";
            $r[2] = $attr;
            $r[4] = [$n, $c];
            $rows[] = $r;
            $r = null;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#thematic-break>
        if (('*' === $c || '-' === $c || '_' === $c) && \strspn($row, $c . " \t") == \strlen($row) && \substr_count($row, $c) >= 3) {
            if ($r && "" !== $r[1]) {
                $rows[] = $r;
            }
            $rows[] = ['hr', false, [], $d, [$c]];
            $r = null;
            continue;
        }
        if (false !== \strpos($row, '|')) {}
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
        $r = ['p', $row . "\n", (object) [], $d];
    }
    if ($r && "" !== $r[1]) {
        // At this point, there is probably an open block that has reached the forced last `\n` character. It should be
        // removed; otherwise, the result will have too many extra `\n` character(s) at the end of the block content.
        if (2 === $r[0]) {
            $r[1] = \trim($r[1], "\n") . "\n";
            if ($note = note($r[1])) {
                $r[4] = $note;
            } else {
                $r[0] = 'p';
            }
        } else if ('pre' === $r[0]) {
            if ('`' === $r[4][1] || '~' === $r[4][1]) {
                $r[1] = \substr($r[1], 1);
            }
            $r[1] = \substr($r[1], 0, -1);
        }
        $rows[] = $r;
    }
    if (true === $deep || $deep > 0) {
        foreach ($rows as $k => &$v) {
            // Put the abbreviation, reference, and note block(s) into the batch!
            if (0 === $v[0] || 1 === $v[0] || 2 === $v[0]) {
                // <https://spec.commonmark.org/0.31.2#example-204>
                if (!isset($lot[$v[0]]->{$v[4][0]})) {
                    $lot[$v[0]]->{$v[4][0]} = 0 === $v[0] ? \array_slice($v[4], 1) : $v[4][1];
                }
                unset($rows[$k]);
            }
            // unset($v[3], $v[4]);
        }
        unset($v);
    }
    return [\array_values($rows), $lot];

    foreach ($raws as $raw) {
        [$row, $d] = d($raw, 4);
        if (0 === $r[0]) {
            $r[1] = \ltrim($r[1], "\n");
            // <https://spec.commonmark.org/0.31.2#example-197>
            if ("" === \trim($row)) {
                // TODO: Strict pattern without regular expression for link reference definition
                if (\preg_match('/^\S+$/', \trim($r[1]))) {
                    $r[1] = s2($r[1], \strspn($r[1], ' '), $r[4][1]);
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
                    $r[1] = s2($r[1], \strspn($r[1], ' '), $r[4][1]);
                    $rows[] = $r;
                    $r = $row_new;
                    continue;
                }
                $r[1] .= $raw . "\n";
                continue;
            }
            // TODO: Strict pattern without regular expression for link reference definition
            if (\preg_match('/^\S+$/', \trim($r[1]))) {
                $r[1] = s2($r[1], \strspn($r[1], ' '), $r[4][1]);
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
            $r[1] = s2($r[1] . "\n", \strspn($r[1], ' '), $r[4][1]);
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
            [$row, $attr] = a($row);
            $rows[] = ['h' . $n, $row . "\n", $attr, $d, [$n, '#']];
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
            $r = ['pre', "", (object) attr($rest), $d, [$n, $row[0]]];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#setext-heading>
        if (('-' === $c || '=' === $c) && \strspn($row, $c) === \strlen($row) && 'p' === $r[0] && "" !== $r[1]) {
            [$row, $attr] = a(\substr($r[1], 0, -1));
            $r[0] = 'h' . ($n = '-' === $c ? 2 : 1);
            $r[1] = $row . "\n";
            $r[2] = $attr;
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
        $r[1] = s2($r[1], \strspn($r[1], ' '), $r[4][1]);
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

function s(string $text, int $max = 4, int $n = 0) {
    $limit = \strlen($text);
    $r = "";
    for ($i = 0; $i < $limit; ++$i) {
        if ($n >= $max) {
            return $r . \substr($text, $i);
        }
        $c = $text[$i];
        if (' ' === $c) {
            $r .= $c;
            $n += 1;
            continue;
        }
        if ("\t" === $c) {
            $z = 4 - ($n % 4);
            if ($n + $z > $max) {
                $r .= \str_repeat(' ', $max - $n);
                return $r . \substr($text, $i);
            }
            $r .= \str_repeat(' ', $z);
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

function s2(string $text, int $d = 0) {
    $r = "";
    foreach (\explode("\n", $text) as $v) {
        if (($n = \strspn($v, ' ')) >= $d) {
            $v = \substr($v, $d);
        }
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
    echo \htmlspecialchars(\json_encode(rows($text, $lot, 1), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
    echo '</pre>';
    echo '</div>';
}