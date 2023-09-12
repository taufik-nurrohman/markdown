<?php

namespace x\markdown {
    function from(?string $content, $block = true): ?string {
        if (!$block) {
            [$row] = from\row($content);
            if (!$row) {
                return null;
            }
            if (\is_string($row)) {
                $content = \trim(\preg_replace('/\s+/', ' ', $row));
                return "" !== $content ? $content : null;
            }
            foreach ($row as &$v) {
                $v = \is_array($v) ? from\s($v) : $v;
            }
            $content = \trim(\preg_replace('/\s+/', ' ', \implode("", $row)));
            return "" !== $content ? $content : null;
        }
        [$rows] = from\rows($content);
        if (!$rows) {
            return null;
        }
        foreach ($rows as &$row) {
            $row = \is_array($row) ? from\s($row) : $row;
        }
        $content = \implode("", $rows);
        $content = \strtr($content, ['</dl><dl>' => ""]);
        return $content;
    }
}

namespace x\markdown\from {
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
            $pattern = '/([#.](?>\\\\.|[\w:-])+|(?>[\w:.-]+(?>=(?>' . q('"') . '|' . q("'") . '|\S+)?)?))/';
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
                        $v[1] = v(\substr($v[1], 1, -1));
                    // `{a=false}`
                    } else if ('false' === $v[1]) {
                        $v[1] = false;
                    // `{a=true}`
                    } else if ('true' === $v[1]) {
                        $v[1] = true;
                    // `{a=null}`
                    } else if ('null' === $v[1]) {
                        $v[1] = null;
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
            if ($class = \array_unique($class)) {
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
                $out[] = true === $v ? $k : $k . '="' . e($v) . '"';
            }
            return $out ? ' ' . \implode(' ', $out) : null;
        }
        foreach (\preg_split('/\s+|(?=[#.])/', $info, -1, \PREG_SPLIT_NO_EMPTY) as $v) {
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
        if ($class = \array_unique($class)) {
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
            $out[] = $k . '="' . e($v) . '"';
        }
        if ($out) {
            \sort($out);
            return ' ' . \implode(' ', $out);
        }
        return null;
    }
    function d(?string $v, $as = \ENT_HTML5 | \ENT_QUOTES) {
        return \html_entity_decode($v ?? "", $as, 'UTF-8');
    }
    function data(?string $row): array {
        if ("" === ($row ?? "")) {
            return [null, $row, [], 0];
        }
        $dent = \strspn($row, ' ');
        $d = \str_repeat(' ', $dent);
        if ($dent >= 4) {
            $row = \substr(\strtr($row, ["\n" . $d => "\n"]), 4);
            return ['pre', $row, [], $dent];
        }
        // Remove indent(s)
        $row = \substr(\strtr($row, ["\n" . $d => "\n"]), $dent);
        if ("" === $row) {
            return [null, $row, [], $dent];
        }
        if (false !== \strpos($row, '|')) {
            return ['table', $row, [], $dent];
        }
        // `!…`
        if (0 === \strpos($row, '!')) {
            if (
                // `![asdf](…`
                \strpos($row, '](') > 0 ||
                // `![asdf][…`
                \strpos($row, '][') > 0 ||
                // `![asdf]` not followed by a `:`
                false !== ($n = \strpos($row, ']')) && ':' !== \substr($row, $n + 1, 1)
            ) {
                // `…)` or `…]` or `…}`
                if (false !== \strpos(')]}', \substr($row, -1))) {
                    return ['figure', $row, [], $dent];
                }
            }
            return ['p', $row, [], $dent];
        }
        // `#…`
        if (0 === \strpos($row, '#')) {
            $n = \strspn($row, '#');
            // `#######…`
            if ($n > 6) {
                return ['p', $row, [], $dent];
            }
            // `# …`
            if (false !== \strpos(" \t", \substr($row, $n, 1))) {
                return ['h' . $n, $row, [], $dent, [$n, '#']];
            }
            return ['p', $row, [], $dent];
        }
        // `*…`
        if ('*' === \rtrim($row)) {
            return ['ul', "", [], $dent, [1, $row[0], ""]];
        }
        if (0 === \strpos($row, '*')) {
            // `*[…`
            if (1 === \strpos($row, '[') && false === \strpos($row, '](') && false === \strpos($row, '][')) {
                return [1, $row, [], $dent];
            }
            // `***`
            $test = \strtr($row, [
                "\t" => "",
                ' ' => ""
            ]);
            if (\strspn($test, '*') === ($v = \strlen($test)) && $v > 2) {
                return ['hr', $row, [], $dent, '*'];
            }
            // `* …`
            if (false !== \strpos(" \t", \substr($row, 1, 1))) {
                return ['ul', $row, [], $dent, [1 + \strspn($row, ' ', 1), $row[0], ""]];
            }
            return ['p', $row, [], $dent];
        }
        // `+`
        if ('+' === \rtrim($row)) {
            return ['ul', "", [], $dent, [1, $row[0], ""]];
        }
        // `+…`
        if (0 === \strpos($row, '+')) {
            // `+ …`
            if (false !== \strpos(" \t", \substr($row, 1, 1))) {
                return ['ul', $row, [], $dent, [1 + \strspn($row, ' ', 1), $row[0], ""]];
            }
            return ['p', $row, [], $dent];
        }
        // `-`
        if ('-' === \rtrim($row)) {
            return ['ul', "", [], $dent, [1, $row[0], ""]];
        }
        // `--`
        if ('--' === \rtrim($row)) {
            return ['h2', $row, [], $dent, [2, '-']]; // Look like a Setext-header level 2
        }
        // `-…`
        if (0 === \strpos($row, '-')) {
            // `---`
            $test = \strtr($row, [
                "\t" => "",
                ' ' => ""
            ]);
            if (\strspn($test, '-') === ($v = \strlen($test)) && $v > 2) {
                return ['hr', $row, [], $dent, '-'];
            }
            // `- …`
            if (false !== \strpos(" \t", \substr($row, 1, 1))) {
                return ['ul', $row, [], $dent, [1 + \strspn($row, ' ', 1), $row[0], ""]];
            }
            return ['p', $row, [], $dent];
        }
        // `:…`
        if (0 === \strpos($row, ':')) {
            // `: …`
            if (false !== \strpos(" \t", \substr($row, 1, 1))) {
                return ['dl', $row, [], $dent, [1 + \strspn($row, ' ', 1), $row[0], ""]]; // Look like a definition list
            }
            return ['p', $row, [], $dent];
        }
        // `<…`
        if (0 === \strpos($row, '<')) {
            // `<asdf…`
            if ($t = \rtrim(\strtok(\substr($row, 1), " \n\t>"), '/')) {
                // The `:` and `@` character is not a valid part of a HTML element name, so it must be a link syntax
                // <https://spec.commonmark.org/0.30#tag-name>
                if (false !== \strpos($t, ':') || false !== \strpos($t, '@')) {
                    return ['p', $row, [], $dent];
                }
                // `<![…`
                if (0 === \strpos($t, '![')) {
                    $t = \substr($t, 0, \strrpos($t, '[') + 1); // `![CDATA[asdf` → `![CDATA[`
                }
                // `<!---…`
                if (0 === \strpos($t, '!--')) {
                    return [false, $row, [], $dent, '!--'];
                }
                if (false !== \strpos('!?', $t[0])) {
                    return [false, $row, [], $dent, $t];
                }
                // <https://spec.commonmark.org/0.30#html-blocks>
                if (false !== \strpos(',address,article,aside,base,basefont,blockquote,body,caption,center,col,colgroup,dd,details,dialog,dir,div,dl,dt,fieldset,figcaption,figure,footer,form,frame,frameset,h1,h2,h3,h4,h5,h6,head,header,hr,html,iframe,legend,li,link,main,menu,menuitem,nav,noframes,ol,optgroup,option,p,pre,param,script,section,source,style,summary,table,tbody,td,textarea,tfoot,th,thead,title,tr,track,ul,', ',' . \trim($t, '/') . ',')) {
                    return [false, $row, [], $dent, $t];
                }
                // <https://spec.commonmark.org/0.30#example-163>
                if ('>' === \substr($test = \rtrim($row), -1)) {
                    if ('/' === $t[0] && '<' . $t . '>' === $test) {
                        return [false, $row, [], $dent, $t];
                    }
                    if (\preg_match('/^<' . \preg_quote(\trim($t, '/'), '/') . '(\s[^>]*)?>$/', $test)) {
                        return [false, $row, [], $dent, $t];
                    }
                }
                return ['p', $row, [], $dent, $t];
            }
            return ['p', $row, [], $dent];
        }
        // `=…`
        if (0 === \strpos($row, '=')) {
            if (\strspn($row, '=') === \strlen($row)) {
                return ['h1', $row, [], $dent, [1, '=']]; // Look like a Setext-header level 1
            }
            return ['p', $row, [], $dent];
        }
        // `>…`
        if (0 === \strpos($row, '>')) {
            return ['blockquote', $row, [], $dent];
        }
        // `[…`
        if (0 === \strpos($row, '[')) {
            if (
                // `[asdf](…`
                \strpos($row, '](') > 0 ||
                // `[asdf][…`
                \strpos($row, '][') > 0 ||
                // `[asdf]` not followed by a `:`
                false !== ($n = \strpos($row, ']')) && ':' !== \substr($row, $n + 1, 1)
            ) {
                return ['p', $row, [], $dent];
            }
            if (1 === \strpos($row, '^')) {
                return [2, $row, [], $dent];
            }
            return [0, $row, [], $dent];
        }
        // `_…`
        if (0 === \strpos($row, '_')) {
            // `___`
            $test = \strtr($row, [
                "\t" => "",
                ' ' => ""
            ]);
            if (\strspn($test, '_') === ($v = \strlen($test)) && $v > 2) {
                return ['hr', $row, [], $dent, '_'];
            }
            return ['p', $row, [], $dent];
        }
        // ``…`
        if (0 === \strpos($row, '`')) {
            // ````…`
            if (0 === \strpos($row, '```')) {
                $fence = \substr($row, 0, $v = \strspn($row, '`'));
                $info = \trim(\substr($row, $v));
                // <https://spec.commonmark.org/0.30#example-145>
                if (false !== \strpos($info, '`')) {
                    return ['p', $row, [], $dent];
                }
                return ['pre', $row, a($info, true), $dent, $fence];
            }
            return ['p', $row, [], $dent];
        }
        // `~…`
        if (0 === \strpos($row, '~')) {
            // `~~~…`
            if (0 === \strpos($row, '~~~')) {
                $fence = \substr($row, 0, $v = \strspn($row, '~'));
                $info = \trim(\substr($row, $v));
                return ['pre', $row, a($info, true), $dent, $fence];
            }
            return ['p', $row, [], $dent];
        }
        // `1…`
        $n = \strspn($row, '0123456789');
        // <https://spec.commonmark.org/0.30#example-266>
        if ($n > 9) {
            return ['p', $row, [], $dent];
        }
        // `1)` or `1.`
        if ($n && ($n + 1) === \strlen(\rtrim($row)) && false !== \strpos(').', \substr(\rtrim($row), -1))) {
            $start = (int) \substr($row, 0, $n);
            return ['ol', "", ['start' => 1 !== $start ? $start : null], $dent, [$n + 1, $start, \substr($row, -1)]];
        }
        // `1) …` or `1. …`
        if (false !== \strpos(').', \substr($row, $n, 1)) && false !== \strpos(" \t", \substr($row, $n + 1, 1))) {
            $start = (int) \substr($row, 0, $n);
            return ['ol', $row, ['start' => 1 !== $start ? $start : null], $dent, [$n + 1 + \strspn($row, ' ', $n + 1), $start, \substr($row, $n, 1)]];
        }
        return ['p', $row, [], $dent];
    }
    function e(string $v, $as = \ENT_HTML5 | \ENT_QUOTES) {
        return \htmlspecialchars($v, $as, 'UTF-8');
    }
    function l(?string $link) {
        // ``
        if (!$link) {
            return true;
        }
        // `asdf` or `../asdf` or `/asdf` or `?asdf` or `#asdf`
        if (0 !== \strpos($link, '//') && (false === \strpos($link, '://') || false !== \strpos('./?#', $link[0]))) {
            return true;
        }
        // `//127.0.0.1` or `*://127.0.0.1`
        if (\parse_url($link, \PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? 0)) {
            return true;
        }
        return false;
    }
    // Apply reference(s), abbreviation(s), and note(s) data to the row(s)
    function lot($row, array &$lot = [], $lazy = true) {
        if (!$row) {
            // Keep the `false` value because it is used to mark void element(s)
            return false === $row ? false : null;
        }
        if (\is_string($row = m($row))) {
            // Optimize if current chunk is a complete word boundary
            if (isset($lot[1][$row])) {
                $title = $lot[1][$row];
                return [['abbr', $row, ['title' => "" !== $title ? $title : null], -1]];
            }
            // Else, chunk by word boundary
            $pattern = [];
            if (!empty($lot[1])) {
                foreach ($lot[1] as $k => $v) {
                    $pattern[] = \preg_quote($k, '/');
                }
            }
            $pattern = $pattern ? '/\b(' . \implode('|', $pattern) . ')\b/' : 0;
            if ($pattern) {
                $chops = [];
                foreach (\preg_split($pattern, $row, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY) as $v) {
                    if (isset($lot[1][$v])) {
                        $title = $lot[1][$v];
                        $chops[] = ['abbr', $v, ['title' => "" !== $title ? $title : null], -1];
                        continue;
                    }
                    $chops[] = $v;
                }
                if (1 === \count($chops) && \is_string($chop = \reset($chops))) {
                    return $chop;
                }
                return $chops;
            }
            return $row;
        }
        foreach ($row as &$v) {
            if (\is_array($v) && isset($v[0])) {
                if (false === $v[0] || 'abbr' === $v[0] || 'code' === $v[0]) {
                    continue;
                }
                $v[1] = lot($v[1], $lot);
                if ('a' === $v[0] || 'img' === $v[0]) {
                    if (!empty($v[4][2]) || false === $v[4][0]) {
                        continue; // Skip!
                    }
                    if (!isset($lot[0][$v[4][0]]) && $lazy) {
                        // Restore the original syntax
                        $v = $v[4][1];
                        continue;
                    }
                    $data = $v[2];
                    if (!isset($data[$k = 'a' === $v[0] ? 'href' : 'src'])) {
                        unset($data[$k]);
                    }
                    if (!isset($data['title'])) {
                        unset($data['title']);
                    }
                    $v[2][$k] = $lot[0][$v[4][0]][0] ?? null;
                    $v[2]['title'] = $lot[0][$v[4][0]][1] ?? null;
                    $v[2] = \array_replace($v[2], $lot[0][$v[4][0]][2] ?? [], $data);
                    if ($lazy) {
                        $v[4][2] = true; // Done!
                    }
                    continue;
                }
                if ('sup' === $v[0]) {
                    if (!empty($v[4][2]) || false === $v[4][0]) {
                        continue; // Skip!
                    }
                    if (!isset($lot[2][$v[4][0]]) && $lazy) {
                        // Restore the original syntax
                        $v = $v[4][1];
                        continue;
                    }
                    if ($lazy) {
                        $v[4][0][2] = true;
                    }
                }
            }
        }
        unset($v);
        return $row;
    }
    function m($row) {
        if (!$row || !\is_array($row)) {
            return $row;
        }
        if (1 === ($count = \count($row))) {
            $v = \reset($row);
            if (\is_array($v) && false === $v[0] && \is_string($v[1])) {
                return $v[1];
            }
            if (\is_string($v)) {
                return $v;
            }
        }
        // Simplify an array of continuous string(s) into a single string
        $row = \array_values($row);
        foreach ($row as $k => $v) {
            if (\is_string($row[$k - 1] ?? 0) && \is_string($v)) {
                $row[$k - 1] .= $v;
                unset($row[$k]);
                continue;
            }
        }
        if (1 === \count($row)) {
            $v = \reset($row);
            if (\is_array($v) && false === $v[0] && \is_string($v[1])) {
                return $v[1];
            }
            if (\is_string($v)) {
                return $v;
            }
        }
        return \array_values($row);
    }
    function q(string $char = '"', $capture = false, string $before = "", string $x = ""): string {
        $a = \preg_quote($char[0], '/');
        $b = \preg_quote($char[1] ?? $char[0], '/');
        $c = $a . ($b === $a ? "" : $b);
        return '(?>' . $a . ($capture ? '(' : "") . '(?>' . ($before ? $before . '|' : "") . '[^' . $c . $x . '\\\\]|\\\\.)*' . ($capture ? ')' : "") . $b . ')';
    }
    function r(string $char = '[]', $capture = false, string $before = "", string $x = ""): string {
        $a = \preg_quote($char[0], '/');
        $b = \preg_quote($char[1] ?? $char[0], '/');
        $c = $a . ($b === $a ? "" : $b);
        return '(?>' . $a . ($capture ? '(' : "") . '(?>' . ($before ? $before . '|' : "") . '[^' . $c . $x . '\\\\]|\\\\.|(?R))*' . ($capture ? ')' : "") . $b . ')';
    }
    function raw(?string $content, $block = true): array {
        return $block ? rows($content) : row($content);
    }
    function row(?string $content, array &$lot = []) {
        if ("" === \trim($content ?? "")) {
            return [[], $lot];
        }
        $chops = [];
        $prev = ""; // Capture the previous chunk
        $is_image = isset($lot['is_image']);
        $is_table = isset($lot['is_table']);
        $note_count = $lot['note_count'] ?? [];
        while (false !== ($chop = \strpbrk($content, '\\<`' . ($is_table ? '|' : "") . '*_![&' . "\n"))) {
            if ("" !== ($prev = \substr($content, 0, \strlen($content) - \strlen($chop)))) {
                $chops[] = e($prev);
            }
            if (0 === \strpos($chop, "\n")) {
                $prev = $chops[$last = \count($chops) - 1] ?? [];
                if (\is_string($prev) && ('  ' === \substr(\strtr($prev, ["\t" => '  ']), -2))) {
                    $chops[$last] = $prev = \rtrim($prev);
                    $chops[] = ['br', false, [], -1];
                    $content = $chop = \ltrim(\substr($chop, 1));
                    continue;
                }
                $chops[] = ' ';
                $content = $chop = \substr($chop, 1);
                continue;
            }
            if (0 === \strpos($chop, '!')) {
                if (1 === \strpos($chop, '[')) {
                    $lot['is_image'] = 1;
                    $row = row(\substr($chop, 1), $lot)[0][0];
                    unset($lot['is_image']);
                    if (\is_array($row) && 'a' === $row[0]) {
                        $row[0] = 'img';
                        if (\is_array($row[1])) {
                            $alt = "";
                            foreach ($row[1] as $v) {
                                // <https://spec.commonmark.org/0.30#example-573>
                                if (\is_array($v) && 'img' === $v[0]) {
                                    $alt .= $v[2]['alt'] ?? "";
                                    continue;
                                }
                                $alt .= \is_array($v) ? s($v) : $v;
                            }
                        } else {
                            $alt = $row[1];
                        }
                        $row[1] = false;
                        // <https://spec.commonmark.org/0.30#example-572>
                        $row[2]['alt'] = \trim(\strip_tags($alt));
                        $row[2]['src'] = $row[2]['href'];
                        $row[4][1] = '!' . $row[4][1];
                        unset($row[2]['href'], $row[2]['rel'], $row[2]['target']);
                        $chops[] = $row;
                        $content = $chop = \substr($chop, \strlen($prev = $row[4][1]));
                        continue;
                    }
                }
                $chops[] = $prev = '!';
                $content = $chop = \substr($chop, 1);
                continue;
            }
            if (0 === \strpos($chop, '&')) {
                if (false === ($n = \strpos($chop, ';')) || $n < 2 || !\preg_match('/^&(?>#x[a-f\d]{1,6}|#\d{1,7}|[a-z][a-z\d]{1,31});/i', $chop, $m)) {
                    $chops[] = e($prev = '&');
                    $content = $chop = \substr($chop, 1);
                    continue;
                }
                // <https://spec.commonmark.org/0.30#example-26>
                if ('&#0;' === $m[0]) {
                    $m[0] = '&#xfffd;';
                }
                $chops[] = ['&', $m[0], [], -1];
                $content = $chop = \substr($chop, \strlen($prev = $m[0]));
                continue;
            }
            // A left-flanking delimiter run is a delimiter run that is (1) not followed by Unicode white-space, and
            // either (2a) not followed by a Unicode punctuation character, or (2b) followed by a Unicode punctuation
            // character and preceded by Unicode white-space or a Unicode punctuation character. For purpose(s) of this
            // definition, the beginning and the end of the line count as Unicode white-space.
            //
            // A right-flanking delimiter run is a delimiter run that is (1) not preceded by Unicode white-space, and
            // either (2a) not preceded by a Unicode punctuation character, or (2b) preceded by a Unicode punctuation
            // character and followed by Unicode white-space or a Unicode punctuation character. For purpose(s) of this
            // definition, the beginning and the end of the line count as Unicode white-space.
            //
            // <https://spec.commonmark.org/0.30#emphasis-and-strong-emphasis>
            if (\strlen($chop) > 2 && false !== \strpos('*_', $c = $chop[0])) {
                $n = \strspn($chop, $c);
                $pattern = '*' === $c ? '/' .
                    // `<em>…`
                    '(?>' .
                        '(?<![' . $c . '])[' . $c . '](?![\p{P}\s])' .
                    '|' .
                        '(?<=^|[\p{P}\s])[' . $c . '](?=[\p{P}])' .
                    ')' .
                    '(?>' .
                        // <https://spec.commonmark.org/0.30#example-341>
                        '`[^`]+`' .
                    '|' .
                        // `…`
                        '[^' . $c . ($is_table ? '|' : "") . '\\\\]|\\\\.' .
                    '|' .
                        // `<strong>…`
                        '(?>' .
                            '[' . $c . ']{2}(?![\p{P}\s])' .
                        '|' .
                            '(?<=[\p{P}\s])[' . $c . ']{2}(?=[\p{P}])' .
                        ')' .
                        '(?>' .
                            // <https://spec.commonmark.org/0.30#example-341>
                            '`[^`]+`' .
                        '|' .
                            // `…`
                            '[^' . $c . ($is_table ? '|' : "") . '\\\\]|\\\\.' .
                        ')+?' .
                        // `…</strong>`
                        '(?>' .
                            '(?<![\p{P}\s])[' . $c . ']{2}' .
                        '|' .
                            '(?<=[\p{P}])[' . $c . ']{2}(?=[\p{P}\s])' .
                        ')' .
                    '|' .
                        '(?R)' .
                    ')+?' .
                    // `…</em>`
                    '(?>' .
                        '(?<![\p{P}\s])[' . $c . '](?![' . $c . '])' .
                    '|' .
                        '(?<=[\p{P}])[' . $c . '](?=[\p{P}\s]|$)' .
                    ')' .
                '/u' : '/' .
                    // `<em>…`
                    '(?>' .
                        '(?<=^|[\p{P}\s])[' . $c . '](?![\p{P}\s])' .
                    ')' .
                    '(?>' .
                        // <https://spec.commonmark.org/0.30#example-341>
                        '`[^`]+`' .
                    '|' .
                        '[^' . $c . '\\\\]|\\\\.' .
                    '|' .
                        '(?<![\p{P}\s])[' . $c . '](?![\p{P}\s])' .
                    '|' .
                        '(?R)' .
                    ')+?' .
                    // `…</em>`
                    '(?>' .
                        '(?<![\p{P}\s])[' . $c . '](?=[\p{P}\s]|$)' .
                    ')' .
                '/u';
                if ((1 === $n || $n > 2) && \preg_match($pattern, \substr($prev, -1) . $chop, $m, \PREG_OFFSET_CAPTURE)) {
                    if ($m[0][1] > 1) {
                        $chops[] = e(\substr($chop, 0, $m[0][1]));
                        $content = $chop = \substr($chop, $m[0][1]);
                    }
                    $chops[] = ['em', row(\substr($m[0][0], 1, -1), $lot)[0], [], -1, $c];
                    $content = $chop = \substr($chop, \strlen($prev = $m[0][0]));
                    continue;
                }
                $pattern = '*' === $c ? '/' .
                    // `<strong>…`
                    '(?>' .
                        '(?<![' . $c . '])[' . $c . ']{2}(?![\p{P}\s])' .
                    '|' .
                        '(?<=^|[\p{P}\s])[' . $c . ']{2}(?=[\p{P}])' .
                    ')' .
                    '(?>' .
                        // <https://spec.commonmark.org/0.30#example-341>
                        '`[^`]+`' .
                    '|' .
                        // `…`
                        '[^' . $c . ($is_table ? '|' : "") . '\\\\]|\\\\.' .
                    '|' .
                        // `<em>…`
                        '(?>' .
                            '[' . $c . '](?![\p{P}\s])' .
                        '|' .
                            '(?<=[\p{P}\s])[' . $c . '](?=[\p{P}])' .
                        ')' .
                        '(?>' .
                            // <https://spec.commonmark.org/0.30#example-341>
                            '`[^`]+`' .
                        '|' .
                            // `…`
                            '[^' . $c . ($is_table ? '|' : "") . '\\\\]|\\\\.' .
                        ')+?' .
                        // `…</em>`
                        '(?>' .
                            '(?<![\p{P}\s])[' . $c . ']' .
                        '|' .
                            '(?<=[\p{P}])[' . $c . '](?=[\p{P}\s])' .
                        ')' .
                    '|' .
                        '(?R)' .
                    ')+?' .
                    // `…</strong>`
                    '(?>' .
                        '(?<![\p{P}\s])[' . $c . ']{2}(?![' . $c . '])' .
                    '|' .
                        '(?<=[\p{P}])[' . $c . ']{2}(?=[\p{P}\s]|$)' .
                    ')' .
                '/u' : '/' .
                    // `<strong>…`
                    '(?>' .
                        '(?<=^|[\p{P}\s])[' . $c . ']{2}(?![\p{P}\s])' .
                    ')' .
                    '(?>' .
                        // <https://spec.commonmark.org/0.30#example-341>
                        '`[^`]+`' .
                    '|' .
                        '[^' . $c . '\\\\]|\\\\.' .
                    '|' .
                        '(?<![\p{P}\s])[' . $c . ']{2}(?![\p{P}\s])' .
                    '|' .
                        '(?R)' .
                    ')+?' .
                    // `…</strong>`
                    '(?>' .
                        '(?<![\p{P}\s])[' . $c . ']{2}(?=[\p{P}\s]|$)' .
                    ')' .
                '/u';
                if (\preg_match($pattern, \substr($prev, -1) . $chop, $m, \PREG_OFFSET_CAPTURE)) {
                    if ($m[0][1] > 1) {
                        $chops[] = e(\substr($chop, 0, $m[0][1]));
                        $content = $chop = \substr($chop, $m[0][1]);
                    }
                    $chops[] = ['strong', row(\substr($m[0][0], 2, -2), $lot)[0], [], -1, $c . $c];
                    $content = $chop = \substr($chop, \strlen($prev = $m[0][0]));
                    continue;
                }
                $chops[] = $c;
                $content = $chop = \substr($chop, 1);
                continue;
            }
            if (0 === \strpos($chop, '<')) {
                if (0 === \strpos($chop, '<!--') && false !== ($n = \strpos($chop, '-->'))) {
                    $v = \substr($chop, 0, $n + 3);
                    // <https://spec.commonmark.org/0.30#example-625>
                    // <https://spec.commonmark.org/0.30#example-626>
                    if ($n < 4 || false !== \strpos(\substr($v, 4, -3), '--') || ('-' === \substr($v, -4, 1) && '<!---->' !== $v)) {
                        $chops[] = e($prev = '<');
                        $content = $chop = \substr($chop, 1);
                        continue;
                    }
                    $chops[] = [false, \strtr($v, "\n", ' '), [], -1, '!--'];
                    $content = $chop = \substr($chop, \strlen($prev = $v));
                    continue;
                }
                if (0 === \strpos($chop, '<![CDATA[') && ($n = \strpos($chop, ']]>')) > 8) {
                    $chops[] = [false, \strtr($v = \substr($chop, 0, $n + 3), "\n", ' '), [], -1, '![CDATA['];
                    $content = $chop = \substr($chop, \strlen($prev = $v));
                    continue;
                }
                if (0 === \strpos($chop, '<!') && \preg_match('/^<![a-z](?>' . q('"') . '|' . q("'") . '|[^>])+>/i', $chop, $m)) {
                    $chops[] = [false, \strtr($m[0], "\n", ' '), [], -1, \rtrim(\strtok(\substr($m[0], 1), " \n\t>"), '/')];
                    $content = $chop = \substr($chop, \strlen($prev = $m[0]));
                    continue;
                }
                if (0 === \strpos($chop, '<' . '?') && \preg_match('/^<\?(?>' . q('"') . '|' . q("'") . '|[^>])+\?>/', $chop, $m)) {
                    $chops[] = [false, \strtr($m[0], "\n", ' '), [], -1, \strtok(\substr($m[0], 1), " \n\t>")];
                    $content = $chop = \substr($chop, \strlen($prev = $m[0]));
                    continue;
                }
                $test = (string) \strstr($chop, '>', true);
                // <https://github.com/commonmark/commonmark.js/blob/df3ea1e80d98fce5ad7c72505f9230faa6f23492/lib/inlines.js#L73>
                if (\strpos($test, '@') > 0 && \preg_match('/^<([a-z\d!#$%&\'*+.\/=?^_`{|}~-]+@[a-z\d](?>[a-z\d-]{0,61}[a-z\d])?(?>\.[a-z\d](?>[a-z\d-]{0,61}[a-z\d])?)*)>/i', $chop, $m)) {
                    // <https://spec.commonmark.org/0.30#example-605>
                    if (false !== \strpos($email = $m[1], '\\')) {
                        $chops[] = e($m[0]);
                        $content = $chop = \substr($chop, \strlen($prev = $m[0]));
                        continue;
                    }
                    $chops[] = ['a', e($m[1]), ['href' => u('mailto:' . $email)], -1, [false, $m[0]]];
                    $content = $chop = \substr($chop, \strlen($m[0]));
                    continue;
                }
                // <https://github.com/commonmark/commonmark.js/blob/df3ea1e80d98fce5ad7c72505f9230faa6f23492/lib/inlines.js#L75>
                if (\strpos($test, ':') > 1 && \preg_match('/^<([a-z][a-z\d.+-]{1,31}:[^<>' . ($is_table ? '|' : "") . '\x00-\x20]*)>/i', $chop, $m)) {
                    if (l($m[1])) {
                        $rel = $target = null;
                    } else {
                        $rel = 'nofollow';
                        $target = '_blank';
                    }
                    $chops[] = ['a', e($m[1]), [
                        'href' => u($m[1]),
                        'rel' => $rel,
                        'target' => $target
                    ], -1, [false, $m[0]]];
                    $content = $chop = \substr($chop, \strlen($prev = $m[0]));
                    continue;
                }
                // <https://spec.commonmark.org/0.30#raw-html>
                if (\preg_match('/^<(\/[a-z][a-z\d-]*)\s*>/i', $chop, $m)) {
                    $chops[] = [false, $m[0], [], -1, $m[1]];
                    $content = $chop = \substr($chop, \strlen($prev = $m[0]));
                    continue;
                }
                // <https://spec.commonmark.org/0.30#raw-html>
                if (\preg_match('/^<([a-z][a-z\d-]*)(\s[a-z:_][\w.:-]*(\s*=\s*(?>"[^"]*"|\'[^\']*\'|[^\s"\'<=>`]+)?)?)*\s*\/?>/i', $chop, $m)) {
                    $chops[] = [false, $m[0], [], -1, $m[1]];
                    $content = $chop = \substr($chop, \strlen($prev = $m[0]));
                    continue;
                }
                $chops[] = e($prev = '<');
                $content = $chop = \substr($chop, 1);
                continue;
            }
            if (0 === \strpos($chop, '[')) {
                $data = $key = $link = $title = null;
                // <https://spec.commonmark.org/0.30#example-342>
                $contains = '`[`^]+`';
                // `[asdf]…`
                if (\preg_match('/' . r('[]', true, $contains, $is_table ? '|' : "") . '/', $chop, $m, \PREG_OFFSET_CAPTURE)) {
                    $prev = $m[0][0];
                    if ($m[0][1] > 0) {
                        $chops[] = e(\substr($chop, 0, $m[0][1]));
                        $content = $chop = \substr($chop, $m[0][1]);
                    }
                    $content = $chop = \substr($chop, \strlen($prev));
                    // `[^asdf]`
                    if (0 === \strpos($m[1][0], '^')) {
                        $key = \trim(\substr($m[1][0], 1));
                        $note_count[$key] = ($note_count[$key] ?? 0) + 1;
                        $chops[] = ['sup', [['a', (string) \count($note_count), [
                            'href' => '#to:' . $key,
                            'role' => 'doc-noteref'
                        ], -1, [false, "", true]]], [
                            'id' => 'from:' . $key . ($note_count[$key] > 1 ? '.' . $note_count[$key] : "")
                        ], -1, [$key, $prev, false]];
                        $lot['note_count'] = $note_count;
                        continue;
                    }
                    $row = row($m[1][0], $lot)[0];
                    if (!$is_image && $row && \is_array($row) && false !== \strpos($m[1][0], '[')) {
                        $deep = false;
                        foreach ($row as $v) {
                            if (\is_array($v) && 'a' === $v[0]) {
                                // Found recursive link syntax!
                                $deep = true;
                                break;
                            }
                        }
                        // <https://spec.commonmark.org/0.30#example-517>
                        if ($deep) {
                            $chops[] = '[';
                            foreach ($row as $v) {
                                $chops[] = $v;
                            }
                            $chops[] = ']';
                            continue;
                        }
                    }
                    // `…(asdf)`
                    if (0 === \strpos($chop, '(') && \preg_match('/' . r('()', true, q('<>'), $is_table ? '|' : "") . '/', $chop, $n, \PREG_OFFSET_CAPTURE)) {
                        $prev = $n[0][0];
                        // `[asdf]()`
                        if ("" === ($n[1][0] = \trim($n[1][0] ?? ""))) {
                            $chops[] = ['a', $row, ['href' => ""], -1, [false, $m[0][0] . $n[0][0]]];
                            $content = $chop = \substr($chop, \strlen($n[0][0]));
                            continue;
                        }
                        // `[asdf](<>)`
                        if ('<>' === $n[1][0]) {
                            $link = "";
                        // `[asdf](<asdf>)`
                        } else if ('<' === $n[1][0][0]) {
                            if (false !== ($v = \strstr(\substr($n[1][0], 1), '>', true))) {
                                $link = $v;
                                $title = \trim(\substr($n[1][0], \strlen($v) + 2));
                                $title = "" !== $title ? $title : null;
                            } else {
                                $link = $n[1][0];
                            }
                        // `[asdf](asdf …)`
                        } else if (\strpos($n[1][0], ' ') > 0 || \strpos($n[1][0], "\n") > 0) {
                            $link = \trim(\strtok($n[1][0], " \n"));
                            $title = \trim(\strpbrk($n[1][0], " \n"));
                        // `[asdf](asdf)`
                        } else {
                            $link = $n[1][0];
                        }
                        // <https://spec.commonmark.org/0.30#example-490>
                        // <https://spec.commonmark.org/0.30#example-492>
                        // <https://spec.commonmark.org/0.30#example-493>
                        if (false !== \strpos($link, "\n") || '\\' === \substr($link, -1) || 0 === \strpos($link, '<')) {
                            $chops[] = e(v(\strtr($m[0][0] . $n[0][0], "\n", ' ')));
                            $content = $chop = \substr($chop, \strlen($n[0][0]));
                            continue;
                        }
                        if (\is_string($title) && "" !== $title) {
                            // `[asdf](asdf "asdf")` or `[asdf](asdf 'asdf')` or `[asdf](asdf (asdf))`
                            $a = $title[0];
                            $b = \substr($title, -1);
                            if (('"' === $a && '"' === $b || "'" === $a && "'" === $b || '(' === $a && ')' === $b) && \preg_match('/^' . q($a . $b) . '$/', $title)) {
                                $title = v(d(\substr($title, 1, -1)));
                            // `[asdf](asdf asdf)`
                            // <https://spec.commonmark.org/0.30#example-487>
                            } else {
                                $chops[] = e(v(\strtr($m[0][0] . $n[0][0], "\n", ' ')));
                                $content = $chop = \substr($chop, \strlen($n[0][0]));
                                continue;
                            }
                        }
                        $key = false;
                        $content = $chop = \substr($chop, \strlen($n[0][0]));
                    // `…[]` or `…[asdf]`
                    } else if (0 === \strpos($chop, '[') && \preg_match('/' . r('[]', true, "", $is_table ? '|' : "") . '/', $chop, $n, \PREG_OFFSET_CAPTURE)) {
                        $prev = $n[0][0];
                        // `[asdf][]`
                        if ("" === $n[1][0]) {
                            $of = $lot[0][$key = \trim(\strtolower($m[1][0]))] ?? [];
                        // `[asdf][asdf]`
                        } else {
                            $of = $lot[0][$key = \trim(\strtolower($n[1][0]))] ?? [];
                        }
                        $data = $of[2] ?? [];
                        $link = $of[0] ?? null;
                        $title = $of[1] ?? null;
                        $content = $chop = \substr($chop, \strlen($n[0][0]));
                    }
                    // …{asdf}
                    if (0 === \strpos(\trim($chop), '{') && \preg_match('/^\s*(' . q('{}', false, q('"') . '|' . q("'"), $is_table ? '|' : "") . ')/', $chop, $o)) {
                        if ("" !== \trim(\substr($o[1], 1, -1))) {
                            $data = \array_replace($data ?? [], a($o[1], true));
                            $content = $chop = \substr($chop, \strlen($o[0]));
                        }
                    }
                    if (!l($link)) {
                        $data['rel'] = $data['rel'] ?? 'nofollow';
                        $data['target'] = $data['target'] ?? '_blank';
                    }
                    $chops[] = ['a', $row, \array_replace([
                        // Need to retain the `null` value, otherwise the reference link style feature won’t work
                        'href' => null !== $link ? u(v($link)) : null,
                        'title' => $title
                    ], $data ?? []), -1, [$key ?? \trim(\strtolower($m[1][0])), $prev = $m[0][0] . ($n[0][0] ?? "") . ($o[0] ?? ""), false]];
                    continue;
                }
                $chops[] = $prev = '[';
                $content = $chop = \substr($chop, 1);
                continue;
            }
            if ('\\' === $chop) {
                $chops[] = $prev = $chop;
                $content = $chop = "";
                break;
            }
            if (0 === \strpos($chop, '\\') && isset($chop[1])) {
                // <https://spec.commonmark.org/0.30#example-644>
                if ("\n" === $chop[1]) {
                    $chops[] = ['br', false, [], -1, '\\'];
                    $content = \ltrim(\substr($chop, 2));
                    $prev = '\\';
                    continue;
                }
                $chops[] = e($prev = \substr($chop, 1, 1));
                $content = $chop = \substr($chop, 2);
                continue;
            }
            if (0 === \strpos($chop, $c = '`')) {
                $v = \str_repeat($c, $n = \strspn($chop, $c));
                if (1 === $n) {
                    $r = $c . '{2,}';
                } else if (2 === $n) {
                    $r = $c . '{3,}|' . $c;
                } else {
                    $r = $c . '{' . ($n + 1) . ',}|' . $c . '{1,' . ($n - 1) . '}';
                }
                if (\preg_match('/^' . $v . '((?>[^' . $c . ']|(?<!' . $c . ')(?>' . $r . ')(?!' . $c . '))+)' . $v . '(?!' . $c . ')/', $chop, $m)) {
                    // <https://spec.commonmark.org/0.30#code-span>
                    $raw = \strtr($m[1], "\n", ' ');
                    if (' ' !== $raw && '  ' !== $raw && ' ' === $raw[0] && ' ' === \substr($raw, -1)) {
                        $raw = \substr($raw, 1, -1);
                    }
                    $chops[] = ['code', e($raw), [], -1, $v];
                    $content = $chop = \substr($chop, \strlen($prev = $m[0]));
                    continue;
                }
                $chops[] = $prev = $v;
                $content = $chop = \substr($chop, $n);
                continue;
            }
            if ($is_table && 0 === \strpos($chop, '|')) {
                $chops[] = [false, '|', [], -1];
                $content = $chop = \substr($chop, 1);
                continue;
            }
            $chops[] = e($prev = $chop);
            $content = $chop = "";
        }
        if ("" !== $content) {
            $chops[] = e($prev = $content);
            $content = $chop = "";
        }
        if (\is_string($chops = lot($chops, $lot, false))) {
            return [$chops, $lot];
        }
        return [m($chops), $lot];
    }
    function rows(?string $content, array &$lot = []): array {
        // List of reference(s), abbreviation(s), and note(s)
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
            // TODO: Keep tab character(s)
            while (false !== ($before = \strstr($row, "\t", true))) {
                $v = \strlen($before);
                $row = $before . \str_repeat(' ', 4 - $v % 4) . \substr($row, $v + 1);
            }
            $current = data($row); // `[$type, $row, $data, $dent, …]`
            // If a block is available in the index `$block`, it indicates that we have a previous block.
            if ($prev = $blocks[$block] ?? 0) {
                // Raw HTML
                if (false === $prev[0]) {
                    if ('!--' === $prev[4]) {
                        if (false !== ($n = \strpos($prev[1], '-->'))) {
                            if ($n < 4) {
                                $blocks[$block++] = ['p', $prev[1], [], $prev[3]];
                                continue;
                            }
                            if (false !== \strpos(\substr($prev[1], 4, $n - 4), '--')) {
                                [$a, $b] = \explode("\n", $prev[1] . "\n", 2);
                                $blocks[$block] = ['p', $a, [], $prev[3]];
                                if ("" !== $b && \is_array($b = rows($b, $lot)[0])) {
                                    foreach ($b as $bb) {
                                        $blocks[++$block] = $bb;
                                    }
                                }
                                continue;
                            }
                            if (null === $current[0]) {
                                continue;
                            }
                            $blocks[++$block] = $current;
                            continue;
                        }
                        if (false !== ($n = \strpos($row, '-->'))) {
                            if ('-' === \substr($row, $n - 1, 1)) {
                                [$a, $b] = \explode("\n", $prev[1] . "\n", 2);
                                $blocks[$block] = ['p', $a, [], $prev[3]];
                                if ("" !== $b && \is_array($b = rows($b, $lot)[0])) {
                                    foreach ($b as $bb) {
                                        $blocks[++$block] = $bb;
                                    }
                                }
                                $blocks[++$block] = ['p', e($row), [], $current[3]];
                                continue;
                            }
                            $blocks[$block++][1] .= "\n" . $row;
                            continue;
                        }
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    if ('![CDATA[' === $prev[4]) {
                        if (false !== \strpos($prev[1], ']]>')) {
                            if (null === $current[0]) {
                                continue;
                            }
                            $blocks[++$block] = $current;
                            continue;
                        }
                        if (false !== \strpos($row, ']]>')) {
                            $blocks[$block++][1] .= "\n" . $row;
                            continue;
                        }
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    if ('!' === $prev[4][0]) {
                        if (false !== \strpos($prev[1], '>')) {
                            if (null === $current[0]) {
                                continue;
                            }
                            $blocks[++$block] = $current;
                            continue;
                        }
                        if (false !== \strpos($row, '>')) {
                            $blocks[$block++][1] .= "\n" . $row;
                            continue;
                        }
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    if (false !== \strpos(',pre,script,style,textarea,', ',' . $prev[4] . ',')) {
                        if (false !== \strpos($prev[1], '</' . $prev[4] . '>')) {
                            if (null === $current[0]) {
                                continue;
                            }
                            $blocks[++$block] = $current;
                            continue;
                        }
                        if (false !== \strpos($row, '</' . $prev[4] . '>')) {
                            $blocks[$block++][1] .= "\n" . $row;
                            continue;
                        }
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    if ('?' === $prev[4][0]) {
                        if (false !== \strpos(\preg_replace('/' . q('"') . '|' . q("'") . '/', "", $prev[1]), '?' . '>')) {
                            if (null === $current[0]) {
                                continue;
                            }
                            $blocks[++$block] = $current;
                            continue;
                        }
                        if (false !== \strpos(\preg_replace('/' . q('"') . '|' . q("'") . '/', "", $row), '?' . '>')) {
                            $blocks[$block++][1] .= "\n" . $row;
                            continue;
                        }
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    // CommonMark is not concerned with HTML tag balancing. It only concerned about blank line(s). Any
                    // non-blank line that sits right next to or below the opening/closing tag other than `<pre>`,
                    // `<script>`, `<style>`, and `<textarea> tag(s) will be interpreted as raw HTML. From that point
                    // forward, no Markdown processing will be performed.
                    // <https://spec.commonmark.org/0.30#example-161>
                    if ("" !== $current[1]) {
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                }
                // Indented code block
                if ('pre' === $prev[0] && $prev[3] >= 4) {
                    // Exit indented code block
                    if (null !== $current[0] && $current[3] < 4) {
                        $blocks[++$block] = $current;
                        continue;
                    }
                    // Continue indented code block
                    $blocks[$block][1] .= "\n" . \substr($row, 4);
                    continue;
                }
                // Reference, abbreviation, or note
                if (\is_int($prev[0])) {
                    if (\is_int($current[0])) {
                        $blocks[++$block] = $current;
                        continue;
                    }
                    $row = \substr($row, $prev[3]);
                    if (2 === $prev[0]) {
                        if ("" !== $current[1] && false === \strpos(" \t", $row[0]) && "\n" === \substr($prev[1], -1)) {
                            $blocks[++$block] = $current;
                            continue;
                        }
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    if ("" !== $current[1]) {
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                }
                if ('p' === $prev[0]) {
                    // Followed by a definition data block. Convert the previous paragraph as its definition term(s).
                    if ('dl' === $current[0]) {
                        $blocks[$block][0] = 'dl';
                        $blocks[$block][1] .= "\n" . $row;
                        $blocks[$block][4] = $current[4];
                        continue;
                    }
                    // <https://spec.commonmark.org/0.30#example-285>
                    // <https://spec.commonmark.org/0.30#example-304>
                    if ('ol' === $current[0] && ("" === $current[1] || 1 !== $current[4][1])) {
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    // <https://spec.commonmark.org/0.30#example-285>
                    if ('ul' === $current[0] && "" === $current[1]) {
                        if ('-' === $current[4][1]) {
                            $blocks[$block][0] = 'h2';
                            $blocks[$block][1] .= "\n" . $row;
                            $blocks[$block][4] = [2, '-'];
                            $block += 1;
                            continue;
                        }
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                }
                // List block is so complex that I decided to blindly concatenate all of the remaining line(s) until the
                // very end of the stream by default when the first list marker is found. To exit the list, we will do
                // so manually while we are in the list block.
                if ('dl' === $prev[0]) {
                    if (null !== $current[0]) {
                        if ('dl' !== $current[0] && $current[3] < $prev[3] + $prev[4][0]) {
                            // Remove final line break
                            $blocks[$block][1] = \rtrim($prev[1], "\n");
                            // Exit the list using block(s) other than the paragraph block
                            $blocks[++$block] = $current;
                            continue;
                        }
                    }
                    // Continue as part of the list item content
                    $row = \substr($row, $prev[3]);
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                if ('ol' === $prev[0]) {
                    // <https://spec.commonmark.org/0.30#example-99>
                    if ('h1' === $current[0] && '=' === $current[4][1]) {
                        $blocks[++$block] = ['p', $current[1], [], $current[3]];
                        continue;
                    }
                    // <https://spec.commonmark.org/0.30#example-278> but with indent less than the minimum required
                    if ('p' === $current[0] && "" === $prev[1] && $current[3] < $prev[4][0]) {
                        $current[1] = $prev[4][1] . $prev[4][2] . "\n" . $current[1];
                        $blocks[$block] = $current;
                        continue;
                    }
                    // To exit the list, either start a new list marker with a lower number than the previous list
                    // number or use a different number suffix. For example, use `1)` to separate the previous list that
                    // was using `1.` as the list marker.
                    if ('ol' === $current[0] && $current[3] === $prev[3] && ($current[4][2] !== $prev[4][2] || $current[4][1] < $prev[4][1])) {
                        // Remove final line break
                        $blocks[$block][1] = \rtrim($prev[1], "\n");
                        $blocks[++$block] = $current;
                        continue;
                    }
                    if (null !== $current[0]) {
                        if ('ol' !== $current[0] && $current[3] < $prev[3] + $prev[4][0]) {
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
                    if (isset($current[4][1]) && $current[3] === $prev[3]) {
                        $blocks[$block][4][1] = $current[4][1];
                    }
                    // Continue as part of the list item content
                    $row = \substr($row, $prev[3]);
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                if ('ul' === $prev[0]) {
                    // <https://spec.commonmark.org/0.30#example-99>
                    if ('h1' === $current[0] && '=' === $current[4][1]) {
                        $blocks[++$block] = ['p', $current[1], [], $current[3]];
                        continue;
                    }
                    // <https://spec.commonmark.org/0.30#example-278> but with indent less than the minimum required
                    if ('p' === $current[0] && "" === $prev[1] && $current[3] < $prev[4][0]) {
                        $current[1] = $prev[4][1] . "\n" . $current[1];
                        $blocks[$block] = $current;
                        continue;
                    }
                    // To exit the list, use a different list marker.
                    if ('ul' === $current[0] && $current[3] === $prev[3] && $current[4][1] !== $prev[4][1]) {
                        // Remove final line break
                        $blocks[$block][1] = \rtrim($prev[1], "\n");
                        $blocks[++$block] = $current;
                        continue;
                    }
                    if (null !== $current[0]) {
                        if ('ul' !== $current[0] && $current[3] < $prev[3] + $prev[4][0]) {
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
                    $row = \substr($row, $prev[3]);
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                // Fenced code block
                if ('pre' === $prev[0] && isset($prev[4])) {
                    // Exit fenced code block
                    if ('pre' === $current[0] && isset($current[4]) && $prev[4] === $current[1]) {
                        $blocks[$block++][1] .= "\n" . $row;
                        continue;
                    }
                    // Continue fenced code block
                    $blocks[$block][1] .= "" !== $prev[1] ? "\n" . $row : $row;
                    continue;
                }
                if ('blockquote' === $prev[0]) {
                    if ('p' === $current[0]) {
                        $blocks[$block][1] .= "\n" . $row; // Lazy quote block
                        continue;
                    }
                }
                if ('figure' === $prev[0]) {
                    // Exit image block
                    if (null !== $current[0] && $current[3] <= $prev[3]) {
                        $blocks[++$block] = $current;
                        continue;
                    }
                    if ($current[3] > $prev[3] || "" === $current[1]) {
                        $row = \substr($row, $current[3] > 4 ? $current[3] - 4 : $current[3]);
                        $blocks[$block][4] = isset($prev[4]) ? $prev[4] . "\n" . $row : $row;
                        continue;
                    }
                    $blocks[$block][1] .= "\n" . $current[1];
                    continue;
                }
                if ('table' === $prev[0]) {
                    // Continue table block if the previous table block does not end with a blank line
                    if ('table' === $current[0] && "\n" !== \substr($prev[1], -1)) {
                        $blocks[$block][1] .= "\n" . $current[1];
                        continue;
                    }
                    if (null === $current[0]) {
                        $blocks[$block][1] .= "\n"; // End of the table block, prepare to exit the table block
                        $blocks[$block][4] = "\n"; // Initialize the caption with a blank line
                    }
                    if ($current[3] > $prev[3] || "" === $current[1]) {
                        $row = \substr($row, $current[3] > 4 ? $current[3] - 4 : $current[3]);
                        $blocks[$block][4] = isset($prev[4]) ? $prev[4] . "\n" . $row : $row;
                        continue;
                    }
                    // Exit table block
                    $blocks[++$block] = $current;
                    continue;
                }
                // Found Setext-header marker level 1 right below a paragraph or quote block
                if ('h1' === $current[0] && '=' === $current[4][1]) {
                    // <https://spec.commonmark.org/0.30#example-93>
                    if ('blockquote' === $prev[0]) {
                        $blocks[$block][1] .= ' ' . $current[1];
                    } else if ('p' === $prev[0]) {
                        $blocks[$block][0] = $current[0]; // Treat the previous block as Setext-header level 1
                        $blocks[$block][1] .= "\n" . $current[1];
                        $blocks[$block][4] = $current[4];
                    }
                    $block += 1; // Start a new block after this
                    continue;
                }
                // Found Setext-header marker level 2 right below a paragraph block
                if ('h2' === $current[0] && '-' === $current[4][1] && 'p' === $prev[0]) {
                    $blocks[$block][0] = $current[0]; // Treat the previous block as Setext-header level 2
                    $blocks[$block][1] .= "\n" . $current[1];
                    $blocks[$block][4] = $current[4];
                    $block += 1; // Start a new block after this
                    continue;
                }
                // Found thematic break that sits right below a paragraph block
                if ('hr' === $current[0] && '-' === $current[4] && 'p' === $prev[0] && \strspn($current[1], $current[4]) === \strlen($current[1])) {
                    $blocks[$block][0] = 'h2'; // Treat the previous block as Setext-header level 2
                    $blocks[$block][1] .= "\n" . $current[1];
                    $blocks[$block][4] = [2, '-'];
                    $block += 1; // Start a new block after this
                    continue;
                }
                // Default action is to join current block with the previous block that has the same type
                if ($current[0] === $prev[0]) {
                    $row = \substr($row, $current[3]);
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
            }
            // Any other named block(s) will be processed from here
            if (\is_string($current[0])) {
                // Look like a Setext-header level 1, but preceded by a blank line, treat it as a paragraph block
                // <https://spec.commonmark.org/0.30#example-97>
                if ('h1' === $current[0] && '=' === $current[4][1] && (!$prev || null === $prev[0])) {
                    $blocks[++$block] = ['p', $current[1], [], $current[3]];
                    continue;
                }
                // Look like a Setext-header level 2, but preceded by a blank line, treat it as a paragraph block
                if ('h2' === $current[0] && '-' === $current[4][1] && (!$prev || null === $prev[0])) {
                    $blocks[++$block] = ['p', $current[1], [], $current[3]];
                    continue;
                }
                // An ATX-header block or thematic break
                if ('h' === $current[0][0]) {
                    $blocks[++$block] = $current;
                    $block += 1; // Start a new block after this
                    continue;
                }
            }
            // A blank line
            if (null === $current[0]) {
                if ($prev) {
                    // <https://spec.commonmark.org/0.30#example-197>
                    if (\is_int($prev[0]) && false === \strpos($prev[1], ']:')) {
                        $blocks[$block++][0] = 'p';
                        continue;
                    }
                }
                // Default action is to start a new block after a blank line
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
            if ('p' === $v[0] && isset($blocks[$k + 1]) && 'dl' === $blocks[$k + 1][0]) {
                $v[0] = 'dl';
                $v[1] .= "\n\n" . $blocks[$k + 1][1];
                $v[4] = $blocks[$k + 1][4];
                unset($blocks[$k + 1]);
                // Parse the definition list later
                continue;
            }
            if ('dl' === $v[0]) {
                // Must be a definition data without its term(s). Fall it back to the default block type!
                if (\strlen($v[1]) > 2 && ':' === $v[1][0] && false !== \strpos(" \t", $v[1][1])) {
                    $v = ['p', $v[1], [], $v[3]];
                }
                // Parse the definition list later
                continue;
            }
            if (\is_int($v[0]) && \strpos($v[1], ']:') > 1) {
                // Match an abbreviation
                if (0 === \strpos($v[1], '*[') && \preg_match('/' . r('[]', true) . '/', \substr($v[1], 1), $m) && ':' === \substr($v[1], \strlen($m[0]) + 1, 1)) {
                    // Remove abbreviation block from the structure
                    unset($blocks[$k]);
                    // Abbreviation is not part of the CommonMark specification, but I will just assume it to behave
                    // similar to the reference specification.
                    $key = \trim(\preg_replace('/\s+/', ' ', $m[1]));
                    // Queue the abbreviation data to be used later
                    $title = \trim(\substr($v[1], \strlen($m[0]) + 2));
                    if (isset($lot[$v[0]][$key])) {
                        continue;
                    }
                    $lot[$v[0]][$key] = $title;
                    continue;
                }
                // Match a note or a reference
                if (0 === \strpos($v[1], '[') && \preg_match('/' . r('[]', true) . '/', $v[1], $m) && ':' === \substr($v[1], \strlen($m[0]), 1)) {
                    if (0 === \strpos($m[1], '^')) {
                        $key = \trim(\strtolower(\preg_replace('/\s+/', ' ', \substr($m[1], 1))));
                        $note = \substr($v[1], \strlen($m[0]) + 1);
                        $d = \str_repeat(' ', \strlen($m[0]) + 1 + \strspn($note, ' '));
                        if (isset($lot[$v[0]][$key])) {
                            continue;
                        }
                        if ("" === \trim($note)) {
                            $v = ['p', row($v[1], $lot)[0], [], $v[3]];
                            continue;
                        }
                        if (false !== \strpos(" \n", $note[0])) {
                            $note = \substr($note, 1);
                        } else if ("\t" === $note[0]) {
                            $note = '   ' . \substr($note[0]);
                        }
                        // Remove indent(s)
                        [$a, $b] = \explode("\n", $note . "\n", 2);
                        $note = \trim(\strtr("\n" . $a . "\n" . $b, [
                            "\n" . \str_repeat(' ', \strspn(\trim($b, "\n"), ' ')) => "\n"
                        ]), "\n");
                        // Queue the note data to be used later
                        $lot_of_note = [$lot[0], $lot[1]];
                        $lot[$v[0]][$key] = rows($note, $lot_of_note)[0];
                        continue;
                    }
                    $data = $key = $link = $title = null;
                    if (\preg_match('/^\s*(' . q('<>') . '|\S+?)(?>\s+(' . q('"') . '|' . q("'") . '|' . q('()') . '))?(?>\s*(' . q('{}', false, q('"') . '|' . q("'")) . '))?\s*$/', \substr($v[1], \strlen($m[0]) + 1), $n)) {
                        // Remove reference block from the structure
                        unset($blocks[$k]);
                        // <https://spec.commonmark.org/0.30#matches>
                        $key = \trim(\strtolower(\preg_replace('/\s+/', ' ', $m[1])));
                        // <https://spec.commonmark.org/0.30#example-204>
                        if (isset($lot[$v[0]][$key])) {
                            continue;
                        }
                        if ($link = $n[1] ?? "") {
                            if ('<' === $link[0] && '>' === \substr($link, -1)) {
                                $link = \substr($link, 1, -1);
                                // <https://spec.commonmark.org/0.30#example-490>
                                // <https://spec.commonmark.org/0.30#example-492>
                                // <https://spec.commonmark.org/0.30#example-493>
                                if (false !== \strpos($link, "\n") || '\\' === \substr($link, -1) || 0 === \strpos($link, '<')) {
                                    $v[0] = 'p';
                                    $v[1] = row($v[1], $lot)[0];
                                    continue;
                                }
                            }
                        }
                        if ("" !== ($title = $n[2] ?? "")) {
                            $a = $title[0];
                            $b = \substr($title, -1);
                            if (('"' === $a && '"' === $b || "'" === $a && "'" === $b || '(' === $a && ')' === $b) && \preg_match('/^' . q($a . $b) . '$/', $title)) {
                                $title = v(d(\substr($title, 1, -1)));
                            } else {
                                $v[0] = 'p';
                                $v[1] = row($v[1], $lot)[0];
                                continue;
                            }
                        } else {
                            $title = null;
                        }
                        if ($data = $n[3] ?? []) {
                            $data = a($n[3], true);
                        }
                        if (!l($link)) {
                            $data['rel'] = $data['rel'] ?? 'nofollow';
                            $data['target'] = $data['target'] ?? '_blank';
                        }
                        // Queue the reference data to be used later
                        $lot[$v[0]][$key] = [u(v($link)), $title, $data];
                        continue;
                    }
                    $v[0] = 'p';
                    $v[1] = row($v[1], $lot)[0];
                    continue;
                }
            }
            if ('blockquote' === $v[0]) {
                $v[1] = \substr(\strtr($v[1], ["\n>" => "\n"]), 1);
                $v[1] = \substr(\strtr("\n" . $v[1], ["\n " => "\n"]), 1); // Remove space
                $v[1] = rows($v[1], $lot)[0];
                continue;
            }
            if ('figure' === $v[0]) {
                $row = row(\trim($v[1], "\n"), $lot)[0];
                // The image syntax doesn’t seem to appear alone on a single line
                if (\count($row) > 1) {
                    if (!empty($v[4])) {
                        [$a, $b] = \explode("\n\n", $v[4] . "\n\n", 2);
                        $v = [false, \array_merge([['p', row(\trim($v[1] . "\n" . $a, "\n"), $lot)[0], [], 0]], rows(\trim($b, "\n"), $lot)[0]), [], $v[3]];
                        continue;
                    }
                    $v = ['p', $row, [], $v[3]];
                    continue;
                }
                if (!empty($v[4])) {
                    $b = \rtrim($v[4], "\n");
                    $caption = rows($b, $lot)[0];
                    if (0 !== \strpos($b, "\n") && false === \strpos($b, "\n\n") && \is_array($test = \reset($caption)) && 'p' === $test[0]) {
                        $caption = $test[1];
                    }
                    $row[] = ['figcaption', $caption, [], 0];
                }
                $v[1] = $row;
                continue;
            }
            if ('hr' === $v[0]) {
                $v[1] = false;
                continue;
            }
            if ('h' === $v[0][0]) {
                if ('#' === $v[4][1]) {
                    $v[1] = \trim(\substr($v[1], \strspn($v[1], '#')));
                    if ('#' === \substr($v[1], -1)) {
                        $vv = \substr($v[1], 0, \strpos($v[1], '#'));
                        if (false !== \strpos(" \t", \substr($vv, -1))) {
                            $v[1] = \substr($vv, 0, -1);
                        }
                    }
                } else if (false !== \strpos('-=', $v[4][1])) {
                    $v[1] = \substr($v[1], 0, \strpos($v[1], "\n" . $v[4][1]));
                }
                // Late attribute parsing
                if (\strpos($v[1], '{') > 0 && \preg_match('/' . q('{}', true, q('"') . '|' . q("'")) . '\s*$/', $v[1], $m, \PREG_OFFSET_CAPTURE)) {
                    if ("" !== \trim($m[1][0]) && '\\' !== \substr($v[1], $m[0][1] - 1, 1)) {
                        $v[1] = \rtrim(\substr($v[1], 0, $m[0][1]));
                        $v[2] = \array_replace($v[2], a(\rtrim($m[0][0]), true));
                    }
                }
                $v[1] = row($v[1], $lot)[0];
                continue;
            }
            if ('ol' === $v[0]) {
                $list = \preg_split('/\n+(?=\d+[).]\s)/', $v[1]);
                $list_is_tight = false === \strpos($v[1], "\n\n");
                foreach ($list as &$vv) {
                    $vv = \substr(\strtr($vv, ["\n" . \str_repeat(' ', $v[4][0]) => "\n"]), $v[4][0]); // Remove indent(s)
                    $vv = rows($vv, $lot)[0];
                    if ($list_is_tight && \is_array($vv)) {
                        foreach ($vv as &$vvv) {
                            if (\is_array($vvv) && 'p' === $vvv[0]) {
                                $vvv[0] = false;
                            }
                        }
                        unset($vvv);
                    }
                    $vv = ['li', $vv, [], 0];
                }
                unset($vv);
                $v[1] = $list;
                $v[4][] = !$list_is_tight;
                continue;
            }
            if ('pre' === $v[0]) {
                $v[1] = e($v[1]);
                if (isset($v[4])) {
                    $v[1] = \substr(\strstr($v[1], "\n"), 1, -\strlen($v[4]));
                    $v[1] = [['code', "" === \trim($v[1], "\n") ? $v[1] : \substr($v[1], 0, -1), $v[2]]];
                    $v[2] = [];
                    continue;
                }
                if ("\n" === \substr($v[1], -1)) {
                    $v[1] = \substr($v[1], 0, -1);
                }
                $v[1] = [['code', $v[1], $v[2]]];
                $v[2] = [];
                continue;
            }
            if ('table' === $v[0]) {
                $table = [
                    ['thead', [['tr', [], [], 0]], 0],
                    ['tbody', [], [], 0]
                ];
                $rows = \explode("\n", \trim($v[1], "\n"));
                $headers = \trim(\array_shift($rows) ?? "", " \t|");
                $styles = \trim(\array_shift($rows) ?? "", " \t|");
                // Header-less table
                if (\strspn($headers, " \t-:|") === \strlen($headers)) {
                    \array_unshift($rows, $styles);
                    $styles = $headers;
                    $headers = "";
                }
                // Missing table header line
                if ("" === $styles) {
                    $v = ['p', row($v[1], $lot)[0], [], $v[3]];
                    continue;
                }
                // Invalid table header line
                if (\strspn($styles, " \t-:|") !== \strlen($styles)) {
                    $v = ['p', row($v[1], $lot)[0], [], $v[3]];
                    continue;
                }
                $styles = \explode('|', $styles);
                $styles_count = \count($styles);
                foreach ($styles as &$vv) {
                    $vv = \trim($vv);
                    if (':' === $vv[0] && ':' === \substr($vv, -1)) {
                        $vv = 'center';
                        continue;
                    }
                    if (':' === $vv[0]) {
                        $vv = 'left';
                        continue;
                    }
                    if (':' === \substr($vv, -1)) {
                        $vv = 'right';
                        continue;
                    }
                    $vv = null;
                }
                unset($vv);
                $lot['is_table'] = 1;
                if ("" !== $headers) {
                    $th = [];
                    if (\is_array($headers = row($headers, $lot)[0])) {
                        $i = 0;
                        foreach ($headers as $vv) {
                            $th[$i] = $th[$i] ?? ['th', [], [], 0];
                            if (\is_array($vv)) {
                                if (false === $vv[0] && '|' === $vv[1]) {
                                    $i += 1;
                                    continue;
                                }
                                $th[$i][1][] = $vv;
                                continue;
                            }
                            $th[$i][1][] = $vv;
                        }
                        foreach ($th as $kk => &$vv) {
                            $vv[1] = m($vv[1]);
                            if (\is_array($vv[1])) {
                                if (\is_string(\reset($vv[1]))) {
                                    $vv[1][$kk = \key($vv[1])] = \ltrim($vv[1][$kk]);
                                }
                                if (\is_string(\end($vv[1]))) {
                                    $vv[1][$kk = \key($vv[1])] = \rtrim($vv[1][$kk]);
                                }
                            } else if (\is_string($vv[1])) {
                                $vv[1] = \trim($vv[1]);
                            }
                            if (isset($styles[$kk])) {
                                $vv[2]['style'] = 'text-align: ' . $styles[$kk] . ';';
                            }
                        }
                        unset($vv);
                    } else {
                        $th[] = ['th', \trim($headers), [], 0];
                    }
                    $table[0][1][0][1] = \array_pad(\array_slice($th, 0, $styles_count), $styles_count, ['th', "", [], 0]);
                }
                foreach ($rows as $row) {
                    $td = [];
                    if (\is_array($row = row(\trim($row, " \t|"), $lot)[0])) {
                        $i = 0;
                        foreach ($row as $vv) {
                            $td[$i] = $td[$i] ?? ['td', [], [], 0];
                            if (\is_array($vv)) {
                                if (false === $vv[0] && '|' === $vv[1]) {
                                    $i += 1;
                                    continue;
                                }
                                $td[$i][1][] = $vv;
                                continue;
                            }
                            $td[$i][1][] = $vv;
                        }
                        foreach ($td as $kk => &$vv) {
                            $vv[1] = m($vv[1]);
                            if (\is_array($vv[1])) {
                                if (\is_string(\reset($vv[1]))) {
                                    $vv[1][$kk = \key($vv[1])] = \ltrim($vv[1][$kk]);
                                }
                                if (\is_string(\end($vv[1]))) {
                                    $vv[1][$kk = \key($vv[1])] = \rtrim($vv[1][$kk]);
                                }
                            } else if (\is_string($vv[1])) {
                                $vv[1] = \trim($vv[1]);
                            }
                            if (isset($styles[$kk])) {
                                $vv[2]['style'] = 'text-align: ' . $styles[$kk] . ';';
                            }
                        }
                        unset($vv);
                    } else {
                        $td[] = ['td', \trim($row), [], 0];
                    }
                    $table[1][1][] = ['tr', \array_pad(\array_slice($td, 0, $styles_count), $styles_count, ['td', "", [], 0]), [], 0];
                }
                unset($lot['is_table']);
                // Remove empty `<thead>`
                if (empty($table[0][1][0][1])) {
                    unset($table[0]);
                }
                // Remove empty `<tbody>`
                if (empty($table[1][1])) {
                    unset($table[1]);
                }
                if (!empty($v[4])) {
                    $b = \rtrim($v[4], "\n");
                    $caption = rows($b, $lot)[0];
                    if (0 !== \strpos($b, "\n") && false === \strpos($b, "\n\n") && \is_array($test = \reset($caption)) && 'p' === $test[0]) {
                        $caption = $test[1];
                    }
                    \array_unshift($table, ['caption', $caption, [], 0]);
                }
                $v[1] = $table;
                continue;
            }
            if ('ul' === $v[0]) {
                $list = \preg_split('/\n+(?=[*+-]\s)/', $v[1]);
                $list_is_tight = false === \strpos($v[1], "\n\n");
                foreach ($list as &$vv) {
                    $vv = \substr(\strtr($vv, ["\n" . \str_repeat(' ', $v[4][0]) => "\n"]), $v[4][0]); // Remove indent(s)
                    $vv = rows($vv, $lot)[0];
                    if ($list_is_tight && \is_array($vv)) {
                        foreach ($vv as &$vvv) {
                            if (\is_array($vvv) && 'p' === $vvv[0]) {
                                $vvv[0] = false;
                            }
                        }
                        unset($vvv);
                    }
                    $vv = ['li', $vv, [], 0];
                }
                unset($vv);
                $v[1] = $list;
                $v[4][] = !$list_is_tight;
                continue;
            }
            if (\is_string($v[1])) {
                $v[1] = row(\rtrim($v[1]), $lot)[0];
            }
        }
        foreach ($blocks as &$v) {
            // Late definition list parsing
            if ('dl' === $v[0]) {
                $list = \preg_split('/\n+(?=:\s|[^:\s])/', $v[1]);
                $list_is_tight = false === \strpos($v[1], "\n\n");
                foreach ($list as &$vv) {
                    if (\strlen($vv) > 2 && ':' === $vv[0] && false !== \strpos(" \t", $vv[1])) {
                        $vv = rows(\substr(\strtr($vv, [
                            "\n  " => "\n"
                        ]), 2), $lot)[0];
                        if ($list_is_tight && \is_array($vv)) {
                            foreach ($vv as &$vvv) {
                                if (\is_array($vvv) && 'p' === $vvv[0]) {
                                    $vvv[0] = false;
                                }
                            }
                            unset($vvv);
                        }
                        $vv = ['dd', $vv, [], 0];
                        continue;
                    }
                    $vv = ['dt', row($vv)[0], [], 0];
                }
                unset($vv);
                $v[1] = $list;
                $v[4][] = !$list_is_tight;
            }
            $v[1] = lot($v[1], $lot);
        }
        unset($v);
        $blocks = \array_values($blocks);
        if (!empty($lot[2])) {
            $notes = ['div', [
                ['hr', false, [], 0, '-'],
                ['ol', [], [], 0, [0, 1, '.']]
            ], [
                'role' => 'doc-endnotes'
            ], 0];
            foreach ($lot[2] as $k => $v) {
                if (!isset($lot['note_count'][$k])) {
                    continue;
                }
                if (\is_array($v) && \is_array($last = \array_pop($v))) {
                    if ('p' === $last[0]) {
                        $last[1] = (array) $last[1];
                        for ($i = 0, $j = $lot['note_count'][$k]; $i < $j; ++$i) {
                            $last[1][] = ['&', '&#160;', [], -1];
                            $last[1][] = ['a', [['&', '&#8617;', [], -1]], [
                                'href' => '#from:' . $k . ($i > 0 ? '.' . ($i + 1) : ""),
                                'role' => 'doc-backlink'
                            ], -1];
                        }
                        $v[] = $last;
                    } else {
                        $v[] = $last;
                        $p = ['p', [], [], 0];
                        for ($i = 0, $j = $lot['note_count'][$k]; $i < $j; ++$i) {
                            if ($i > 0) {
                                $p[1][] = ['&', '&#160;', [], -1];
                            }
                            $p[1][] = ['a', [['&', '&#8617;', [], -1]], [
                                'href' => '#from:' . $k . ($i > 0 ? '.' . ($i + 1) : ""),
                                'role' => 'doc-backlink'
                            ], -1];
                        }
                        $v[] = $p;
                    }
                }
                $notes[1][1][1][] = ['li', $v, [
                    'id' => 'to:' . $k
                ], 0];
            }
            if ($notes[1][1][1]) {
                $blocks['notes'] = $notes;
            }
            unset($lot['note_count']);
        }
        return [$blocks, $lot];
    }
    function s(array $data): ?string {
        if (false === $data[0]) {
            if (\is_array($data[1])) {
                $out = "";
                foreach ($data[1] as $v) {
                    $out .= \is_array($v) ? s($v) : $v;
                }
                return $out;
            }
            return $data[1];
        }
        if (\is_int($data[0])) {
            return "";
        }
        if ('&' === $data[0]) {
            return e(d($data[1]));
        }
        $out = '<' . $data[0];
        if (!empty($data[2])) {
            \ksort($data[2]);
            foreach ($data[2] as $k => $v) {
                if (false === $v || null === $v) {
                    continue;
                }
                $out .= ' ' . $k . (true === $v ? "" : '="' . e($v) . '"');
            }
        }
        if (false !== $data[1]) {
            $out .= '>';
            if (\is_array($data[1])) {
                foreach ($data[1] as $v) {
                    $out .= \is_array($v) ? s($v) : $v;
                }
            } else {
                $out .= $data[1];
            }
            $out .= '</' . $data[0] . '>';
        } else {
            $out .= ' />';
        }
        return "" !== $out ? $out : null;
    }
    function u(?string $v): string {
        \preg_match('/^([^?#]*)?([?][^#]*)?([#].*)?$/', d($v), $m);
        return \strtr(\rawurlencode($m[1]), [
            '%25' => '%',
            '%2B' => '+',
            '%2C' => ',',
            '%2F' => '/',
            '%3A' => ':',
            '%3B' => ';',
            '%40' => '@'
        ]) . (isset($m[2]) && "" !== $m[2] && '?' !== $m[2] ? '?' . \strtr(\rawurlencode(\substr($m[2], 1)), [
            '%25' => '%',
            '%26' => '&',
            '%3D' => '='
        ]) : "") . (isset($m[3]) && "" !== $m[3] && '#' !== $m[3] ? '#' . \strtr(\rawurlencode(\substr($m[3], 1)), [
            '%25' => '%'
        ]) : "");
    }
    // <https://spec.commonmark.org/0.30#example-12>
    function v(?string $content): string {
        return null !== $content ? \strtr($content, [
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
            '\~' => '~'
        ]) : "";
    }
}