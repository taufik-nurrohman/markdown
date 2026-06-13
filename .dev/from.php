<?php

namespace x\markdown {
    function from(?string $value, $block = true): ?string {
        return ""; // TODO
    }
}

/*

    !
    #
    *
    +
    -
    1
    :
    <
    =
    >
    [
    _
    `
    |
    ~

*/

namespace x\markdown\from {
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
    const c1 = " \t";
    const c2 = c1 . "\n";
    const c3 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    const c4 = '0123456789';
    const c5 = c3 . c4 . '-';
    const c6 = c3 . ':_';
    const c7 = c5 . '.:_';
    const c8 = '!"#$%&()*+,-./:;<=>?@[]^_`{|}~' . "'\\";
    const deep = 25; // A regular user would likely never reach this maximum level of recursion.
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
                ++$esc;
            }
            if (0 !== $esc % 2) {
                continue;
            }
            // Capture the first unescaped `{` that meets the white-space condition
            if ('{' === $c) {
                if (0 === $i || false !== \strpos(c1, $row[$i - 1])) {
                    $at = $i;
                }
                break;
            }
        }
        if (false !== $at) {
            $r[0] = \rtrim(\substr($row, 0, $at));
            $r[1] = a1(\substr($row, $at));
        }
        return $r;
    }
    function a1(string $text) {
        // Force a space at the end of the text. This will make processing easier.
        $limit = \strlen($text = \trim($text) . ' ');
        $r = [];
        $s = "";
        // With attribute syntax wrapped by `{` and `}`, an attribute name immediately followed by white-space will be
        // treated as a toggle attribute. Alternatively, without such wrapping, an attribute name immediately followed
        // by white-space will be treated as a class name preceded by the `language-` prefix.
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
                if ('class' === $k) {
                    $r[$k] = \array_replace($r[$k] ?? [], \array_flip(s1($s)));
                } else {
                    $r[$k] = $s;
                }
                $s = "";
                continue;
            }
            if ("" === $s && false !== \strpos(c1, $c)) {
                continue;
            }
            if ("" !== $s && false !== \strpos(c1 . '#.', $c)) {
                if ('#' === $s[0] && "" !== ($s = \substr($s, 1))) {
                    $r['id'] = $s;
                } else if ('.' === $s[0] && "" !== ($s = \substr($s, 1))) {
                    $r['class'][$s] = 1;
                } else {
                    $b ? ($r[$s] = true) : ($r['class']['language-' . $s] = 1);
                }
                $s = false !== \strpos(c1, $c) ? "" : $c;
                continue;
            }
            $s .= $c;
        }
        if (\is_array($set = $r['class'] ?? 0)) {
            \ksort($set);
            $r['class'] = \implode(' ', \array_keys($set));
        }
        \ksort($r);
        return $r;
    }
    // <https://spec.commonmark.org/0.31.2#link-reference-definition>
    function r0(string $text) {
        if ('[' !== ($text[0] ?? 0)) {
            return [];
        }
        $i = 1; // Start after `[`
        $r = [null, [null, null, []]];
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
        $r[0] = s2(v(\trim(\substr($text, 1, $n - 1))));
        // <https://spec.commonmark.org/0.31.2#example-551>
        if ("" === $r[0]) {
            return []; // Link label cannot be empty
        }
        $limit = \strlen($r[0]);
        // <https://spec.commonmark.org/0.31.2#character>
        // Link label can have at most 999 character(s) inside the `[` and `]` character(s). It originally considers
        // line(s) to be composed of character(s) rather than byte(s), but this one counts byte(s) for simplicity.
        if ($limit > 999) {
            return [];
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
            $r[1][0] = ""; // Link destination cannot be empty, unless it is `<>`
            $text = \substr($text, 2);
        } else if ('<' === $text[0]) {
            if (false === ($n = \strpos($text, '>'))) {
                return []; // Unclosed :(
            }
            $r[1][0] = \substr($text, 0, $n + 1);
            // <https://spec.commonmark.org/0.31.2#example-491>
            if (false !== \strpos($r[1][0], "\n")) {
                return []; // Link destination cannot contain line break
            }
            $limit = \strlen($r[1][0]) - 1;
            for ($j = 1; $j < $limit; ++$j) {
                if ("\\" === $r[1][0][$j]) {
                    ++$j;
                    continue;
                }
                if ('<' === $r[1][0][$j] || '>' === $r[1][0][$j]) {
                    return []; // Link destination cannot contain unescaped `<` and `>` character(s)
                }
            }
            $text = \substr($text, $n + 1);
        } else {
            if (!$n = \strcspn($text, " \n\t")) {
                return [];
            }
            $r[1][0] = \substr($text, 0, $n);
            $d = 0;
            $limit = \strlen($r[1][0]);
            for ($j = 0; $j < $limit; ++$j) {
                if ("\\" === $r[1][0][$j]) {
                    ++$j;
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#example-496>
                if ('(' === $r[1][0][$j]) {
                    ++$d;
                } else if (')' === $r[1][0][$j]) {
                    if (0 === $d) {
                        return [];
                    }
                    --$d;
                }
                if ('<' === $r[1][0][$j]) {
                    return [];
                }
            }
            if (0 !== $d) {
                return []; // Unclosed :(
            }
            $text = \substr($text, $n);
        }
        // <https://spec.commonmark.org/0.31.2#example-201>
        if ("" !== $text && !($n = \strspn($text, c2))) {
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
                    $r[1][1] = v(\substr($text, 1, $n - 1));
                    $text = \substr($text, $n + 1);
                }
            }
            // It has a title that can be followed by attribute(s), which must be preceded by a white-space
            if ("" !== $text && !($n = \strspn($text, c2))) {
                if (null !== $r[1][1]) {
                    return [];
                }
            }
            // Attribute(s) exist right after the link destination
            if ("" !== ($text = \substr($text, $n))) {
                $r[1][2] = \trim($text);
                // <https://spec.commonmark.org/0.31.2#example-209>
                if ('{' !== $r[1][2][0] || '}' !== \substr($r[1][2], -1) || "\\" === \substr($r[1][2], -2, 1)) {
                    return []; // This part must be junk text after the link destination or title
                }
                $r[1][2] = a1($r[1][2]);
            }
        }
        return $r;
    }
    function r1(string $text) {
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
            return [s2(v($key)), $value . "\n"];
        }
        return [];
    }
    function r2(string $text) {
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
            $n && ($value = s3($value, $n));
            if ("" === $key || "" === $value) {
                return []; // Note key and content cannot be empty
            }
            return [s2(v($key)), $value . "\n"];
        }
        return [];
    }
    function raw(?string $value): array {
        $lot = [];
        return row($value ?? "", $lot, deep);
    }
    function raws(?string $value): array {
        $lot = [];
        $r = rows($value ?? "", $lot, deep);
        // Parse the note block(s)
        if (!empty($r[1][2])) {
            foreach ($r[1][2] as &$v) {
                $v = rows($v, $lot, deep - 1)[0];
            }
            unset($v);
        }
        return $r;
    }
    function row(string $value, array &$lot = [], int $deep = 0) {
        // TODO
        return \trim($value);
    }
    function rows(string $value, array &$lot = [], int $deep = 0) {
        $lot = \array_replace([[], [], []], $lot);
        if ("" === \trim($value)) {
            return [[], $lot, 0];
        }
        $max = \count($raws = \explode("\n", \rtrim(\strtr($value, [
            "\r\n" => "\n",
            "\r" => "\n"
        ]), "\n") . "\n")) - 1;
        $r = null;
        $rows = [];
        // Count the number of “real” blank line(s) between block(s) to determine if a list is loose or tight.
        $void = 0;
        foreach ($raws as $at => $raw) {
            if ($d = \strspn($row = s($raw, 4), ' ')) {
                $row = \substr($row, $d);
            }
            if ($r && 0 === $r[0]) {
                // A link reference definition cannot contain blank line(s).
                if ("" === $row) {
                    if ($r0 = r0($r[1])) {
                        $r[4] = $r0;
                        $rows[] = $r;
                        $r = null;
                        ++$void;
                        continue;
                    }
                    // A blank line closes the current potential link reference definition. If the potential link
                    // reference definition is not valid, treat it as a normal paragraph.
                    $r[0] = 'p';
                    $rows[] = $r;
                    $r = null;
                    ++$void;
                    continue;
                }
                // Current line validates the link reference definition.
                if ($r0 = r0($r[1] . $raw)) {
                    $r[4] = $r0;
                    $rows[] = $r;
                    $r = null;
                    continue;
                }
                // Current line invalidates the link reference definition. Try to validate the previous chunk. If it is
                // a valid link reference definition, then we can assume that the next line can start a new block.
                if ($r0 = r0($r[1])) {
                    $r[4] = $r0;
                    $rows[] = $r;
                    // Assume the next line starts a new block
                    $r = rows($raw, $lot)[0][0] ?? null;
                    continue;
                }
                // At this point, current link reference definition stream is not yet a valid link reference definition.
                // We can assume that the current line may start a new block. However, if the current line is a
                // paragraph, consider it as a continuation of the link reference definition, which is to be validated
                // later.
                $now = rows($raw, $lot)[0][0] ?? null;
                if ($now && 'p' === $now[0]) {
                    $r[1] .= $now[1];
                    continue;
                }
                // A new block interrupts the current link reference definition stream, causing it to become invalid.
                if ($now) {
                    $r[0] = 'p';
                }
                $rows[] = $r;
                $r = $now;
                continue;
            }
            if ($r && 1 === $r[0]) {
                // An abbreviation definition cannot contain blank line(s).
                if ("" === $row) {
                    if ($r1 = r1($r[1])) {
                        $r[4] = $r1;
                        $rows[] = $r;
                        $r = null;
                        ++$void;
                        continue;
                    }
                    $r[0] = 'p';
                    $rows[] = $r;
                    $r = null;
                    ++$void;
                    continue;
                }
                // Check if the current line can continue the abbreviation definition.
                $now = rows($raw, $lot)[0][0] ?? null;
                if ($now && 'p' === $now[0]) {
                    $r[1] .= $now[1];
                    continue;
                }
                if ($now && false === $now[0] && 7 === $now[4][0]) {
                    $r[1] .= $now[1];
                    continue;
                }
                // At this point, the new block should interrupt the current abbreviation definition stream.
                if ($r1 = r1($r[1])) {
                    $r[4] = $r1;
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
            if ($r && 2 === $r[0]) {
                // A note can have blank line(s), just like list(s).
                if ("" === $row) {
                    $r[1] .= "\n";
                    continue;
                }
                // Multi-line note(s) don’t have to be indented by four space(s) as required by Markdown Extra. A space
                // or tab is enough to continue the note. Since there is no formal specification for this block, the
                // best approach is probably to use the minimum indent rule for list item continuation where the note
                // label acts as the list item marker. However, since note label(s) can vary in length, a less strict
                // rule would be better for this block. Multiple note label(s) with note content below them would look
                // more organized if they are all lined up at the same indentation level.
                if ($d > 0) {
                    if (\strspn($raw = s($raw, $d), ' ') > $r[3]) {
                        $raw = \substr($raw, $r[3]);
                    }
                    $r[1] .= $raw . "\n";
                    continue;
                }
                $now = rows($raw, $lot)[0][0] ?? null;
                if ($now && 'p' === $now[0] && "\n\n" !== \substr($r[1], -2)) {
                    $r[1] .= $now[1];
                    continue;
                }
                $r[1] = \trim($r[1], "\n") . "\n";
                if ($r2 = r2($r[1])) {
                    $r[4] = $r2;
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
                if (1 === $r[4][0] && isset(b1[$r[4][1]]) && false !== \stripos($r[1], '</' . $r[4][1] . '>')) {
                    if ("" !== $row) {
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
                    if ("" !== $row) {
                        $r[1] .= $raw . "\n";
                    }
                    $rows[] = $r;
                    $r = null;
                    continue;
                }
                // HTML block type 6, and 7
                if ((6 === $r[4][0] && isset(b6[$r[4][1]]) || 7 === $r[4][0]) && "" === $row) {
                    $rows[] = $r;
                    $r = null;
                    ++$void;
                    continue;
                }
                $r[1] .= $raw . "\n";
                continue;
            }
            if ($r && 'blockquote' === $r[0]) {
                $now = rows($raw, $lot)[0][0] ?? null;
                if ($now && 'blockquote' === $now[0]) {
                    $r[1] .= $now[1];
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#paragraph-continuation-text>
                if ($now && (false === $now[0] && 7 === $now[4][0] || 'pre' === $now[0] && "\t" === $now[4][1] || \in_array($now[0], [0, 1, 2, 'p'], true))) {
                    // <https://spec.commonmark.org/0.31.2#example-238>
                    // Also, check if the last block type in the block quote stream accepts paragraph continuation text.
                    // If it does accept it, treat the current potential paragraph continuation text as a new block.
                    $test = \end(rows($r[1], $lot)[0]) ?: null;
                    if ($test && !\in_array($test[0], [0, 1, 2, 'dl', 'ol', 'ul', 'p'], true)) {
                        $rows[] = $r;
                        $r = $now;
                        continue;
                    }
                    $r[1] .= $raw . "\n";
                    continue;
                }
                $rows[] = $r;
                $r = $now;
                continue;
            }
            if ($r && 'dl' === $r[0]) {
                if ("" === $row) {
                    $r[1] .= "\n";
                    continue;
                }
                $now = rows($raw, $lot)[0][0] ?? null;
                if ($d >= $r[3] + $r[4][0]) {
                    if (\strspn($r[1], "\n") === ($n = \strlen($r[1])) && $n > 1) {
                        $r[1] = "\n";
                        $rows[] = $r;
                        $r = $now;
                        ++$void;
                        continue;
                    }
                    $r[1] .= \substr(s($raw, $r[3] + $r[4][0]), $r[3] + $r[4][0]) . "\n";
                    continue;
                }
                if ($now && (false === $now[0] && 7 === $now[4][0] || 'p' === $now[0]) && "\n\n" !== \substr($r[1], -2)) {
                    if (':' === $now[1][0]) {
                        $now[1] = s($now[1], 6);
                        if (\strspn($now[1] = s($now[1], 6), ' ', 1)) {
                            $now[1] = "\x1e" . \substr($now[1], $r[4][0]);
                        }
                    }
                    $r[1] .= $now[1];
                    continue;
                }
                $r[1] = \trim($r[1], "\n") . "\n";
                // If previous block was closed with a blank line, try to capture the last block in queue. If it is a
                // description list, combine the current definition list with the last block.
                if ($rows && \is_array($last =& $rows[\array_key_last($rows)])) {
                    if ('dl' === $last[0]) {
                        $last[1] .= "\x1e" . $r[1];
                        $r = null;
                        if ($void > 0) {
                            --$void;
                        }
                        unset($last);
                        continue;
                    }
                }
                $rows[] = $r;
                $r = $now;
                continue;
            }
            if ($r && 'ol' === $r[0]) {
                // <https://spec.commonmark.org/0.31.2#example-306>
                if ("" === $row) {
                    $r[1] .= "\n";
                    continue;
                }
                $now = rows($raw, $lot)[0][0] ?? null;
                // <https://spec.commonmark.org/0.31.2#example-307>
                if ($d >= $r[3] + $r[4][0]) {
                    // <https://spec.commonmark.org/0.31.2#example-279>
                    // <https://spec.commonmark.org/0.31.2#example-280>
                    // More than one blank line is too much for a list item that starts with a blank line.
                    if (\strspn($r[1], "\n") === ($n = \strlen($r[1])) && $n > 1) {
                        $r[1] = "\n";
                        $rows[] = $r;
                        $r = $now;
                        ++$void;
                        continue;
                    }
                    $r[1] .= \substr(s($raw, $r[3] + $r[4][0]), $r[3] + $r[4][0]) . "\n";
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#example-284>
                if ("\n" === $r[1] && !('ol' === $now[0] && $now[4][1] >= $r[4][1] && $now[4][2] === $r[4][2])) {
                    $rows[] = $r;
                    $r = $now;
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#lazy-continuation-line>
                if ($now && (false === $now[0] && 7 === $now[4][0] || 'p' === $now[0]) && "\n\n" !== \substr($r[1], -2)) {
                    $r[1] .= $now[1];
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#of-the-same-type>
                if ($now && 'ol' === $now[0] && $now[4][1] >= $r[4][1] && $now[4][2] === $r[4][2]) {
                    // Keep track the last number of the list item(s) to break the list if current number is less than
                    // the previous one. This is not defined in the CommonMark specification. If you don’t like this
                    // behavior, you can comment out this line so that any number can continue the list.
                    $r[4][1] = $now[4][1];
                    // Merge list item with a special separator so that it will be easy to split them later.
                    $r[1] .= "\x1e" . $now[1];
                    continue;
                }
                // At this point, the list block should end due to insufficient indentation level, different list item
                // type, or a new block that interrupts the list.
                $r[1] = \trim($r[1], "\n") . "\n";
                $rows[] = $r;
                $r = $now;
                continue;
            }
            if ($r && 'pre' === $r[0]) {
                if ('`' === $r[4][1] || '~' === $r[4][1]) {
                    $row = s($raw, $r[3]);
                    if ($d = \strspn($row = s($raw, $r[3]), ' ')) {
                        // <https://spec.commonmark.org/0.31.2#example-137>
                        $row = \substr($row, $d > 3 ? 3 : $d);
                    }
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
                if ($d >= 4 || "" === $row) {
                    // <https://spec.commonmark.org/0.31.2#example-112>
                    $r[1] .= \substr(s($raw, 4), 4) . "\n";
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#example-114>
                // At this point, the code block should end due to the indentation level that is less than 4.
                if ("\n\n" === \substr($r[1], -2)) {
                    $r[1] = \substr($r[1], 0, -1);
                    ++$void;
                }
                $rows[] = $r;
                $r = rows($raw, $lot)[0][0] ?? null;
                continue;
            }
            if ($d >= 4 && (!$r || 'p' !== $r[0])) {
                $r = ['pre', \substr(s($raw, 4), 4) . "\n", [], 4, [1, "\t"]];
                continue;
            }
            $c = $row[0] ?? "\x2";
            // <https://spec.commonmark.org/0.31.2#atx-heading>
            if ('#' === $c && ($n = \strspn($row, $c)) && $n < 7 && \strspn($row . ' ', c1, $n)) {
                if ($r) {
                    // <https://spec.commonmark.org/0.31.2#example-70>
                    if ('p' === $r[0] && $d >= 4) {
                        $r[1] .= $raw . "\n";
                        continue;
                    }
                    $rows[] = $r;
                }
                // <https://spec.commonmark.org/0.31.2#example-67>
                $row = \trim(\substr($row, $n));
                $row_test = \rtrim($row, '#');
                // <https://spec.commonmark.org/0.31.2#example-75>
                // <https://spec.commonmark.org/0.31.2#example-76>
                if ($row_test !== $row && "\\" !== \substr($row_test, -1) && false !== \strpos(c1, \substr($row_test, -1))) {
                    $row = \trim($row_test);
                }
                [$row, $a] = a($row);
                $rows[] = ['h' . $n, $row . "\n", $a, $d, [$n, '#']];
                $r = null;
                continue;
            }
            // There is no formal specification for the abbreviation block in CommonMark, so I will treat it similarly
            // to the link reference definition block. It acts as a leaf block that cannot interrupt a paragraph. It can
            // span multiple line(s), but it cannot contain any blank line(s).
            if ('*' === $c && '[' === ($row[1] ?? 0)) {
                // <https://spec.commonmark.org/0.31.2#example-213>
                if ($r) {
                    if ('p' === $r[0]) {
                        $r[1] .= $raw . "\n";
                        continue;
                    }
                    // <https://spec.commonmark.org/0.31.2#example-214>
                    $rows[] = $r;
                }
                $r = [1, $row . "\n", [], $d, []];
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#ordered-list>
            if (($n = \strspn($row, c4)) && $n < 10 && false !== \strpos(').', $row[$n] ?? "\x3") && ($w = \strspn($row . ' ', c1, $n + 1))) {
                // <https://spec.commonmark.org/0.31.2#start-number>
                $start = (int) \substr($row, 0, $n);
                if ($r) {
                    // <https://spec.commonmark.org/0.31.2#example-285>
                    // <https://spec.commonmark.org/0.31.2#example-304>
                    if (1 !== $start || $n + 1 === \strlen($row)) {
                        $r[1] .= $raw . "\n";
                        continue;
                    }
                    $rows[] = $r;
                }
                $r = ['ol', \substr($row, $n + 1 + $w) . "\n", ['start' => $start], $d, [$n + 1 + $w, $start, $row[$n]]];
                // <https://spec.commonmark.org/0.31.2#example-284>
                continue;
            }
            // Since there is no formal specification for the description list block in CommonMark, the closest match
            // will be used. The description term will be treated as a leaf block, just like the heading block. The
            // description details will be treated as a container block, identical to an unordered list, where the
            // bullet marker is a `:`.
            if (':' === $c && ($w = \strspn(($now = s($row, 6)) . ' ', c1, 1))) {
                // If previous block was closed with a blank line, try to capture the last block in queue. It should be
                // a paragraph. Otherwise, the current line is not a valid candidate for the description details block.
                if (!$r && $rows && \is_array($last = $rows[\array_key_last($rows)])) {
                    if ('p' === $last[0]) {
                        $r = \array_pop($rows);
                        if ($void > 0) {
                            --$void;
                        }
                    }
                }
                if ($r && 'p' === $r[0]) {
                    $r[0] = 'dl';
                    $r[1] .= "\x3\x1e" . \substr($now, $w) . "\n";
                    $r[4] = [$w, $c, ""];
                    continue;
                }
                if ($r) {
                    $r[1] .= $raw . "\n";
                    continue;
                }
                $r = ['p', $raw . "\n", [], $d];
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#html-block>
            if ('<' === $c && ($b = \substr($row, 1, \strcspn($row, c1 . '>', 1)))) {
                if ('!' === ($b[0] ?? 0) && '>' !== ($b[1] ?? 0)) {
                    // <https://spec.commonmark.org/0.31.2#example-185>
                    if ($r) {
                        $rows[] = $r;
                    }
                    // <https://spec.commonmark.org/0.31.2#example-179>
                    // <https://spec.commonmark.org/0.31.2#html-comment>
                    if ('--' === \substr($b, 1, 2)) {
                        $r = [false, $raw . "\n", [], $d, [2]];
                        if (false !== \strpos($row, '-->')) {
                            // End on its own line
                            $rows[] = $r;
                            $r = null;
                        }
                        continue;
                    }
                    // <https://spec.commonmark.org/0.31.2#cdata-section>
                    // <https://spec.commonmark.org/0.31.2#example-182>
                    if ('[CDATA[' === \substr($b, 1, 7)) {
                        $r = [false, $raw . "\n", [], $d, [5]];
                        if (false !== \strpos($row, ']]>')) {
                            // End on its own line
                            $rows[] = $r;
                            $r = null;
                        }
                        continue;
                    }
                    // <https://spec.commonmark.org/0.31.2#declaration>
                    // <https://spec.commonmark.org/0.31.2#example-181>
                    if (\strspn($b, c3, 1)) {
                        $r = [false, $raw . "\n", [], $d, [4]];
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
                if ('?' === ($b[0] ?? 0) && '>' !== ($b[1] ?? 0)) {
                    // <https://spec.commonmark.org/0.31.2#example-185>
                    if ($r) {
                        $rows[] = $r;
                    }
                    $r = [false, $raw . "\n", [], $d, [3]];
                    if (false !== \strpos($row, '?>')) {
                        // End on its own line
                        $rows[] = $r;
                        $r = null;
                    }
                    continue;
                }
                if (isset(b1[$b = \strtolower($b)])) {
                    // <https://spec.commonmark.org/0.31.2#example-185>
                    if ($r) {
                        $rows[] = $r;
                    }
                    $r = [false, $raw . "\n", [], $d, [1, $b]];
                    if (false !== \stripos($row, '</' . $b . '>')) {
                        // End on its own line
                        $rows[] = $r;
                        $r = null;
                    }
                    continue;
                }
                // HTML block type 6 does not differentiate between open and close tag(s). The initial tag does not need
                // to be a valid HTML tag. As long as it starts like one, it is still valid. Even a start tag that looks
                // like `<div <?asdf [asdf] --`, is still considered a valid HTML block type 6.
                if (isset(b6[$b6 = \trim($b, '/')])) {
                    // <https://spec.commonmark.org/0.31.2#example-185>
                    if ($r) {
                        $rows[] = $r;
                    }
                    $r = [false, $raw . "\n", [], $d, [6, $b6]];
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
                    // <https://spec.commonmark.org/0.31.2#tag-name>
                    if (\strspn($test = \trim($test), c3) && ($n = \strspn($test, c5, 1))) {
                        // An opening or closing tag with no attribute(s)
                        if ("" === ($test = \trim(\substr($test, $n + 1)))) {
                            if ($r) {
                                // <https://spec.commonmark.org/0.31.2#example-187>
                                if ('p' === $r[0]) {
                                    $r[1] .= $raw . "\n";
                                    continue;
                                }
                                $rows[] = $r;
                            }
                            $r = [false, $raw . "\n", [], $d, [7]];
                            continue;
                        }
                        if (0 !== $k) {
                            $e = true;
                            $limit = \strlen($test);
                            for ($i = 0; $i < $limit; ++$i) {
                                while ($i < $limit && false !== \strpos(c1, $test[$i])) {
                                    ++$i; // Skip white-space(s) after tag name
                                }
                                if ($i >= $limit) {
                                    break;
                                }
                                // <https://spec.commonmark.org/0.31.2#attribute-name>
                                if (false === \strpos(c2, $test[$i])) {
                                    $e = false;
                                    break;
                                }
                                while ($i < $limit && false !== \strpos(c7, $test[$i])) {
                                    ++$i;
                                }
                                // <https://spec.commonmark.org/0.31.2#attribute-value-specification>
                                while ($i < $limit && false !== \strpos(c1, $test[$i])) {
                                    ++$i; // Skip white-space(s) after tag attribute name if any
                                }
                                // <https://spec.commonmark.org/0.31.2#attribute-value>
                                if ($i < $limit && '=' === $test[$i]) {
                                    ++$i; // Go to one character after `=`
                                    // <https://spec.commonmark.org/0.31.2#attribute-value-specification>
                                    while ($i < $limit && false !== \strpos(c1, $test[$i])) {
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
                                        if (false !== \strpos(c1 . '"<=>`' . "'", $test[$i])) {
                                            $e = false;
                                            break;
                                        }
                                        while ($i < $limit && false === \strpos(c1 . '"<=>`' . "'", $test[$i])) {
                                            ++$i; // Skip white-space(s) after bare attribute value
                                        }
                                        --$i; // Put the cursor back right after the un-quoted attribute value
                                    }
                                } else {
                                    --$i; // Put the cursor back right after the attribute name if there is no value
                                }
                            }
                            if ($e) {
                                if ($r) {
                                    // <https://spec.commonmark.org/0.31.2#example-187>
                                    if ('p' === $r[0]) {
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
                if ($r) {
                    $rows[] = $r;
                }
                // Expand the `\t` after the block quote marker to 4 column(s), and then normalize the required
                // 4-column(s) indentation of an indented-style code block into space(s).
                $row = s(\substr($row, 1), 8, 1);
                // Now, we can drop the optional white-space (it is a literal space now) after the block quote marker.
                if (' ' === ($row[0] ?? 0)) {
                    $row = \substr($row, 1);
                }
                $r = ['blockquote', $row . "\n", [], $d];
                continue;
            }
            // There is no formal specification for note block in CommonMark. For now, the closest fit is to treat it as
            // a container block. It can span multiple line(s) and may contain blank line(s), just like the list block.
            // However, since its label syntax is very similar to that of the link reference definition’s label syntax,
            // it will also be treated in the same way, so that it cannot interrupt a paragraph.
            if ('[' === $c && '^' === ($row[1] ?? 0)) {
                // <https://spec.commonmark.org/0.31.2#example-213>
                if ($r) {
                    if ('p' === $r[0]) {
                        $r[1] .= $raw . "\n";
                        continue;
                    }
                    // <https://spec.commonmark.org/0.31.2#example-214>
                    $rows[] = $r;
                }
                $r = [2, $row . "\n", [], $d, []];
                continue;
            }
            if ('[' === $c) {
                // <https://spec.commonmark.org/0.31.2#example-213>
                if ($r) {
                    if ('p' === $r[0]) {
                        $r[1] .= $raw . "\n";
                        continue;
                    }
                    // <https://spec.commonmark.org/0.31.2#example-214>
                    $rows[] = $r;
                }
                $r = [0, $row . "\n", [], $d, []];
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#thematic-break>
            if (('*' === $c || '-' === $c || '_' === $c) && \strspn($row, $c . c1) == \strlen($row) && ($n = \substr_count($row, $c)) >= 3) {
                if ($r) {
                    $rows[] = $r;
                }
                $rows[] = ['hr', false, [], $d, [$c, $n]];
                $r = null;
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#bullet-list>
            if (('*' === $c || '+' === $c || '-' === $c) && ($w = \strspn($row . ' ', c1, 1))) {
                $vo = $c === \trim($row);
                if ($r) {
                    // <https://spec.commonmark.org/0.31.2#example-285>
                    if ($vo) {
                        $r[1] .= $row . "\n";
                        continue;
                    }
                    $rows[] = $r;
                }
                $r = ['ul', \substr($row, 1 + $n) . "\n", [], $d, [1 + $w, $c, ""]];
                // <https://spec.commonmark.org/0.31.2#example-284>
                if ($vo) {
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
                    if ($r) {
                        $r[1] .= $raw . "\n";
                    }
                    $r = ['p', $raw . "\n", [], $d];
                    continue;
                }
                if ($r) {
                    // <https://spec.commonmark.org/0.31.2#example-140>
                    $rows[] = $r;
                }
                $r = ['pre', "\n", a1($rest), $d, [$n, $c]];
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#setext-heading>
            if (('-' === $c || '=' === $c) && \strspn($row, $c) === \strlen($row) && $r && 'p' === $r[0]) {
                [$row, $a] = a(\substr($r[1], 0, -1));
                $r[0] = 'h' . ($n = '-' === $c ? 2 : 1);
                $r[1] = $row . "\n";
                $r[2] = $a;
                $r[4] = [$n, $c];
                $rows[] = $r;
                $r = null;
                continue;
            }
            if (false !== \strpos($row, '|')) {}
            if ("" === $row) {
                if ($r) {
                    $rows[] = $r;
                }
                $r = null;
                if ($at < $max) {
                    ++$void;
                }
                continue;
            }
            if ($r) {
                $r[1] .= $row . "\n";
                continue;
            }
            $r = ['p', $row . "\n", [], $d];
        }
        if ($r) {
            // At this point, there is probably an open block that has reached the forced last `\n` character. It should
            // be removed; otherwise, the result will have too many extra `\n` character(s) at the end of the block.
            if (\in_array($r[0], [2, 'dl', 'ol', 'ul'], true)) {
                $r[1] = \trim($r[1], "\n") . "\n";
            }
            if (2 === $r[0]) {
                if ($r2 = r2($r[1])) {
                    $r[4] = $r2;
                } else {
                    $r[0] = 'p';
                }
            } else if ('pre' === $r[0]) {
                if ('`' === $r[4][1] || '~' === $r[4][1]) {
                    $r[1] = \substr($r[1], 1);
                }
                $r[1] = \substr($r[1], 0, -1);
            }
            if ($r) {
                $rows[] = $r;
            }
        }
        foreach ($rows as $k => &$v) {
            if (false === $v[0]) {
                $v[1] = \trim($v[1], "\n");
                continue;
            }
            if ($deep > 0) {
                // Put the abbreviation, reference, and note block(s) into the batch!
                if (0 === $v[0] || 1 === $v[0] || 2 === $v[0]) {
                    if (1 === $v[0]) {
                        // Collect all abbreviations’ first character(s) to be used later by the `row()` function. This
                        // function reads the line character by character, letting me quickly determine when a character
                        // might start an abbreviation. I want to avoid using regular expression for this task.
                        $lot["\x2"] ??= [];
                        $lot["\x2"][$v[4][0][0]][$v[4][0]] = 1;
                        // <https://spec.commonmark.org/0.31.2#example-204>
                        if (!isset($lot[$v[0]][$v[4][0]])) {
                            $lot[$v[0]][$v[4][0]] = \rtrim($v[4][1]);
                        }
                        unset($rows[$k]);
                        continue;
                    }
                    // <https://spec.commonmark.org/0.31.2#example-204>
                    if (!isset($lot[$v[0]][$v[4][0]])) {
                        $lot[$v[0]][$v[4][0]] = $v[4][1];
                    }
                    unset($rows[$k]);
                    continue;
                }
                if ('blockquote' === $v[0]) {
                    $v[1] = rows($v[1], $lot, $deep - 1)[0] ?: "";
                    continue;
                }
                if ('dl' === $v[0]) {
                    $text = $v[1];
                    $v[1] = [];
                    $loose = false !== \strpos($text, "\n\n\x1e");
                    foreach (\explode("\x1e", $text) as $r) {
                        if ("\x3" === \substr($r, -1)) {
                            // The description term block comes with a special case. A description term block that
                            // contains line break(s) will be treated as if it consisted of more than one description
                            // term. This behavior differs from how most block(s) in CommonMark are treated.
                            foreach (\explode("\n", \trim(\substr($r, 0, -1))) as $t) {
                                $v[1][] = ['dt', row($t, $lot, $deep - 1), []];
                            }
                            continue;
                        }
                        $r = rows($r, $lot, $deep - 1);
                        $v[1][] = ['dd', $r[0], []];
                        if ($r[2] > 0) {
                            $loose = true;
                        }
                    }
                    if (!($v[4][3] = $loose) && $v[1]) {
                        foreach ($v[1] as &$r) {
                            if (\is_array($r[1] ?: 0) && \count($r[1]) < 3 && 'p' === $r[1][0][0]) {
                                if (\in_array($r[1][1][0] ?? 0, ['dl', 'ol', 'ul'], true)) {
                                    $r[1][0] = $r[1][0][1]; // Remove the surrounding paragraph
                                    continue;
                                }
                                $r[1][0] = $r[1][0][1]; // Remove the surrounding paragraph
                                continue;
                            }
                        }
                        unset($r);
                    }
                    continue;
                }
                if ('pre' === $v[0]) {
                    $v[1] = ['code', \htmlspecialchars($v[1]), $v[2]];
                    $v[2] = [];
                    continue;
                }
                if (\in_array($v[0], ['ol', 'ul'], true)) {
                    $text = $v[1];
                    $v[1] = [];
                    // <https://spec.commonmark.org/0.31.2#loose>
                    // A list is loose if any of its constituent list item(s) are separated by blank line(s) …
                    $loose = false !== \strpos($text, "\n\n\x1e");
                    foreach (\explode("\x1e", $text) as $r) {
                        $r = rows($r, $lot, $deep - 1);
                        $v[1][] = ['li', $r[0] ?: "", []];
                        // … or if any of its constituent list item(s) directly contain two block-level element(s) with
                        // a blank line between them.
                        if ($r[2] > 0) {
                            $loose = true;
                        }
                    }
                    if (!($v[4][3] = $loose) && $v[1]) {
                        foreach ($v[1] as &$r) {
                            if (\is_array($r[1] ?: 0) && \count($r[1]) < 3 && 'p' === $r[1][0][0]) {
                                if (\in_array($r[1][1][0] ?? 0, ['dl', 'ol', 'ul'], true)) {
                                    $r[1][0] = $r[1][0][1]; // Remove the surrounding paragraph
                                    continue;
                                }
                                $r[1][0] = $r[1][0][1]; // Remove the surrounding paragraph
                                continue;
                            }
                        }
                        unset($r);
                    }
                    continue;
                }
                // TODO
                if (false !== $v[1]) {
                    $v[1] = row($v[1], $lot, $deep - 1);
                }
            }
        }
        unset($v);
        return [\array_values($rows), $lot, $void];
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
        $i = 0;
        $limit = \strlen($text);
        $r = [];
        while ($i < $limit) {
            $i += \strspn($text, c2, $i);
            if ($i >= $limit) {
                break;
            }
            $r[] = \substr($text, $i, $n = \strcspn($text, c2, $i));
            $i += $n;
        }
        return $r;
    }
    function s2(string $text) {
        return \implode(' ', s1($text));
    }
    function s3(string $text, int $d = 0) {
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
}