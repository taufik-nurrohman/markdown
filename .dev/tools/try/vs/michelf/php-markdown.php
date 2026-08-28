<?php

$label = 'Markdown Extra';

$t = hrtime(true);
$r = Michelf\MarkdownExtra::defaultTransform($content);
$t = (hrtime(true) - $t) / 1e6;

return $r;