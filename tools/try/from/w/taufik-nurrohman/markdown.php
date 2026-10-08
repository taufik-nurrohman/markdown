<?php

$t = hrtime(true);
$r = x\markdown\from($content);
$t = (hrtime(true) - $t) / 1e6;

$with = 'My Markdown Parser';

return $r;