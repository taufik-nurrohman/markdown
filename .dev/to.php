<?php

namespace x\markdown {
    function to(?string $value, $state = []): ?string {
        if ("" === $value) {
            return null;
        }
        if (!\is_array($state)) {
            $state = ['block' => !!$state];
        }
        $state = \array_replace_recursive([
            'block' => true
        ], $state);
        $block = !empty($state['block']);
        if (!$block) {
            return null; // TODO
        }
        return null; // TODO
    }
}

namespace x\markdown\to {
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
}