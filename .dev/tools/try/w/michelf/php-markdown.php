<?php

$t = hrtime(true);
$r = Michelf\MarkdownExtra::defaultTransform($content);
$t = (hrtime(true) - $t) / 1e6;

$with = 'Markdown Extra';

return $r;