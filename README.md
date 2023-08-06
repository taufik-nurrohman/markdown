Markdown to HTML Converter
==========================

Motivation
----------

Why another Markdown converter?

Usage
-----

~~~ php
<?php

use function x\markdown\from as from_markdown;

require __DIR__ . '/index.php';

echo from_markdown('# asdf {#a}'); // Returns `'<h1 id="a">asdf</h1>'`
~~~