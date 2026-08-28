<?php

$label = 'Parsedown Extra';

$t = hrtime(true);
$r = (new ParsedownExtra)->text($content);
$t = (hrtime(true) - $t) / 1e6;

return $r;