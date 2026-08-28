<?php

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DescriptionList\DescriptionListExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

$label = 'CommonMark PHP';

$t = hrtime(true);
$environment = new Environment();
$environment->addExtension(new CommonMarkCoreExtension());
$environment->addExtension(new DescriptionListExtension());
$environment->addExtension(new TableExtension());
$environment->addExtension(new AttributesExtension());
$converter = new MarkdownConverter($environment);
$r = $converter->convert($content)->getContent();
$t = (hrtime(true) - $t) / 1e6;

return $r;