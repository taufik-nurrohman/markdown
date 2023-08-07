<?php

// TODO: Remove this!
namespace {
    \error_reporting(\E_ALL | \E_STRICT);
    \ini_set('display_errors', true);
    \ini_set('display_startup_errors', true);
    \ini_set('html_errors', 1);
}

namespace x\markdown {
    function a(?string $info, $raw = false) {
        if ("" === ($info = \trim($info ?? ""))) {
            return $raw ? [] : null;
        }
        $a = [];
        $class = [];
        $id = null;
        if ('{' === $info[0] && '}' === \substr($info, -1)) {
            if ("" === ($info = \trim(\substr($info, 1, -1)))) {
                return $raw ? [] : null;
            }
            $pattern = '/([#.](?:\\\\[#.]|[\w:-])+|(?:[\w:.-]+(?:=(?:"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'|\S+)?)?))/';
            foreach (\preg_split($pattern, $info, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY) as $v) {
                if ("" === \trim($v)) {
                    continue; // Skip the space(s)
                }
                // `{#a}`
                if ('#' === $v[0]) {
                    $id = $id ?? \substr($v, 1);
                    continue;
                }
                // `{.a}`
                if ('.' === $v[0]) {
                    $class[] = \substr($v, 1);
                    continue;
                }
                // `{a=b}`
                if (false !== \strpos($v, '=')) {
                    $v = \explode('=', $v, 2);
                    // `{a=}`
                    if ("" === $v[1]) {
                        $a[$v[0]] = "";
                        continue;
                    }
                    // `{a="b"}` or `{a='b'}`
                    if ('"' === $v[1][0] || "'" === $v[1][0]) {
                        $v[1] = \x\markdown\v(\substr($v[1], 1, -1));
                    }
                    if ('class' === $v[0]) {
                        $class[] = $v[1]; // Merge class value(s)
                        continue;
                    }
                    $a[$v[0]] = $v[1];
                    continue;
                }
                // `{a}`
                $a[$v] = true;
            }
            if ($class) {
                \sort($class);
                $a['class'] = \implode(' ', $class);
            }
            if ($id) {
                $a['id'] = $id;
            }
            $a && \ksort($a);
            if ($raw) {
                return $a;
            }
            $out = [];
            foreach ($a as $k => $v) {
                $out[] = true === $v ? $k : $k . '="' . \htmlspecialchars($v) . '"';
            }
            if ($out) {
                \sort($out);
                return ' ' . \implode(' ', $out);
            }
            return null;
        }
        foreach (\preg_split('/\s++|(?=[#.])/', $info, -1, \PREG_SPLIT_NO_EMPTY) as $v) {
            if ('#' === $v[0]) {
                $id = $id ?? \substr($v, 1);
                continue;
            }
            if ('.' === $v[0]) {
                $class[] = \substr($v, 1);
                continue;
            }
            $class[] = 'language-' . $v;
        }
        if ($class) {
            \sort($class);
            $a['class'] = \implode(' ', $class);
        }
        if ($id) {
            $a['id'] = $id;
        }
        $a && \ksort($a);
        if ($raw) {
            return $a;
        }
        $out = [];
        foreach ($a as $k => $v) {
            $out[] = $k . '="' . \htmlspecialchars($v) . '"';
        }
        if ($out) {
            \sort($out);
            return ' ' . \implode(' ', $out);
        }
        return null;
    }
    // Takes out attribute(s) from its row
    function attr(string $row) {
        $attr = [$row, []];
        if ("" === ($row = \rtrim($row))) {
            return $attr;
        }
        // Must ends with `}` but not with `\}`
        if ('}' !== \substr($row, -1) || '\\}' === \substr($row, -2)) {
            return $attr;
        }
        // Must contains `{` but not `\{`
        if (false === ($a = \strpos($row, '{')) || '\\' === \substr($row, $a - 1, 1)) {
            return $attr;
        }
        if (\preg_match('/^(.+?)\s*(\{.+?\})\s*$/', $row, $m)) {
            if ('{}' === \strtr($m[2], [' ' => ""])) {
                return $attr;
            }
            $attr[0] = $m[1];
            $attr[1] = \x\markdown\a($m[2], true);
        }
        return $attr;
    }
    function tag(array $info): string {
        if (false === $info[0]) {
            if (\is_array($info[1])) {
                $out = "";
                foreach ($info[1] as $v) {
                    $out .= \is_array($v) ? \x\markdown\tag($v) : $v;
                }
                return $out;
            }
            return $info[1];
        }
        if (\is_int($info[0])) {
            return "";
        }
        $out = '<' . $info[0];
        if (!empty($info[2])) {
            foreach ($info[2] as $k => $v) {
                $out .= ' ' . $k . (true === $v ? "" : '="' . \htmlspecialchars($v) . '"');
            }
        }
        $out .= '>';
        if (false !== $info[1]) {
            if (\is_array($info[1])) {
                foreach ($info[1] as $v) {
                    $out .= \is_array($v) ? \x\markdown\tag($v) : $v;
                }
            } else {
                $out .= $info[1];
            }
            $out .= '</' . $info[0] . '>';
        }
        return $out;
    }
    function info(?string $row): array {
        if ("" === ($row ?? "")) {
            return [null, $row, [], 0];
        }
        $dent = \strspn($row, ' ');
        $d = \str_repeat(' ', $dent);
        if ($dent >= 4) {
            $row = \substr(\strtr($row, ["\n" . $d => "\n"]), $dent);
            return ['pre', $row, [], $dent];
        }
        // Remove indent(s)
        $row = \substr(\strtr($row, ["\n" . $d => "\n"]), $dent);
        if ("" === $row) {
            return [null, $row, [], $dent];
        }
        // `#…`
        if (0 === \strpos($row, '#')) {
            $n = \strspn($row, '#');
            // `#######…`
            if ($n > 6) {
                return ['p', $row, [], $dent];
            }
            // `# …`
            if ($n === \strpos($row, ' ')) {
                return ['h' . $n, $row, [], $dent, $n, '#'];
            }
            return ['p', $row, [], $dent];
        }
        // `*…`
        if (0 === \strpos($row, '*')) {
            // `*[…`
            if (1 === \strpos($row, '[')) {
                return [1, $row, [], $dent];
            }
            // `***`
            $test = \strtr($row, [' ' => ""]);
            if (\strspn($test, '*') === ($v = \strlen($test)) && $v > 2) {
                return ['hr', $row, [], $dent, '*'];
            }
            // `* …`
            if (1 === \strpos($row, ' ')) {
                return ['ul', $row, [], [$dent, $dent + 1 + \strspn($row, ' ', 1)], $row[0]];
            }
            return ['p', $row, [], $dent];
        }
        // `+…`
        if (0 === \strpos($row, '+')) {
            // `+ …`
            if (1 === \strpos($row, ' ')) {
                return ['ul', $row, [], [$dent, $dent + 1 + \strspn($row, ' ', 1)], $row[0]];
            }
            return ['p', $row, [], $dent];
        }
        // `-…`
        if (0 === \strpos($row, '-')) {
            // `---`
            $test = \strtr($row, [' ' => ""]);
            if (\strspn($test, '-') === ($v = \strlen($test)) && $v > 2) {
                return ['hr', $row, [], $dent, '-'];
            }
            // `- …`
            if (1 === \strpos($row, ' ')) {
                return ['ul', $row, [], [$dent, $dent + 1 + \strspn($row, ' ', 1)], $row[0]];
            }
            // `--`
            if ('-' === $row || '--' === $row) {
                return ['h2', $row, [], $dent]; // Look like a Setext header level 2
            }
            return ['p', $row, [], $dent];
        }
        // `:…`
        if (0 === \strpos($row, ':')) {
            // `: …`
            if (1 === \strpos($row, ' ')) {
                return ['dl', $row, [], $dent]; // Look like a part of a definition list
            }
            return ['p', $row, [], $dent];
        }
        // `<…`
        if (0 === \strpos($row, '<')) {
            // `<asdf…`
            if ($n = \strtok(\substr($row, 1), " \n\t>")) {
                // `<![…`
                if (0 === \strpos($n, '![')) {
                    $n = \substr($n, 0, \strrpos($n, '[') + 1); // `![CDATA[asdf` → `![CDATA[`
                }
                return [false, $row, [], $dent, $n]; // Look like a raw HTML
            }
            return ['p', $row, [], $dent];
        }
        // `=…`
        if (0 === \strpos($row, '=')) {
            if (\strspn($row, '=') === \strlen($row)) {
                return ['h1', $row, [], $dent, 1]; // Look like a Setext header level 1
            }
            return ['p', $row, [], $dent];
        }
        // `>…`
        if (0 === \strpos($row, '>')) {
            return ['blockquote', $row, [], $dent];
        }
        // `[…`
        if (0 === \strpos($row, '[')) {
            if (1 === \strpos($row, '^')) {
                return [2, $row, [], $dent];
            }
            return [0, $row, [], $dent];
        }
        // `_…`
        if (0 === \strpos($row, '_')) {
            // `___`
            $test = \strtr($row, [' ' => ""]);
            if (\strspn($test, '_') === ($v = \strlen($test)) && $v > 2) {
                return ['hr', $row, [], $dent, '_'];
            }
            return ['p', $row, [], $dent];
        }
        // ``…`
        if (0 === \strpos($row, '`')) {
            // ````…`
            if (0 === \strpos($row, '```')) {
                $fence = \substr($row, 0, \strrpos($row, '`') + 1);
                return ['pre', $row, \x\markdown\a(\trim(\substr($row, \strlen($fence))), true), $dent, $fence];
            }
            return ['p', $row, [], $dent];
        }
        // `~…`
        if (0 === \strpos($row, '~')) {
            // `~~~…`
            if (0 === \strpos($row, '~~~')) {
                $fence = \substr($row, 0, \strrpos($row, '~') + 1);
                return ['pre', $row, \x\markdown\a(\trim(\substr($row, \strlen($fence))), true), $dent, $fence];
            }
            return ['p', $row, [], $dent];
        }
        // `1…`
        $n = \strspn($row, '0123456789');
        // `1) …` or `1. …`
        if ($n === \strpos($row, ') ') || $n === \strpos($row, '. ')) {
            $start = (int) \substr($row, 0, $n);
            return ['ol', $row, 1 !== $start ? ['start' => $start] : [], [$dent, $dent + $n + 1 + \strspn($row, ' ', $n + 1)], \substr($row, $n, 1), $start];
        }
        return ['p', $row, [], $dent];
    }
    function row(?string $content, array $lot = []): array {
        if ("" === \trim($content ?? "")) {
            return [[], $lot];
        }
        $chops = [];
        // Priority: escape, code, raw, image, link, other(s)
        while ("" !== $content) {
            if ($n = \strcspn($content, '\\`<![*_&' . "\n")) {
                $chops[] = [false, \substr($content, 0, $n)];
                $content = \substr($content, $n);
            }
            if (0 === \strpos($content, "\n")) {
                $last = \count($chops) - 1;
                if ('  ' === \substr($chops[$last][1], -2)) {
                    $chops[$last][1] = \rtrim($chops[$last][1]);
                    $chops[] = ['br', false];
                    $content = \ltrim(\substr($content, 1));
                    continue;
                }
                $chops[] = [false, "\n"];
                $content = \substr($content, 1);
                continue;
            }
            if (0 === \strpos($content, '![')) {}
            if (0 === \strpos($content, '&')) {}
            if (0 === \strpos($content, '*') && isset($content[2])) {
                if (0 === \strpos($content, '**')) {
                    // TODO
                    continue;
                }
                if (\preg_match('/^[*]([\p{Ps}][\s\S]*?[\p{Pe}])[*]/u', $content, $m)) {
                    $chops[] = ['em', \x\markdown\row($m[1], $lot)[0]];
                    $content = \substr($content, \strlen($m[0]));
                    continue;
                }
                if (\preg_match('/^[*]([^\p{P}\p{Z}][\s\S]*?[^\p{P}\p{Z}])[*]/u', $content, $m)) {
                    $chops[] = ['em', \x\markdown\row($m[1], $lot)[0]];
                    $content = \substr($content, \strlen($m[0]));
                    continue;
                }
                $chops[] = [false, '*'];
                $content = \substr($content, 1);
                continue;
            }
            if (0 === \strpos($content, '<') && \preg_match('/^(<[^>]+>)(.*)$/', $content, $m)) {
                $chops[] = [false, $m[1]];
                $content = $m[2];
                continue;
            }
            if (0 === \strpos($content, '[')) {}
            if (0 === \strpos($content, '[^')) {}
            if (0 === \strpos($content, '\\')) {
                $chops[] = [false, \substr($content, 1, 1)];
                $content = \substr($content, 2);
                continue;
            }
            if (0 === \strpos($content, '_') && isset($content[2])) {
                if (0 === \strpos($content, '__')) {
                    // TODO
                    continue;
                }
                if (\preg_match('/^[_]([\p{Ps}][\s\S]*?[\p{Pe}])[_]/u', $content, $m)) {
                    $chops[] = ['em', \x\markdown\row($m[1], $lot)[0]];
                    $content = \substr($content, \strlen($m[0]));
                    continue;
                }
                if (\preg_match('/^[_]([^\p{P}\p{Z}][\s\S]*?[^\p{P}\p{Z}])[_]/u', $content, $m)) {
                    $chops[] = ['em', \x\markdown\row($m[1], $lot)[0]];
                    $content = \substr($content, \strlen($m[0]));
                    continue;
                }
                $chops[] = [false, '_'];
                $content = \substr($content, 1);
                continue;
            }
            if (0 === \strpos($content, '`') && \preg_match('/^(`.*?`)(.*)$/', $content, $m)) {
                $chops[] = ['code', \substr($m[1], 1, -1)];
                $content = $m[2];
                continue;
            }
            if ("" !== $content) {
                $chops[] = [false, $content];
                $content = "";
            }
        }
        return [$chops, $lot];
    }
    function rows(?string $content, array $lot = []): array {
        // List of reference(s), abbreviation(s), and foot-note(s)
        $lot = \array_replace([[], [], []], $lot);
        if ("" === \trim($content ?? "")) {
            return [[], $lot];
        }
        // Normalize line break(s)
        $content = \trim(\strtr($content, [
            "\r\n" => "\n",
            "\r" => "\n"
        ]), "\n");
        $block = 0;
        $blocks = [];
        $rows = \explode("\n", $content);
        foreach ($rows as $row) {
            $current = \x\markdown\info($row); // `[$type, $row, $data, $dent, …]`
            // If a block is available in the index `$block`, it indicates that we have a previous block.
             if ($prev = $blocks[$block] ?? 0) {
                // Reference, abbreviation, or foot-note
                if (\is_int($prev[0]) && "" !== $current[1]) {
                    if (\is_int($current[0])) {
                        $blocks[++$block] = $current;
                        continue;
                    }
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                // Probably a definition list with gap(s) between the term(s) and their definition data. Check if the
                // current paragraph is followed by one or more blank line(s) and a definition data. If so, convert the
                // current paragraph to a part of the definition list.
                if ('p' === $prev[0] && null === $current[0]) {
                    $back = 0;
                    // Move the array pointer forward until reaching a non-empty row
                    while (false !== ($next = \next($rows))) {
                        ++$back;
                        if (0 === \strpos($next, ': ')) {
                            // If the next non-empty row appears to be a definition data row, consider the current empty
                            // row as part of the definition list
                            $current[0] = 'dl';
                            break;
                        }
                        if ("" !== $next) {
                            break;
                        }
                    }
                    // Reset the array pointer to the normal pointer
                    while (--$back >= 0) {
                        \prev($rows);
                    }
                }
                // Verify that the current block has a type of `dl`, and verify that the previous block has a type of
                // `dl` or `p`. If so, merge the current block with the previous block, then change the type of the
                // previous block to `dl`.
                if ('dl' === $current[0] && ('dl' === $prev[0] || 'p' === $prev[0])) {
                    $blocks[$block][0] = 'dl';
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                // Verify that the current block has already been converted to a definition list, then verify that the
                // next row is a paragraph with initial indentation, then merge it with the previous block.
                if ('dl' === $prev[0] && \strspn($row, ' ') >= 2) {
                    $row = \substr($row, 2); // Length of `: ` character(s)
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                // List block is so complex that I decided to concatenate all remaining line(s) until the very end of
                // the file by default when a list pattern is found. To exit the list, we will do so manually while we
                // are in the list block.
                if ('ol' === $prev[0]) {
                    // To exit the list, either start a new list pattern with a lower number than the previous list
                    // number or use a different number suffix. For example, use `1)` to separate the previous list
                    // that was using `1.` as the list marker.
                    if ('ol' === $current[0] && ($current[4] !== $prev[4] || $current[5] < $prev[5]) && $current[3][0] === $prev[3][0]) {
                        // Remove final line break
                        $blocks[$block][1] = \rtrim($prev[1], "\n");
                        $blocks[++$block] = $current;
                        continue;
                    }
                    if (null !== $current[0]) {
                        $n = \is_int($current[3]) ? $current[3] : $current[3][0];
                        if ('ol' !== $current[0] && $n < $prev[3][1]) {
                            if ('p' === $current[0] && "\n" !== \substr($prev[1], -1)) {
                                $blocks[$block][1] .= "\n" . $row; // Lazy list
                                continue;
                            }
                            // Remove final line break
                            $blocks[$block][1] = \rtrim($prev[1], "\n");
                            // Exit the list using block(s) other than the paragraph block
                            $blocks[++$block] = $current;
                            continue;
                        }
                    }
                    // Update final number list to track the current highest number
                    if (isset($current[5]) && $current[3][0] === $prev[3][0]) {
                        $blocks[$block][5] = $current[5];
                    }
                    // Continue as part of the list item content
                    $row = \substr($row, $prev[3][0]);
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                // Here goes the bullet list block
                if ('ul' === $prev[0]) {
                    // To exit the list, use a different bullet character.
                    if ('ul' === $current[0] && $current[4] !== $prev[4] && $current[3][0] === $prev[3][0]) {
                        // Remove final line break
                        $blocks[$block][1] = \rtrim($prev[1], "\n");
                        $blocks[++$block] = $current;
                        continue;
                    }
                    if (null !== $current[0]) {
                        $n = \is_int($current[3]) ? $current[3] : $current[3][0];
                        if ('ul' !== $current[0] && $n < $prev[3][1]) {
                            if ('p' === $current[0] && "\n" !== \substr($prev[1], -1)) {
                                $blocks[$block][1] .= "\n" . $row; // Lazy list
                                continue;
                            }
                            // Remove final line break
                            $blocks[$block][1] = \rtrim($prev[1], "\n");
                            // Exit the list using block(s) other than the paragraph block
                            $blocks[++$block] = $current;
                            continue;
                        }
                    }
                    // Continue as part of the list item content
                    $row = \substr($row, $prev[3][0]);
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                // Fenced code block
                if ('pre' === $prev[0] && isset($prev[4][0])) {
                    // Exit fenced code block
                    if ('pre' === $current[0] && isset($current[4][0]) && $prev[4][0] === $current[4][0]) {
                        $blocks[$block++][1] .= "\n" . $row;
                        continue;
                    }
                    // Continue fenced code block
                    $blocks[$block][1] .= "" !== $prev[1] ? "\n" . $row : $row;
                    continue;
                }
                // Indented code block
                if ('pre' === $current[0] && $current[3] >= 4) {
                    $row = \substr($row, 4);
                    // Continue indented code block
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                // Raw HTML block
                if (false === $prev[0]) {
                    if (
                        // Exit raw HTML
                        (false === $current[0] && '/' === $current[4][0] && $current[4] === '/' . $prev[4]) ||
                        ('!--' === $prev[4] && false !== \strpos($current[1], '-->')) ||
                        ('![CDATA[' === $prev[4] && false !== \strpos($current[1], ']]>')) ||
                        ('?' === $prev[4][0] && false !== \strpos($current[1], '?' . '>')) ||
                        // Enter raw HTML
                        (false !== \strpos(',pre,script,style,textarea,', ',' . $prev[4] . ',')) ||
                        (false !== \strpos(',!,?,', ',' . $prev[4][0] . ','))
                    ) {
                        $blocks[$block++][1] .= "\n" . $row;
                        continue;
                    }
                    // Enter/exit raw HTML
                    // CommonMark is not concerned with HTML tag balancing. It only concerned about blank line(s). Any
                    // non-empty block that sits right next to or below the opening/closing tag other than `<pre>`,
                    // `<script>`, `<style>`, and `<textarea> tag(s) will be interpreted as raw HTML. From that point
                    // forward, there will be no Markdown processing will be performed.
                    //
                    // <https://spec.commonmark.org/0.30#example-161>
                    if ("" !== $current[1]) {
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                }
                // Lazy quote block
                if ('blockquote' === $prev[0] && 'p' === $current[0]) {
                    // Merge the current paragraph that sits right below the quote block
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                // Found Setext header marker level 1 right below a paragraph block
                if ('h1' === $current[0] && '=' === $current[1][0] && 'p' === $prev[0]) {
                    $blocks[$block][1] .= "\n" . $current[1];
                    $blocks[$block++][0] = $current[0]; // Treat the previous block as Setext header level 1
                    continue;
                }
                // Found Setext header marker level 2 right below a paragraph block
                if ('h2' === $current[0] && '-' === $current[1][0] && 'p' === $prev[0]) {
                    $blocks[$block][1] .= "\n" . $current[1];
                    $blocks[$block++][0] = $current[0]; // Treat the previous block as Setext header level 2
                    continue;
                }
                // Found thematic break that sits right below a paragraph block
                if ('hr' === $current[0] && '-' === $current[4] && 'p' === $prev[0]) {
                    $blocks[$block][1] .= "\n" . $current[1];
                    $blocks[$block++][0] = 'h2'; // Treat the previous block as Setext header level 2
                    continue;
                }
                // Default action is to merge current block with the previous block that has the same type
                if ($current[0] === $prev[0]) {
                    $row = \substr($row, $current[3]);
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
            }
            // Any other named block(s) will be processed from here
            if (\is_string($current[0])) {
                // Enter fenced code block
                if ('pre' === $current[0] && isset($current[4][0])) {
                    $blocks[++$block] = $current;
                    continue;
                }
                // Enter quote block
                if ('blockquote' === $current[0]) {
                    // Start a new quote block
                    $blocks[++$block] = $current;
                    continue;
                }
                // Enter ATX header block
                if ('h' === $current[0][0] && '#' === $current[1][0]) {
                    $blocks[++$block] = $current;
                    // Exit ATX header block (force to start a new block)
                    $block += 1;
                    continue;
                }
                // Look like Setext header level 1 but preceded by a blank line, treat it as a paragraph block
                if ('h1' === $current[0] && '=' === $current[1][0] && !isset($blocks[$block][0])) {
                    $current[0] = 'p';
                    $blocks[++$block] = $current;
                    continue;
                }
            }
            if (\is_int($current[0])) {}
            // Default action is to break every block by blank line(s)
            if (null === $current[0]) {
                if ($prev && 'dl' === $prev[0]) {
                    $blocks[$block][1] .= "\n";
                    continue;
                }
                if ($prev && 'pre' === $prev[0]) {
                    $blocks[$block][1] .= "\n";
                    continue;
                }
                $block += 1;
                continue;
            }
            $blocks[++$block] = $current;
        }
        $blocks = \array_values($blocks);
        foreach ($blocks as $k => &$v) {
            if (false === $v[0]) {
                continue;
            }
            if (\is_int($v[0]) && \strpos($v[1], ']:') > 1) {
                // Match an abbreviation
                if (\preg_match('/^[*]\[\s*([^\[\]\\\\]*(?:\\\\.[^\[\]\\\\]*)*)\s*\]:([\s\S]*?)$/', $v[1], $m)) {
                    // Remove abbreviation block from the structure
                    unset($blocks[$k]);
                    // Abbreviation(s) is not part of the CommonMark spec, but I assume it to behave similar to the reference(s)
                    $m[1] = \preg_replace('/\s+/', ' ', $m[1]);
                    // Queue the abbreviation data to be used later
                    $lot[$v[0]][$m[1]] = \trim($m[2] ?? "");
                    continue;
                }
                // Match a reference, or foot-note
                if (\preg_match('/^\[\s*([^\[\]\\\\]*(?:\\\\.[^\[\]\\\\]*)*)\s*\]:(?:\s*(\S+)(?:\s+("[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\')\s*)?)$/', $v[1], $m)) {
                    // Remove reference and foot-note block from the structure
                    unset($blocks[$k]);
                    // <https://spec.commonmark.org/0.30#matches>
                    $m[1] = \strtolower(\preg_replace('/\s+/', ' ', $m[1]));
                    // Match a foot-note
                    if (1 === \strpos($m[1], '^')) {
                        // TODO
                        continue;
                    }
                    // <https://spec.commonmark.org/0.30#example-204>
                    if (isset($lot[$v[0]][$m[1]])) {
                        continue;
                    }
                    $link = $m[2] ?? "";
                    $title = $m[3] ?? "";
                    if ($link && '<' === $link[0] && '>' === \substr($link, -1)) {
                        $link = \substr($link, 1, -1);
                    }
                    if ($title && (
                        "'" === $title[0] && "'" === \substr($title, -1) ||
                        '"' === $title[0] && '"' === \substr($title, -1)
                    )) {
                        $title = \x\markdown\v(\substr($title, 1, -1));
                    }
                    // Queue the reference data to be used later
                    $lot[$v[0]][$m[1]] = [$link, $title];
                    continue;
                }
            }
            if ('blockquote' === $v[0]) {
                $v[1] = \substr(\strtr($v[1], ["\n>" => "\n"]), 1);
                if (0 === \strpos($v[1], ' ')) {
                    $v[1] = \substr(\strtr($v[1], ["\n " => "\n"]), 1);
                }
                $v[1] = \x\markdown\rows($v[1], $lot)[0];
                continue;
            }
            if ('dl' === $v[0]) {
                [$a, $b] = \preg_split('/\n++(?=:[ ])/', $v[1], 2);
                $a = \explode("\n", $a);
                $b = \preg_split('/\n++(?=:[ ])/', $b);
                $tight = false === \strpos($v[1], "\n\n");
                foreach ($a as &$vv) {
                    $vv = ['dt', $vv];
                }
                unset($vv);
                foreach ($b as &$vv) {
                    $vv = \substr($vv, 2); // Length of `: ` character(s)
                    $vv = \x\markdown\rows($vv, $lot)[0];
                    $vv = ['dd', $vv];
                }
                unset($vv);
                $v[1] = \array_merge($a, $b);
                continue;
            }
            if ('hr' === $v[0]) {
                $v[1] = false;
                continue;
            }
            if ('h1' === $v[0] || 'h2' === $v[0] || 'h3' === $v[0] || 'h4' === $v[0] || 'h5' === $v[0] || 'h6' === $v[0]) {
                if (isset($v[5]) && '#' === $v[5]) {
                    $v[1] = \trim(\substr($v[1], \strspn($v[1], '#')));
                    if ('#' === \substr($v[1], -1)) {
                        $vv = \substr($v[1], 0, \strpos($v[1], '#'));
                        if (' ' === \substr($vv, -1)) {
                            $v[1] = \substr($vv, 0, -1);
                        }
                    }
                } else if (false !== \strpos($v[1], "\n=")) {
                    $v[1] = \substr($v[1], 0, \strpos($v[1], "\n="));
                } else if (false !== \strpos($v[1], "\n-")) {
                    $v[1] = \substr($v[1], 0, \strpos($v[1], "\n-"));
                }
                // Late attribute parsing
                $attr = \x\markdown\attr($v[1]);
                if ($attr[1]) {
                    $v[1] = $attr[0];
                    $v[2] = \array_replace($v[2], $attr[1]);
                }
                $v[1] = \x\markdown\row($v[1], $lot)[0];
                continue;
            }
            if ('ol' === $v[0]) {
                $list = \preg_split('/\n(?=\d++[).][ ])/', $v[1]);
                $tight = false === \strpos($v[1], "\n\n");
                foreach ($list as &$vv) {
                    $vv = \substr(\strtr($vv, ["\n" . \str_repeat(' ', $v[3][1]) => "\n"]), $v[3][1]);
                    $vv = \x\markdown\rows($vv, $lot)[0];
                    $vv = ['li', $vv];
                }
                unset($vv);
                $v[1] = $list;
                continue;
            }
            if ('pre' === $v[0]) {
                $v[1] = \htmlspecialchars($v[1]);
                if (isset($v[4])) {
                    $v[1] = [['code', \substr(\strstr($v[1], "\n"), 1, -\strlen($v[4])), $v[2]]];
                    $v[2] = [];
                    continue;
                }
                $v[1] = [['code', $v[1] . "\n", $v[2]]];
                $v[2] = [];
                continue;
            }
            if ('ul' === $v[0]) {
                $list = \preg_split('/\n(?=[*+-][ ])/', $v[1]);
                $tight = false === \strpos($v[1], "\n\n");
                foreach ($list as &$vv) {
                    $vv = \substr(\strtr($vv, ["\n" . \str_repeat(' ', $v[3][1]) => "\n"]), $v[3][1]);
                    $vv = \x\markdown\rows($vv, $lot)[0];
                    $vv = ['li', $vv];
                }
                unset($vv);
                $v[1] = $list;
                continue;
            }
            if (\is_string($v[1])) {
                $v[1] = \x\markdown\row(\rtrim($v[1]), $lot)[0];
            }
        }
        unset($v);
        return [$blocks, $lot];
    }
    function convert(?string $content, array $lot = [], $block = true): ?string {
        if ("" === \trim($content ?? "")) {
            return null;
        }
        $data = \x\markdown\rows($content, $lot);
        $alter_0 = [];
        $alter_1 = [];
        $alter_2 = [];
        if (!empty($data[1][0])) {}
        if (!empty($data[1][1])) {
            foreach ($data[1][1] as $k => $v) {
                $alter_1[$k] = \x\markdown\tag(['abbr', $k, ['title' => $v]]);
            }
        }
        if (!empty($data[1][2])) {}
        $out = [];
        foreach ($data[0] as $row) {
            // Late abbreviation parsing
            if (\is_string($row[0]) && $alter_1) {
                foreach ($row[1] as &$v) {
                    if (false !== $v[0]) {
                        continue;
                    }
                    // Only process raw chop(s)
                    $v[1] = \strtr($v[1], $alter_1);
                }
                unset($v);
            }
            $out[] = \x\markdown\tag($row);
        }
        $content = \implode("", $out);
        // Merge sequence of definition list into single definition list
        $content = \strtr($content, ['</dl><dl>' => ""]);
        return $content;
    }
    // <https://spec.commonmark.org/0.30#example-12>
    function v(string $content): string {
        return $content ? \strtr($content, [
            "\\'" => "'",
            "\\\\" => "\\",
            '\!' => '!',
            '\"' => '&quot;',
            '\#' => '#',
            '\$' => '$',
            '\%' => '%',
            '\&' => '&amp;',
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
            '\~' => '~'
        ]) : $content;
    }
    function x(string $content): string {}
}