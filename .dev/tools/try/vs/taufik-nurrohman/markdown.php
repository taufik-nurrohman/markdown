<?php

$label = 'My Markdown Parser';

$t = hrtime(true);
$r = x\markdown\from($content);
$t = (hrtime(true) - $t) / 1e6;

return $r;