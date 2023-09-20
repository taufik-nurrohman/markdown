<?php

namespace x\markdown {
    function to(?string $content, $block = true): ?string {
        if (!$block) {
            [$row] = to\row($content);
            if (!$row) {
                return null;
            }
            if (\is_string($row)) {
                $content = \trim(\preg_replace('/\s+/', ' ', $row));
                return "" !== $content ? $content : null;
            }
            foreach ($row as &$v) {
                $v = \is_array($v) ? to\s($v) : $v;
            }
            $content = \trim(\preg_replace('/\s+/', ' ', \implode("", $row)));
            return "" !== $content ? $content : null;
        }
        [$rows, $lot] = to\rows($content);
        if (!$rows) {
            return null;
        }
        foreach ($rows as &$row) {
            $row = \is_array($row) ? to\s($row) : $row;
        }
        $content = \implode("\n", $rows);
        if (!empty($lot[1])) {
            foreach ($lot[1] as $k => $v) {
                $content .= "\n*[" . $k . ']:' . ("" !== $v ? ' ' . $v : "");
            }
        }
        return $content;
    }
}

namespace x\markdown\to {
    function a(?string $info) {
        if ("" === ($info = \trim($info ?? ""))) {
            return [];
        }
        $out = [];
        if (!\preg_match_all('/(^|\s)([^"\'\/<=>\s]+)(?>=("[^"]*"|\'[^\']*\'|[^\/<>\s]+)?)?/', $info, $m)) {
            return $out;
        }
        foreach ($m[2] as $k => $v) {
            if (!isset($m[3][$k])) {
                $out[$v] = true;
                continue;
            }
            if ("" === $m[3][$k]) {
                $out[$v] = "";
                continue;
            }
            if ('"' === $m[3][$k][0] && '"' === \substr($m[3][$k], -1)) {
                $m[3][$k] = \substr($m[3][$k], 1, -1);
            } else if ("'" === $m[3][$k][0] && "'" === \substr($m[3][$k], -1)) {
                $m[3][$k] = \substr($m[3][$k], 1, -1);
            }
            $out[$v] = d($m[3][$k]);
        }
        return $out;
    }
    function d(?string $v, $as = \ENT_HTML5 | \ENT_QUOTES) {
        return \htmlspecialchars_decode($v ?? "", $as);
    }
    function e(?string $v, $as = \ENT_HTML5 | \ENT_QUOTES) {
        return \htmlspecialchars($v ?? "", $as, 'UTF-8');
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
    function p(array $tags, $deep = null): string {
        foreach ($tags as &$tag) {
            if (false === $deep) {
                $tag = '<' . $tag . '(?>\s(?>"[^"]*"|\'[^\']*\'|[^\/>])+)?\/?>';
                continue;
            }
            $tag = '<' . $tag . '(?>\s(?>"[^"]*"|\'[^\']*\'|[^\/>])+)?>' . ($deep ? '(?>(?R)|[\s\S]*?)' : '[\s\S]*?') . '<\/' . $tag . '>';
        }
        return \implode('|', $tags);
    }
    function row(?string $content, array &$lot = []) {
        if ("" === \trim($content ?? "")) {
            return [[], $lot];
        }
        $chops = [];
        $pattern_1 = p(['a', 'abbr', 'b', 'br', 'code', 'em', 'i', 'img', 'strong']);
        $pattern_2 = p(['img'], false);
        $pattern_3 = '<(?>"[^"]*"|\'[^\']\'|[^>])+>';
        foreach (\preg_split('/(' . $pattern_1 . '|' . $pattern_2 . '|' . $pattern_3 . '|\s+)/', $content, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY) as $v) {
            if ("" === \trim($v)) {
                $chops[] = ' ';
                continue;
            }
            if ('<' !== $v[0] || '>' !== \substr($v, -1)) {
                $chops[] = $v;
                continue;
            }
            if (!\preg_match('/^<([^\'\/<=>\s]+)(\s(?>"[^"]*"|\'[^\']*\'|[^\/>])+)?(?>>\s*([\s\S]*?)\s*<\/\1>|\/?>)$/', $v, $m)) {
                $chops[] = $v;
                continue;
            }
            $t = $m[1];
            if ('a' === $t) {
                $chops[] = ['a', row($m[3], $lot)[0], a($m[2]), -1];
                continue;
            }
            if ('abbr' === $t) {
                $chops[] = ['abbr', $key = d($m[3]), $a = a($m[2]), -1];
                $lot[1][$key] = \trim(\preg_replace('/\s+/', ' ', $a['title'] ?? ""));
                continue;
            }
            if ('b' === $t || 'strong' === $t) {
                $chops[] = ['strong', row(\strtr($m[3], ['*' => '\*']), $lot)[0], a($m[2]), -1, '**'];
                continue;
            }
            if ('em' === $t || 'i' === $t) {
                $chops[] = ['em', row(\strtr($m[3], ['*' => '\*']), $lot)[0], a($m[2]), -1, '*'];
                continue;
            }
        }
        return [m($chops), $lot];
    }
    function rows(?string $content, array &$lot = []): array {
        if (!$content || false === \strpos($content, '<')) {
            return [];
        }
        $pattern_1 = p(['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'p', 'pre', 'script', 'style', 'textarea']);
        $pattern_2 = p(['blockquote', 'dl', 'figure', 'ol', 'table', 'ul'], true);
        $pattern_3 = p(['hr'], false);
        $pattern_4 = '<(?>"[^"]*"|\'[^\']\'|[^>])+>';
        $blocks = [];
        foreach (\preg_split('/(' . $pattern_1 . '|' . $pattern_2 . '|' . $pattern_3 . '|' . $pattern_4 . '|\s+)/', $content, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY) as $v) {
            if ("" === \trim($v)) {
                continue;
            }
            if ('<' !== $v[0] || '>' !== \substr($v, -1)) {
                $blocks[] = [false, $v, [], 0];
                continue;
            }
            if (!\preg_match('/^<([^\'\/<=>\s]+)(\s(?>"[^"]*"|\'[^\']*\'|[^\/>])+)?(?>>[ \t]*([\s\S]*?)[ \t]*<\/\1>|\/?>)$/', $v, $m)) {
                $blocks[] = [false, $v, [], 0];
                continue;
            }
            $t = $m[1];
            if ('hr' === $t) {
                $blocks[] = [$t, false, [], 0, '-'];
                continue;
            }
            if ('h' === $t[0] && false !== \strpos('123456', $t[1])) {
                $n = (int) $t[1];
                $blocks[] = [$t, row($m[3], $lot)[0], a($m[2]), 0, [$n, 1 === $n ? '=' : (2 === $n ? '-' : '#')]];
                continue;
            }
            if ('p' === $t) {
                $blocks[] = [$t, row($m[3], $lot)[0], [], 0];
                continue;
            }
            $blocks[] = [false, $v, [], 0];
        }
        return [$blocks, $lot];
    }
    function s(array $data): ?string {
        if (\is_array($data[1])) {
            foreach ($data[1] as &$v) {
                if (!\is_array($v)) {
                    $v = (string) $v;
                    continue;
                }
                if (false === $v[0]) {
                    $v = d($v[1]);
                    continue;
                }
                if ('a' === $v[0]) {
                    $content = \is_array($v[1]) ? s($v[1]) : d($v[1]);
                    $link = $v[2]['href'] ?? "";
                    $title = $v[2]['title'] ?? null;
                    if (false !== \strpos($link, '://') && $content === $link) {
                        $v = '<' . $link . '>';
                        continue;
                    }
                    if ("" === $link || false !== \strpos($link, ' ')) {
                        $link = '<' . $link . '>';
                    }
                    if (null !== $title) {
                        if ("" === $title) {
                            $title = ' ""';
                        } else if (false !== \strpos($title, '"') && false === \strpos($title, "'")) {
                            $title = " '" . $title . "'";
                        } else if (false !== \strpos($title, "'") && false === \strpos($title, '"')) {
                            $title = ' "' . $title . '"';
                        } else if (false !== \strpos($title, '"') && false !== \strpos($title, "'")) {
                            $title = ' (' . $title . ')';
                        } else {
                            $title = " '" . \strtr($title, ['"' => '\"', "'" => '\'']) . "'";
                        }
                    }
                    // TODO: Attribute
                    $v = '[' . $content . '](' . $link . $title . ')';
                    continue;
                }
                if ('abbr' === $v[0]) {
                    $v = $v[1];
                    continue;
                }
                if ('em' === $v[0]) {
                    $v = '*' . (\is_array($v[1]) ? s($v[1]) : d($v[1])) . '*';
                    continue;
                }
                if ('strong' === $v[0]) {
                    $v = '**' . (\is_array($v[1]) ? s($v[1]) : d($v[1])) . '**';
                    continue;
                }
            }
            $data[1] = \implode("", $data[1]);
        }
        if (false === $data[0]) {
            return $data[1] . "\n";
        }
        if ('hr' === $data[0]) {
            return \str_repeat($data[4], 3) . "\n";
        }
        if ('h' === $data[0][0] && isset($data[4][1])) {
            if ($class = $data[2]['class'] ?? "") {
                $class = '.' . \preg_replace('/\s+/', '.', \trim($class));
            }
            if ($id = $data[2]['id'] ?? "") {
                $id = '#' . $id;
            }
            if ($class || $id) {
                $data[1] .= ' {' . $id . $class . '}';
            }
            if ("" === \trim($data[1])) {
                return \str_repeat('#', $data[4][0]) . "\n";
            }
            if (false !== \strpos('-=', $data[4][1])) {
                return $data[1] . "\n" . \str_repeat($data[4][1], \strlen($data[1])) . "\n";
            }
            return \str_repeat($data[4][1], $data[4][0]) . ' ' . $data[1] . "\n";
        }
        if ('p' === $data[0]) {
            return $data[1] . "\n";
        }
        return e($data[1]) . "\n";
    }
    function x(?string $v): string {}
}