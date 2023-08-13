Markdown to HTML Converter
==========================

Motivation
----------

I appreciate the [Parsedown](https://github.com/erusev/parsedown) project for its simplicity and speed. It uses only a
single class file to convert Markdown syntax to HTML. However, given the decrease in Parsedown project activity over
time, I assume that it is now in the state of “feature complete”. It still has some bugs to fix, and with
[the recent release of PHP version 8.1](https://www.php.net/releases/8.1/en.php), some of the PHP syntax there has
become obsolete.

There is actually [a draft for Parsedown version 2.0](https://github.com/erusev/parsedown/tree/2.0.x), but it is no
longer made as a single class file. It’s broken down into components. The goal, I think, is to make it easy to add
functionality without breaking what’s already in the core. For others, it may be of great use, but I see it as a form of
similarity to the features provided by
[CommonMark](https://github.com/thephpleague/commonmark/blob/2.4/docs/2.4/customization/extensions.md). Because of that,
if I want to update, it might be more optimal to just switch to CommonMark.

I’m not into things like that. As someone who needs a function to convert Markdown syntax to HTML, that kind of
flexibility is completely unnecessary to me. I just want to convert Markdown syntax to HTML for once and then move on.
It was fulfilled by [Parsedown version 1.8](https://github.com/erusev/parsedown/tree/1.8.x-beta), but it seems that it
is no longer being actively maintained. Maybe because the era has changed, where things like this are no longer a
priority.

My goal in creating this project was to use this converter in my
[Markdown extension for Mecha](https://github.com/mecha-cms/x.markdown) in the future. Previously, I wanted to develop
this converter directly into the extension, but my friend advised me to create this project separately as it might have
potential to be used by other developers beyond the [Mecha CMS](https://github.com/mecha-cms) developers.

This converter can be installed using [Composer](https://packagist.org/packages/taufik-nurrohman/markdown), but it
doesn’t need any other dependencies and just uses Composer’s ability to automatically include files. Those of you who
don’t use Composer should be able to include the `index.php` file directly into your application without any problems.

Usage
-----

### Using Composer

From the command line interface, navigate to your project folder then run this command:

~~~ sh
composer require taufik-nurrohman/markdown
~~~

Require the generated auto-loader file in your application:

~~~ php
<?php

use function x\markdown\convert;

require 'vendor/autoload.php';

echo convert('# asdf {#asdf}'); // Returns `'<h1 id="asdf">asdf</h1>'`
~~~

### Using File

Require the `index.php` file in your application:

~~~ php
<?php

use function x\markdown\convert;

require 'index.php';

echo convert('# asdf {#asdf}'); // Returns `'<h1 id="asdf">asdf</h1>'`
~~~

Dialect
-------

From time to time, the history of Mecha slowly forms the dialect of this Markdown converter. The Markdown extension used
by Mecha [was first](https://github.com/mecha-cms/mecha/tree/v1.2.2) built with
[Michel Fortin’s Markdown converter](https://michelf.ca/projects/php-markdown) (which I believe is the very first port
of a PHP-based Markdown converter originally written in Perl by
[John Gruber](https://daringfireball.net/projects/markdown)). Until the release of
[Mecha version 1.2.3](https://github.com/mecha-cms/mecha/tree/v1.2.3), I decided to switch to
[Parsedown](https://github.com/erusev/parsedown) because it was quite popular at the time. It can also do the conversion
process much faster. Emanuil Rusev’s way of detecting the block type
[by reading the first character](https://github.com/erusev/parsedown/tree/1.7.4#questions) is, in my opinion, very
clever and efficient.

Tests
-----

Clone this repository into the root of your web server that supports PHP and then you can open the `test.php` file with
your browser to see the result and the performance of this converter in various cases.