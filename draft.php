<?php namespace x\markdown;

function from(?string $value, $block = true): ?string {}

function n(string $text) {
    if ($t = \strspn($text, ' ')) {
        $text = \substr($text, $t > 4 ? 4 : $t);
    }
    return [$text, $t];
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
    $new = $r = ['p', "", [], 0];
    $raws = \explode("\n", \rtrim(\strtr($text, [
        "\r\n" => "\n",
        "\r" => "\n"
    ]), "\n"));
    $rows = [];
    $x = "\x1a";
    foreach ($raws as $raw) {
        [$row, $t] = n($raw);
        if (false === $r[0]) {
            // HTML block type 1
            if (1 === $r[4][0] && isset($blocks[1][$r[4][1]]) && false !== \stripos($row, '</' . $r[4][1] . '>')) {
                $r[1] .= $row . "\n";
                $rows[] = $r;
                $r = $new;
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
                $r = $new;
                continue;
            }
            // HTML block type 6
            if (6 === $r[4][0] && isset($blocks[6][$r[4][1]]) && "" === \trim($row)) {
                $rows[] = $r;
                $r = $new;
                continue;
            }
            $r[1] .= $row . "\n";
            continue;
        }
        if ('ol' === $r[0]) {
            // <https://spec.commonmark.org/0.31.2#example-306>
            if ("" === \trim($row)) {
                $r[1] .= "\n";
                continue;
            }
            $new_row = rows($row, $lot)[0][0] ?? $new;
            // <https://spec.commonmark.org/0.31.2#of-the-same-type>
            if ('ol' === $new_row[0] && $new_row[4][2] === $r[4][2] && $t < $r[3] + $r[4][0]) {
                $r[1] .= "\x3" . \substr($row, $new_row[3] + $new_row[4][0]) . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-307>
            if ($t >= $r[3] + $r[4][0]) {
                $r[1] .= \str_repeat(' ', $t) . $row . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#lazy-continuation-line>
            if ('p' === $new_row[0] && $t < $r[3] + $r[4][0] && "\n\n" !== \substr($r[1], -2)) {
                $r[1] .= $row . "\n";
                continue;
            }
            $r[1] = \rtrim($r[1], "\n") . "\n";
            $rows[] = $r;
            $r = $new_row;
            continue;
        }
        if ('pre' === $r[0]) {
            // <https://spec.commonmark.org/0.31.2#example-111>
            if ("" === \trim($row)) {
                $r[1] .= "\n";
                continue;
            }
            if ("\t" === $r[4][1] && $t < $r[3]) {
                $rows[] = $r;
                $r = rows($row, $lot)[0][0] ?? $new;
                continue;
            }
        }
        if ('ul' === $r[0]) {
            // <https://spec.commonmark.org/0.31.2#example-306>
            if ("" === \trim($row)) {
                $r[1] .= "\n";
                continue;
            }
            $new_row = rows($row, $lot)[0][0] ?? $new;
            // <https://spec.commonmark.org/0.31.2#of-the-same-type>
            if ('ul' === $new_row[0] && $new_row[4][1] === $r[4][1] && $t < $r[3] + $r[4][0]) {
                $r[1] .= "\x3" . \substr($row, $new_row[3] + $new_row[4][0]) . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-307>
            if ($t >= $r[3] + $r[4][0]) {
                $r[1] .= \str_repeat(' ', $t) . $row . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#lazy-continuation-line>
            if ('p' === $new_row[0] && $t < $r[3] + $r[4][0] && "\n\n" !== \substr($r[1], -2)) {
                $r[1] .= $row . "\n";
                continue;
            }
            $r[1] = \rtrim($r[1], "\n") . "\n";
            $rows[] = $r;
            $r = $new_row;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#indented-code-block>
        if ($t >= 4) {
            $r[0] = 'pre';
            $r[1] .= $row . "\n";
            $r[2]['class'] = false;
            if (0 === $r[3]) {
                $r[3] = $t;
            }
            $r[4] = [4, "\t"];
            continue;
        }
        if (($n = \strspn($row, '#')) && $n < 7 && \strspn($row . ' ', " \t", $n)) {
            if ("" !== $r[1]) {
                $rows[] = $r;
            }
            $rows[] = ['h' . $n, \trim(\substr($row, $n)) . "\n", [], $t, [$n, '#']];
            $r = $new;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#html-block>
        if ('<' === ($row[0] ?? $x) && ($block = \substr($row, 1, \strcspn($row, " \t>", 1)))) {
            if ('!' === ($block[0] ?? $x) && '>' !== ($block[1] ?? $x)) {
                if ("" !== $r[1]) {
                    // All type(s) of HTML block(s) except type 7 may interrupt a paragraph
                    $rows[] = $r;
                }
                if ('--' === \substr($block, 1, 2)) {
                    $r = [false, $row . "\n", [], $t, [2, null]];
                    if (false !== \strpos($row, '-->')) {
                        // Ends on its own line
                        $rows[] = $r;
                        $r = $new;
                    }
                    continue;
                }
                if ('[CDATA[' === \substr($block, 1, 7)) {
                    $r = [false, $row . "\n", [], $t, [5, null]];
                    if (false !== \strpos($row, ']]>')) {
                        // Ends on its own line
                        $rows[] = $r;
                        $r = $new;
                    }
                    continue;
                }
                $r = [false, $row . "\n", [], $t, [4, null]];
                if (false !== \strpos($row, '>')) {
                    // Ends on its own line
                    $rows[] = $r;
                    $r = $new;
                }
                continue;
            }
            if ('?' === ($block[0] ?? $x) && '>' !== ($block[1] ?? $x)) {
                if ("" !== $r[1]) {
                    // All type(s) of HTML block(s) except type 7 may interrupt a paragraph
                    $rows[] = $r;
                }
                $r = [false, $row . "\n", [], $t, [3, null]];
                if (false !== \strpos($row, '?>')) {
                    // Ends on its own line
                    $rows[] = $r;
                    $r = $new;
                }
                continue;
            }
            // The initial tag doesn’t need to be a valid tag, as long as it starts like one
            $block = \strtolower($block);
            if (isset($blocks[1][$block])) {
                if ("" !== $r[1]) {
                    // All type(s) of HTML block(s) except type 7 may interrupt a paragraph
                    $rows[] = $r;
                }
                $r = [false, $row . "\n", [], $t, [1, $block]];
                if (false !== \stripos($row, '</' . $block . '>')) {
                    // Ends on its own line
                    $rows[] = $r;
                    $r = $new;
                }
                continue;
            }
            // HTML block(s) type 6 does not care whether it is an open or close tag
            if (isset($blocks[6][$block_6 = \trim($block, '/')])) {
                if ("" !== $r[1]) {
                    // All type(s) of HTML block(s) except type 7 may interrupt a paragraph
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
            $r = $new;
            continue;
        }
        if ('=' === ($row[0] ?? $x) && \strspn($row, '=') === \strlen($row) && 'p' === $r[0] && "" !== $r[1]) {
            $r[0] = 'h1';
            $r[4] = [1, '='];
            $rows[] = $r;
            $r = $new;
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#thematic-break>
        $w = \strtr($row, ["\t" => "", ' ' => ""]);
        if ($w && false !== \strpos('*-_', $w[0]) && \strspn($w, $w[0]) === ($n = \strlen($w)) && $n >= 3) {
            if ("" !== $r[1]) {
                $rows[] = $r;
            }
            $rows[] = ['hr', false, [], $t, [$w[0]]];
            $r = $new;
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
                $r = $new;
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
                $r = $new;
            }
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#paragraph>
        if ("" === \trim($row)) {
            if ('p' !== $r[0] || "" !== $r[1]) {
                $rows[] = $r;
                $r = $new;
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





$files = glob(__DIR__ . '/draft/*/*/*.md', GLOB_NOSORT);

usort($files, function ($a, $b) {
    return strnatcmp(dirname($a) . '/' . basename($a, '.md'), dirname($b) . '/' . basename($b, '.md'));
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
    echo \htmlspecialchars(\json_encode(rows($text, [])[0], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
    echo '</pre>';
    echo '</div>';
}


echo '<hr>';
echo '<hr>';
echo '<hr>';


foreach ([

    // h
    "# asdf asdf asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n# asdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n# asdf asdf asdf asdf",
    "#\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n#\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n#",

    // h1
    "=\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n=\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n=",
    // h1
    "====================\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n====================\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n====================",
    // h1
    "1. asdf asdf asdf asdf\n=\nasdf asdf asdf asdf",

    // h2
    "-\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n-\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n-",
    // h2
    "--------------------\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n--------------------\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n--------------------",

    // hr
    "***\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n***\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n***",
    // hr
    "---\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n---\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n---",
    // hr
    "___\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n___\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n___",

    // p
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",

    // pre
    "    asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n    asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n    asdf asdf asdf asdf",
    // pre
    "    asdf asdf asdf asdf\n    asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n    asdf asdf asdf asdf\n    asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n    asdf asdf asdf asdf\n    asdf asdf asdf asdf",
    // pre
    "    asdf asdf asdf asdf\n\n    asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n    asdf asdf asdf asdf\n\n    asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n    asdf asdf asdf asdf\n\n    asdf asdf asdf asdf",
    // pre
    "    asdf asdf asdf asdf\n        asdf asdf asdf asdf\n    asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n    asdf asdf asdf asdf\n        asdf asdf asdf asdf\n    asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n    asdf asdf asdf asdf\n        asdf asdf asdf asdf\n    asdf asdf asdf asdf",

    // ol
    "1. asdf asdf asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n1. asdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n1. asdf asdf asdf asdf",
    // ol
    "2. asdf asdf asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n2. asdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n2. asdf asdf asdf asdf",
    // ol
    "1.\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n1.\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n1.",
    // ol
    "1. asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n1. asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n1. asdf asdf asdf asdf",
    // ol
    "1. asdf asdf asdf asdf\n1. asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n1. asdf asdf asdf asdf\n1. asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n1. asdf asdf asdf asdf\n1. asdf asdf asdf asdf",
    // ol
    "1. asdf asdf asdf asdf\n   1. asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n1. asdf asdf asdf asdf\n   1. asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n1. asdf asdf asdf asdf\n   1. asdf asdf asdf asdf",

    // ul
    "* asdf asdf asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n* asdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n* asdf asdf asdf asdf",
    // ul
    "*\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n*\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n*",
    // ul
    "* asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n* asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n* asdf asdf asdf asdf",
    // ul
    "* asdf asdf asdf asdf\n* asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n* asdf asdf asdf asdf\n* asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n* asdf asdf asdf asdf\n* asdf asdf asdf asdf",
    // ul
    "* asdf asdf asdf asdf\n  * asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n* asdf asdf asdf asdf\n  * asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n* asdf asdf asdf asdf\n  * asdf asdf asdf asdf",

    // raw 1
    "<script asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n<script asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n<script asdf asdf",
    // raw 1
    "<script asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf\n</script>asdf asdf asdf",
    "<script asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf\n</script>\nasdf asdf asdf asdf",
    "<script asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf\n</script>\n\nasdf asdf asdf asdf",
    // raw 1
    "<script asdf </script> asdf asdf\nasdf asdf asdf asdf",
    "<script asdf asdf </script>\nasdf asdf asdf asdf",
    // raw 2
    "<!-- asdf asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n<!-- asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n<!-- asdf asdf asdf",
    // raw 2
    "<!-- asdf asdf asdf\n\nasdf asdf asdf -->\nasdf asdf asdf asdf",
    "<!-- asdf asdf asdf\n\nasdf asdf asdf -->\n\nasdf asdf asdf asdf",
    // raw 2
    "<!-- asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf -->\nasdf asdf asdf asdf",
    "<!-- asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf -->\n\nasdf asdf asdf asdf",
    // raw 3
    "<? asdf asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n<? asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n<? asdf asdf asdf",
    // raw 3
    "<? asdf asdf asdf\n\nasdf asdf asdf ?>\nasdf asdf asdf asdf",
    "<? asdf asdf asdf\n\nasdf asdf asdf ?>\n\nasdf asdf asdf asdf",
    // raw 3
    "<? asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf ?>\nasdf asdf asdf asdf",
    "<? asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf ?>\n\nasdf asdf asdf asdf",
    // raw 6
    "<div asdf asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n<div asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n<div asdf asdf asdf",
    // raw 6
    "<div asdf asdf asdf\n\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "<div asdf asdf asdf\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",

] as $text) {
    echo '<pre style="border:2px solid #f00;font:normal normal 12px/1.25 monospace;overflow:auto;padding:0 0.25em;">';
    echo \htmlspecialchars($text);
    echo '</pre>';
    echo '<pre style="border:2px solid #00f;font:normal normal 12px/1.25 monospace;overflow:auto;padding:0 0.25em;">';
    echo \htmlspecialchars(\json_encode(rows($text, [])[0], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
    echo '</pre>';
    echo '<hr>';
}