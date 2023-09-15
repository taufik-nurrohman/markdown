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
        $pattern_3 = p(['br', 'img'], false);
        $blocks = [];
        foreach (\preg_split('/(' . $pattern_1 . '|' . $pattern_2 . '|' . $pattern_3 . '|\s+)/', $content, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY) as $v) {
            if ("" === \trim($v)) {
                continue;
            }
            if (0 === \strpos($v, '<h') && \is_numeric($v[2]) && '</h' === \substr($v, -5, 3)) {
                $n = (int) $v[2];
                if ('>' === $v[3]) {
                    $blocks[] = ['h' . $n, \substr($v, 4, -5), [], 0, [$n, 1 === $n ? '=' : (2 === $n ? '-' : '#')]];
                    continue;
                }
                // TODO: Preserve `class` and `id` attribute
                $blocks[] = ['h' . $n, \substr($v, \strpos($v, '>') + 1, -5), [], 0, [$n, 1 === $n ? '=' : (2 === $n ? '-' : '#')]];
                continue;
            }
            if (0 === \strpos($v, '<p>') && '</p>' === \substr($v, -4)) {
                $blocks[] = ['p', \substr($v, 3, -4), [], 0];
                continue;
            }
            $blocks[] = [false, $v, [], 0];
        }
        return [$blocks, []];
    }
    function s(array $data): ?string {
        if (false === $data[0]) {
            return $data[1];
        }
        if ('h' === $data[0][0] && isset($data[4][1])) {
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