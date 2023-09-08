<?php

namespace x\markdown {
    function to(?string $content, $block = true): ?string {
        // TODO
    }
}

namespace x\markdown\to {
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
        foreach (\preg_split('/(' . $pattern_1 . '|' . $pattern_2 . '|' . $pattern_3 . ')/', $content, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY) as $v) {
            // TODO
        }
    }
    function x(?string $v): string {}
}