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
    $total = \strlen($text .= ' ');
    for ($i = 0; $i < $total; ++$i) {
        $c = $text[$i];
        if ('=' === $c) {
            $k = $s;
            $s = "";
            if (++$i >= $total) {
                $r[$k] = "";
                break;
            }
            $c = $text[$i];
            $q = 0;
            if ('"' === $c || "'" === $c) {
                $q = $c;
                ++$i;
            }
            while ($i < $total) {
                $c = $text[$i];
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
        if (false !== \strpos(" \t#.", $c) && "" !== $s) {
            if ("" !== $s) {
                if ('#' === $s[0] && "" !== ($s = \substr($s, 1))) {
                    $r['id'] = $s;
                } else if ('.' === $s[0] && "" !== ($s = \substr($s, 1))) {
                    $r['class'][] = $s;
                } else {
                    $r['class'][] = 'language-' . $s;
                }
            }
            $s = false !== \strpos(" \t", $c) ? "" : $c;
            continue;
        }
        $s .= $c;
    }
    if (!empty($r['class'])) {
        \sort($r['class']);
        $r['class'] = \implode(' ', $r['class']);
    }
    \ksort($r);
    return $r;
}

function from(?string $value, $block = true): ?string {}

function n(string $text, int $max = 0, int $tab = 4) {
    $i = $t = 0;
    $total = \strlen($text);
    $limit = $max ? \min($max, $total) : $total;
    while ($i < $limit) {
        $c = $text[$i];
        if ("\t" === $c) {
            ++$i;
            $t += $tab - ($t % $tab);
            continue;
        }
        if (' ' === $c) {
            ++$i;
            ++$t;
            continue;
        }
        // Hit non white-space before reaching `$max`
        break;
    }
    return [\substr($text, $i), $t, $i];
}

function row(string $text, array $lot = []) {}

function rows(string $text, array $lot = []) {
    static $blocks = [];
    $blocks[1] ??= \array_fill_keys(['pre', 'script', 'style', 'textarea'], 1);
    $blocks[6] ??= \array_fill_keys([
        'address', 'article', 'aside', 'base', 'basefont', 'blockquote', 'body', 'caption', 'center', 'col', 'colgroup',
        'dd', 'details', 'dialog', 'dir', 'div', 'dl', 'dt', 'fieldset', 'figcaption', 'figure', 'footer', 'form',
        'frame', 'frameset', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hr', 'html', 'iframe', 'legend',
        'li', 'link', 'main', 'menu', 'menuitem', 'nav', 'noframes', 'ol', 'optgroup', 'option', 'p', 'param', 'search',
        'section', 'summary', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'title', 'tr', 'track', 'ul'
    ], 1);
    $lot[0] ??= []; // Reference(s)
    $lot[1] ??= []; // Abbreviation(s)
    $lot[2] ??= []; // Note(s)
    $raws = \explode("\n", \rtrim(\strtr($text, [
        "\r\n" => "\n",
        "\r" => "\n"
    ]), "\n"));
    $reset = $r = ['p', "", [], 0];
    $rows = [];
    $x = "\x1a";
    foreach ($raws as $raw) {
        [$row, $t] = n($raw, 4);
        if (0 === $r[0]) {}
        if (1 === $r[0] || 2 === $r[0]) {
            if ("" === \trim($row)) {
                $lot[$r[0]][$r[4][1]][0] .= "\n";
                continue;
            }
            if ($t > 0) {
                $lot[$r[0]][$r[4][1]][0] .= \substr($raw, $r[3]) . "\n";
                continue;
            }
            // Get first indent on a non-empty line after the first line, then use it as the indent of the first line
            $d = 0;
            foreach (\explode("\n", $lot[$r[0]][$r[4][1]][0]) as $v) {
                if ("" !== \trim($v) && ($s = \strspn($v, " \t"))) {
                    $d = \substr($v, 0, $s);
                    break;
                }
            }
            if (0 !== $d) {
                $lot[$r[0]][$r[4][1]][0] = $d . \ltrim($lot[$r[0]][$r[4][1]][0]);
            }
            $lot[$r[0]][$r[4][1]][0] = \rtrim($lot[$r[0]][$r[4][1]][0], "\n") . "\n";
            $lot[$r[0]][$r[4][1]][1] = \strlen($d);
            $r = rows($row, $lot)[0][0] ?? $reset;
            continue;
        }
        if (false === $r[0]) {
            // HTML block type 1
            if (1 === $r[4][0] && isset($blocks[1][$r[4][1]]) && false !== \stripos($row, '</' . $r[4][1] . '>')) {
                $r[1] .= $row . "\n";
                $rows[] = $r;
                $r = $reset;
                continue;
            }
            // HTML block type 2, 3, 4, and 5
            if (
                2 === $r[4][0] && false !== \strpos($row, '-->') ||
                3 === $r[4][0] && false !== \strpos($row, '?>') ||
                4 === $r[4][0] && false !== \strpos($row, '>') ||
                5 === $r[4][0] && false !== \strpos($row, ']]>')
            ) {
                $r[1] .= $row . "\n";
                $rows[] = $r;
                $r = $reset;
                continue;
            }
            // HTML block type 6
            if (6 === $r[4][0] && isset($blocks[6][$r[4][1]]) && "" === \trim($row)) {
                $rows[] = $r;
                $r = $reset;
                continue;
            }
            $r[1] .= $row . "\n";
            continue;
        }
        if ('blockquote' === $r[0]) {
            $row_new = rows($row, $lot)[0][0] ?? $reset;
            if ('blockquote' === $row_new[0]) {
                $r[1] .= $row_new[1];
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#paragraph-continuation-text>
            if ('p' === $row_new[0] && "" !== $row_new[1]) {
                $r[1] .= $row . "\n";
                continue;
            }
            $rows[] = $r;
            $r = $reset;
            continue;
        }
        if ('ol' === $r[0]) {
            // <https://spec.commonmark.org/0.31.2#example-306>
            if ("" === \trim($row)) {
                $r[1] .= "\n";
                continue;
            }
            $row_new = rows($row, $lot)[0][0] ?? $reset;
            // <https://spec.commonmark.org/0.31.2#of-the-same-type>
            if ('ol' === $row_new[0] && $row_new[4][2] === $r[4][2] && $t < $r[3] + $r[4][0]) {
                $r[1] .= "\x3" . \substr($row, $row_new[3] + $row_new[4][0]) . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-307>
            if ($t >= $r[3] + $r[4][0]) {
                $r[1] .= \str_repeat(' ', $t) . $row . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#lazy-continuation-line>
            if ('p' === $row_new[0] && $t < $r[3] + $r[4][0] && "\n\n" !== \substr($r[1], -2)) {
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
                $row = n($raw, $r[3])[0];
                if ($row === \str_repeat($r[4][1], $r[4][0])) {
                    $rows[] = $r;
                    $r = $reset;
                    continue;
                }
                $r[1] .= $row . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-111>
            if ("" === \trim($row)) {
                // <https://spec.commonmark.org/0.31.2#example-112>
                $r[1] .= n($raw, 4)[0] . "\n";
                continue;
            }
            $row_new = rows($raw, $lot)[0][0] ?? $reset;
            if ('pre' === $row_new[0]) {
                $r[1] .= $row_new[1];
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-114>
            if ("\t" === $r[4][1] && $t < $r[3]) {
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
            $row_new = rows($row, $lot)[0][0] ?? $reset;
            // <https://spec.commonmark.org/0.31.2#of-the-same-type>
            if ('ul' === $row_new[0] && $row_new[4][1] === $r[4][1] && $t < $r[3] + $r[4][0]) {
                $r[1] .= "\x3" . \substr($row, $row_new[3] + $row_new[4][0]) . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-307>
            if ($t >= $r[3] + $r[4][0]) {
                $r[1] .= \str_repeat(' ', $t) . $row . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#lazy-continuation-line>
            if ('p' === $row_new[0] && $t < $r[3] + $r[4][0] && "\n\n" !== \substr($r[1], -2)) {
                $r[1] .= $row . "\n";
                continue;
            }
            $r[1] = \rtrim($r[1], "\n") . "\n";
            $rows[] = $r;
            $r = $row_new;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#indented-code-block>
        if ($t >= 4) {
            if ("" !== $r[1]) {
                // <https://spec.commonmark.org/0.31.2#example-113>
                if ('p' === $r[0]) {
                    $r[1] .= $row . "\n";
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#example-115>
                $rows[] = $r;
            }
            $r = ['pre', $row . "\n", ['class' => false], $t, [4, "\t"]];
            continue;
        }
        if (($n = \strspn($row, '#')) && $n < 7 && \strspn($row . ' ', " \t", $n)) {
            if ("" !== $r[1]) {
                $rows[] = $r;
            }
            $row = \trim(\substr($row, $n));
            $row_test = \rtrim($row, '#');
            // `# asdf \#`
            if ("\\" === \substr($row_test, -1)) {
                // Keep `#` suffix
            // `# asdf#`
            } else if (false === \strpos(" \t", \substr($row_test, -1))) {
                // Keep `#` suffix
            } else {
                $row = \trim($row_test);
            }
            $rows[] = ['h' . $n, $row . "\n", [], $t, [$n, '#']];
            $r = $reset;
            continue;
        }
        if (0 === \strpos($row, '*[')) {
            $level = 0;
            $n = \strlen($row);
            for ($i = 2; $i < $n; ++$i) {
                $c = $row[$i];
                if ("\\" === $c && $i + 1 < $n) {
                    ++$i;
                    continue;
                }
                if ('[' === $c) {
                    ++$level;
                    continue;
                }
                if (']' === $c) {
                    if ($level > 0) {
                        --$level;
                        continue;
                    }
                }
                $next = $i + 1; // Go to `]`
                while ($next < $n && false !== \strpos(" \t", $row[$next])) {
                    ++$next;
                }
                // There must be a `:` after the optional white-space(s)
                if ($next >= $n || ':' !== $row[$next]) {
                    continue;
                }
                $next += 1; // Go to one character after `:`
                // There must be a white-space after the `:`, unless it marks the end of the row
                if ($next >= $n || false !== \strpos(" \t", $row[$next])) {
                    $key = v(\substr(\trim(\substr($row, 2, $next - 3)), 0, -1));
                    [$value, $s] = n(\substr($row, $next));
                    $lot[1][$key] = [$value . "\n", 0];
                    if ("" !== $r[1]) {
                        $rows[] = $r;
                    }
                    $r = [1, "", [], $t, [$s, $key]];
                    continue 2;
                }
            }
            $r[1] .= $row . "\n";
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#html-block>
        if ('<' === ($row[0] ?? $x) && ($block = \substr($row, 1, \strcspn($row, " \t>", 1)))) {
            if ('!' === ($block[0] ?? $x) && '>' !== ($block[1] ?? $x)) {
                // <https://spec.commonmark.org/0.31.2#example-185>
                if ("" !== $r[1]) {
                    $rows[] = $r;
                }
                // <https://spec.commonmark.org/0.31.2#example-179>
                if ('--' === \substr($block, 1, 2)) {
                    $r = [false, $row . "\n", [], $t, [2, null]];
                    if (false !== \strpos($row, '-->')) {
                        // Ends on its own line
                        $rows[] = $r;
                        $r = $reset;
                    }
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#example-182>
                if ('[CDATA[' === \substr($block, 1, 7)) {
                    $r = [false, $row . "\n", [], $t, [5, null]];
                    if (false !== \strpos($row, ']]>')) {
                        // Ends on its own line
                        $rows[] = $r;
                        $r = $reset;
                    }
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#example-181>
                $r = [false, $row . "\n", [], $t, [4, null]];
                if (false !== \strpos($row, '>')) {
                    // Ends on its own line
                    $rows[] = $r;
                    $r = $reset;
                }
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-180>
            if ('?' === ($block[0] ?? $x) && '>' !== ($block[1] ?? $x)) {
                // <https://spec.commonmark.org/0.31.2#example-185>
                if ("" !== $r[1]) {
                    $rows[] = $r;
                }
                $r = [false, $row . "\n", [], $t, [3, null]];
                if (false !== \strpos($row, '?>')) {
                    // Ends on its own line
                    $rows[] = $r;
                    $r = $reset;
                }
                continue;
            }
            // The initial tag doesn’t need to be a valid tag, as long as it starts like one
            $block = \strtolower($block);
            if (isset($blocks[1][$block])) {
                // <https://spec.commonmark.org/0.31.2#example-185>
                if ("" !== $r[1]) {
                    $rows[] = $r;
                }
                $r = [false, $row . "\n", [], $t, [1, $block]];
                if (false !== \stripos($row, '</' . $block . '>')) {
                    // Ends on its own line
                    $rows[] = $r;
                    $r = $reset;
                }
                continue;
            }
            // HTML block(s) type 6 does not care whether it is an open or close tag
            if (isset($blocks[6][$block_6 = \trim($block, '/')])) {
                // <https://spec.commonmark.org/0.31.2#example-185>
                if ("" !== $r[1]) {
                    $rows[] = $r;
                }
                $r = [false, $row . "\n", [], $t, [6, $block_6]];
                continue;
            }
            $r[1] .= $row . "\n";
            if (0 === $r[3]) {
                $r[3] = $t;
            }
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#example-80>
        if ('-' === ($row[0] ?? $x) && \strspn($row, '-') === \strlen($row) && 'p' === $r[0] && "" !== $r[1]) {
            $r[0] = 'h2';
            $r[4] = [2, '-'];
            $rows[] = $r;
            $r = $reset;
            continue;
        }
        if ('=' === ($row[0] ?? $x) && \strspn($row, '=') === \strlen($row) && 'p' === $r[0] && "" !== $r[1]) {
            $r[0] = 'h1';
            $r[4] = [1, '='];
            $rows[] = $r;
            $r = $reset;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#block-quote-marker>
        if ('>' === ($row[0] ?? $x)) {
            if ("" !== $r[1]) {
                $rows[] = $r;
            }
            [$a, $b] = n($row = \substr($row, 1), 1);
            if ($w = ($b + 1) - 1) {
                $n = $w - 1;
                $row = \substr($row, 1);
                if ($n > 0) {
                    $row = \str_repeat(' ', $n) . $row;
                }
            }
            $r = ['blockquote', $row . "\n", [], $t];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#thematic-break>
        $w = \strtr($row, ["\t" => "", ' ' => ""]);
        if ($w && false !== \strpos('*-_', $w[0]) && \strspn($w, $w[0]) === ($n = \strlen($w)) && $n >= 3) {
            if ("" !== $r[1]) {
                $rows[] = $r;
            }
            $rows[] = ['hr', false, [], $t, [$w[0]]];
            $r = $reset;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2/#bullet-list>
        if (false !== \strpos('*+-', $row[0] ?? $x) && ($s = \strspn($row . ' ', " \t", 1))) {
            $empty = $row[0] === \trim($row);
            if ("" !== $r[1]) {
                // <https://spec.commonmark.org/0.31.2#example-285>
                if ($empty) {
                    $r[1] .= $row . "\n";
                    continue;
                }
                $rows[] = $r;
            }
            $r = ['ul', \substr($row, 1 + $s) . "\n", [], $t, [1 + $s, $row[0], ""]];
            // <https://spec.commonmark.org/0.31.2#example-284>
            if ($empty) {
                $rows[] = $r;
                $r = $reset;
            }
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#ordered-list>
        if (($n = \strspn($row, '0123456789')) && false !== \strpos(').', $row[$n] ?? $x) && ($s = \strspn($row . ' ', " \t", $n + 1))) {
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
            $r = ['ol', \substr($row, $n + 1 + $s) . "\n", ['start' => $at], $t, [$n + 1 + $s, $at, $row[$n]]];
            // <https://spec.commonmark.org/0.31.2#example-284>
            if ($empty) {
                $rows[] = $r;
                $r = $reset;
            }
            continue;
        }
        if ('[' === ($row[0] ?? $x)) {
            // TODO
        }
        // <https://spec.commonmark.org/0.31.2#code-fence>
        if (($n = \strspn($row, '`') ?: \strspn($row, '~')) && $n >= 3) {
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
            $r = ['pre', "", a($rest), $t, [$n, $row[0]]];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#paragraph>
        if ("" === \trim($row)) {
            if ('p' !== $r[0] || "" !== $r[1]) {
                $rows[] = $r;
                $r = $reset;
            }
            continue;
        }
        $r[1] .= $row . "\n";
        if (0 === $r[3]) {
            $r[3] = $t;
        }
    }
    if ("" !== $r[1]) {
        $rows[] = $r;
    }
    return [$rows, $lot];
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
    return strnatcmp(substr($a, 0, -3), substr($b, 0, -3));
});

$current = "";
foreach ($files as $file) {
    if ($current !== ($t = basename(dirname($file, 2)) . '\\' . basename(dirname($file))) || "" === $current) {
        echo '<h2 style="margin:0;">.\\test\\' . $t . '\\*</h2>';
        $current = $t;
    }
    $text = file_get_contents($file);
    echo '<div style="display:flex;gap:1em;margin:1em 0;">';
    echo '<pre style="border:2px solid #f00;flex:1;font:normal normal 12px/1.25 monospace;margin:0;overflow:auto;padding:0 0.25em;">';
    echo \htmlspecialchars($text);
    echo '</pre>';
    echo '<pre style="border:2px solid #00f;flex:1;font:normal normal 12px/1.25 monospace;margin:0;overflow:auto;padding:0 0.25em;">';
    echo \htmlspecialchars(\json_encode(rows($text, []), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
    echo '</pre>';
    echo '</div>';
}