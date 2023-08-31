<?php

namespace x\markdown {
    function to(?string $content, $block = true): ?string {
        // TODO
    }
}

namespace x\markdown\to {
    function rows(?string $content, array $lot = []): array {
        $blocks = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'p', 'pre', 'script', 'style', 'textarea'];
        $blocks_container = ['blockquote', 'dl', 'figure', 'ol', 'table', 'ul'];
        $voids = ['br', 'img'];
    }
    function x(?string $v): string {}
}