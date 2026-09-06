<?php

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DescriptionList\DescriptionListExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

$t = hrtime(true);
$env = new Environment;
$env->addExtension(new AttributesExtension);
$env->addExtension(new CommonMarkCoreExtension);
$env->addExtension(new DescriptionListExtension);
$env->addExtension(new FootnoteExtension);
$env->addExtension(new TableExtension);
$r = (new MarkdownConverter($env))->convert($content)->getContent();
$t = (hrtime(true) - $t) / 1e6;

$with = 'CommonMark PHP';

return $r;