<?php

$t = hrtime(true);
$r = (new cebe\markdown\MarkdownExtra)->parse($content);
$t = (hrtime(true) - $t) / 1e6;

$with = '“cebe” Markdown Extra';

return $r;