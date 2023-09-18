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
        [$rows] = to\rows($content);
        if (!$rows) {
            return null;
        }
        foreach ($rows as &$row) {
            $row = \is_array($row) ? to\s($row) : $row;
        }
        $content = \implode("\n", $rows);
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
            } else if ("'" === $m[3][$k] && "'" === \substr($m[3][$k], -1)) {
                $m[3][$k] = \substr($m[3][$k], 1, -1);
            }
            $out[$v] = \htmlspecialchars_decode($m[3][$k]);
        }
        return $out;
    }
    function e(string $v, $as = \ENT_HTML5 | \ENT_QUOTES) {
        return \htmlspecialchars($v, $as, 'UTF-8');
    }
    function p(array $tags, $deep = null): string {
        foreach ($tags as &$tag) {
            if (false === $deep) {
                $tag = '<' . $tag . '(?>\s(?>"[^"]*"|\'[^\']\'|[^\/>])+)?\/?>';
                continue;
            }
            $tag = '<' . $tag . '(?>\s(?>"[^"]*"|\'[^\']\'|[^\/>])+)?>' . ($deep ? '(?>(?R)|[\s\S]*?)' : '[\s\S]*?') . '<\/' . $tag . '>';
        }
        return \implode('|', $tags);
    }
    function rows(?string $content, array $lot = []): array {
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
            if (!\preg_match('/^<([^\'\/<=>\s]+)(\s(?>"[^"]*"|\'[^\']\'|[^\/>])+)?(?>>\s*([\s\S]*?)\s*<\/\1>|\/?>)$/', $v, $m)) {
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
                $blocks[] = [$t, $m[3], a($m[2]), 0, [$n, 1 === $n ? '=' : (2 === $n ? '-' : '#')]];
                continue;
            }
            if ('p' === $t) {
                $blocks[] = [$t, $m[3], [], 0];
                continue;
            }
            $blocks[] = [false, $v, [], 0];
        }
        return [$blocks, []];
    }
    function s(array $data): ?string {
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