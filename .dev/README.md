PHP Markdown Parser
===================

![from.php]

[from.php]: https://img.shields.io/github/size/taufik-nurrohman/markdown/from.php?branch=main&color=%234f5d95&label=from.php&labelColor=%231f2328&style=flat-square

With 99% compliance to [CommonMark 0.31.2](https://spec.commonmark.org/0.31.2) specifications.

Motivation
----------

<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://github.com/taufik-nurrohman/markdown/assets/1669261/0a488f4a-0450-4e0a-8137-196a4b0657b0">
  <source media="(prefers-color-scheme: light)" srcset="https://github.com/taufik-nurrohman/markdown/assets/1669261/164b592d-e8db-4e28-be5d-467522f65f0d">
  <img alt="Why?" src="https://github.com/taufik-nurrohman/markdown/assets/1669261/164b592d-e8db-4e28-be5d-467522f65f0d">
</picture>

I appreciate the [Parsedown](https://github.com/erusev/parsedown) project for its simplicity and speed. It only uses a
single class file to convert Markdown syntax to HTML. However, given the decrease in the project’s activity over time, I
assume it is already “feature complete”. There are still some bugs to fix, and some of the PHP syntax has become
obsolete with [the recent release of PHP version 8.1](https://www.php.net/releases/8.1/en.php).

There is actually a draft available for [Parsedown version 2.0](https://github.com/erusev/parsedown/tree/2.0.x), but it
is no longer created as a single class file. It has been broken down into components. I think the goal is to make it
easy to add functionality without messing with the core features. Others may find it useful, but I find it too similar
to the features provided by [CommonMark](https://github.com/thephpleague/commonmark/blob/2.4/docs/2.4/customization/extensions.md).
Therefore, if I want to upgrade, it would be better to simply switch to CommonMark.

I’m not into things like that. As someone who just needs a function to convert Markdown to HTML, that kind of
flexibility is completely unnecessary. I just want to convert Markdown to HTML once and then move on. [Parsedown version
1.8](https://github.com/erusev/parsedown/tree/1.8.x-beta) did the job, but it seems that it is no longer being actively
maintained.

The goal of this project is to have it ready for use in my future [Markdown extension for Mecha](https://github.com/mecha-cms/x.markdown).
Initially, I wanted to develop the parser directly into the extension. However, my friend advised me to create this
project separately, as it may be useful for developers who work with other applications besides the [Mecha CMS](https://github.com/mecha-cms).

Usage
-----

This parser can be installed using [Composer](https://packagist.org/packages/taufik-nurrohman/markdown), but it doesn’t
require any other dependencies. It simply uses Composer’s ability to automatically include files. Those of you who don’t
use Composer should be able to include the `from.php` file directly into your application.

### Using Composer

From the command line interface, navigate to your project folder then run this command:

~~~ sh
composer require taufik-nurrohman/markdown
~~~

Require the generated auto-loader file in your application:

~~~ php
<?php

use function x\markdown\from as from_markdown;

require 'vendor/autoload.php';

echo from_markdown('# asdf {#asdf}'); // Returns `'<h1 id="asdf">asdf</h1>'`
~~~

### Using File

Require the `from.php` file in your application:

~~~ php
<?php

use function x\markdown\from as from_markdown;

require 'from.php';

echo from_markdown('# asdf {#asdf}'); // Returns `'<h1 id="asdf">asdf</h1>'`
~~~

Options
-------

### `block`

If this option is set to `false`, the input will be assumed to contain inline syntax only. Any syntax appearing to be
block syntax will be rendered literally. Here’s an example of when this option will be useful:

~~~ php
<?php

use function x\markdown\from as from_markdown;

echo '<p>';

echo from_markdown('# [asdf](asdf)', [
    'block' => false
]); // Returns `'# <a href="asdf">asdf</a>'`

echo '</p>';
~~~

### `tab`

If this option is set to a number greater than or equal to `0`, it will determine the indent size for block elements,
which will tidy up the HTML output. If it is set to a string, the string will be used as the indent. For example, you
can set its value to `"\t"` to indent the HTML output with [Tab](https://www.compart.com/en/unicode/U+0009) characters.

~~~ php
<?= from_markdown($value, ['tab' => 0]); ?>
~~~

~~~ php
<?= from_markdown($value, ['tab' => 2]); ?>
~~~

~~~ php
<?= from_markdown($value, ['tab' => "\t"]); ?>
~~~

### `with`

A very simple extension system. Pass a list of callables there. It will modify the data structure before it becomes a
HTML string:

~~~ php
<?php

use function x\markdown\from as from_markdown;

// Extension as a closure
$my_extension = function (array $rows) { /* … */ };

// Extension as a function
function my_extension(array $rows) { /* … */ }

// Extension as a class
class MyExtension {
    public function __invoke(array $rows) { /* … */ }
}

echo from_markdown($value, [
    'with' => [
        $my_extension,
        'my_extension',
        new MyExtension,
        // …
    ]
]);
~~~

Dialect
-------

From time to time, the history of Mecha influences my Markdown writing style. Mecha’s Markdown extension
[was initially](https://github.com/mecha-cms/mecha/tree/v1.2.2) developed using [Michel Fortin’s Markdown converter](https://michelf.ca/projects/php-markdown),
which I believe was the first PHP-based Markdown converter, originally written in Perl by [John Gruber](https://daringfireball.net/projects/markdown).
I decided to switch to [Parsedown](https://github.com/erusev/parsedown) at the release of [Mecha version 1.2.3](https://github.com/mecha-cms/mecha/tree/v1.2.3)
because it was quite popular at the time. It can also do the conversion process much faster. Emanuil Rusev’s method of
recognizing the block type by [looking at the first character](https://github.com/erusev/parsedown/tree/1.7.4#questions)
is very clever and efficient, in my opinion.

### Attributes

My Markdown parser supports an extensive attribute syntax that includes a combination of `.class` and `#id` attribute
syntax, and `key=value` attribute syntax:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
# asdf {#asdf}
~~~

</td>
<td>

~~~ html
<h1 id="asdf">asdf</h1>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
# asdf {#asdf.asdf}
~~~

</td>
<td>

~~~ html
<h1 class="asdf" id="asdf">asdf</h1>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
# asdf {#asdf.asdf asdf=asdf}
~~~

</td>
<td>

~~~ html
<h1 asdf="asdf" class="asdf" id="asdf">asdf</h1>
~~~

</td>
</tr>
</tbody>
</table>

Inline attributes always win over native and pre-defined attribute syntax:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
[asdf](asdf){href=1}
~~~

</td>
<td>

~~~ html
<p><a href="1">asdf</a></p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
[asdf]

[asdf]: asdf {href=1}
~~~

</td>
<td>

~~~ html
<p><a href="1">asdf</a></p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
[asdf]{href=2}

[asdf]: asdf {href=1}
~~~

</td>
<td>

~~~ html
<p><a href="2">asdf</a></p>
~~~

</td>
</tr>
</tbody>
</table>

The attribute syntax can be written after any Markdown inline syntax (and some block syntax), as long as there are no
spaces present before it. This is different from the original Markdown Extra attribute syntax rules, which allow for
optional spaces before the opening attribute syntax:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
<asdf:asdf>{#asdf}
~~~

</td>
<td>

~~~ html
<p><a href="asdf:asdf" id="asdf">asdf:asdf</a></p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
<asdf@asdf>{#asdf}
~~~

</td>
<td>

~~~ html
<p><a href="mailto:asdf@asdf" id="asdf">asdf@asdf</a></p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
![asdf](asdf){#asdf}
~~~

</td>
<td rowspan="4">

~~~ html
<figure>
  <img alt="asdf" id="asdf" src="asdf" />
</figure>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
![asdf]{#asdf}

[asdf]: asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
![asdf][]{#asdf}

[asdf]: asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
![asdf][asdf]{#asdf}

[asdf]: asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
*asdf*{#asdf}
~~~

</td>
<td rowspan="2">

~~~ html
<p><em id="asdf">asdf</em></p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
_asdf_{#asdf}
~~~

</td>
</tr>
<tr>
<td>

~~~ md
**asdf**{#asdf}
~~~

</td>
<td rowspan="2">

~~~ html
<p><strong id="asdf">asdf</strong></p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
__asdf__{#asdf}
~~~

</td>
</tr>
<tr>
<td>

~~~ md
[asdf](asdf){#asdf}
~~~

</td>
<td rowspan="4">

~~~ html
<p><a href="asdf" id="asdf">asdf</a></p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
[asdf]{#asdf}

[asdf]: asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
[asdf][]{#asdf}

[asdf]: asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
[asdf][asdf]{#asdf}

[asdf]: asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
`asdf`{#asdf}
~~~

</td>
<td>

~~~ html
<p><code id="asdf">asdf</code></p>
~~~

</td>
</tr>
</tbody>
</table>

I decided it would be better to not allow optional spaces between them. There are several reasons for this:

 1. The CommonMark rules do not allow optional spaces after the link label [^link:1] [^link:2] [^link:3] for consistency
    with the link [shortcut](https://spec.commonmark.org/0.31.2#shortcut-reference-link) syntax. I assume that people
    who are already familiar with CommonMark rules would expect me to treat the attribute syntax the same way CommonMark
    treats the link parts syntax.

 1. It is easier to determine the priority when this construct occurs:

    ~~~ md
    # asdf asdf asdf [asdf](asdf) {#asdf}
    ~~~

    Allowing optional spaces before the attribute syntax would make it difficult to determine if the attribute syntax is
    part of the link or the header. With this restriction, the intent is clear:

    <table>
    <thead>
    <tr>
    <th>Markdown</th>
    <th>HTML</th>
    </tr>
    </thead>
    <tbody>
    <tr>
    <td>

    ~~~ md
    # asdf [asdf](asdf){#asdf}
    ~~~

    </td>
    <td>

    ~~~ html
    <h1>asdf <a href="asdf" id="asdf">asdf</a></h1>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    # asdf [asdf](asdf) {#asdf}
    ~~~

    </td>
    <td>

    ~~~ html
    <h1 id="asdf">asdf <a href="asdf">asdf</a></h1>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    # asdf [asdf](asdf){#asdf} {#asdf}
    ~~~

    </td>
    <td>

    ~~~ html
    <h1 id="asdf">asdf <a href="asdf" id="asdf">asdf</a></h1>
    ~~~

    </td>
    </tr>
    </tbody>
    </table>

 [^link:1]: <https://spec.commonmark.org/0.31.2#example-542>
 [^link:2]: <https://spec.commonmark.org/0.31.2#example-556>
 [^link:3]: <https://spec.commonmark.org/0.31.2#example-511>

Attribute syntax can be classified into two types:

#### Wrapped Attributes

Wrapped attribute syntax contains an attribute list written between curly braces:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
# asdf asdf asdf asdf {#asdf}
~~~

</td>
<td rowspan="2">

~~~ html
<h1 id="asdf">asdf asdf asdf asdf</h1>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
# asdf asdf asdf asdf # {#asdf}
~~~

</td>
</tr>
</tbody>
</table>

#### Naked Attributes

Naked attribute syntax contains an attribute list written with no curly braces. It usually appears after a block marker
where generic textual content should not immediately follow. Naked attribute syntax is currently supported by fenced
code block (where my attribute parser treats the [info string](https://spec.commonmark.org/0.31.2#info-string)),
[_setext_](https://spec.commonmark.org/0.31.2#setext-heading) header, and thematic break syntax:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~~ md
``` #asdf asdf=asdf
asdf asdf asdf asdf
```
~~~~

</td>
<td rowspan="2">

~~~ html
<pre><code asdf="asdf" id="asdf">asdf asdf asdf asdf
</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ #asdf asdf=asdf
asdf asdf asdf asdf
~~~
~~~~

</td>
</tr>
<tr>
<td>

~~~ md
asdf asdf asdf asdf
=== #asdf asdf=asdf
~~~

</td>
<td>

~~~ html
<h1 asdf="asdf" id="asdf">asdf asdf asdf asdf</h1>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
asdf asdf asdf asdf
--- #asdf asdf=asdf
~~~

</td>
<td>

~~~ html
<h2 asdf="asdf" id="asdf">asdf asdf asdf asdf</h2>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
asdf asdf asdf asdf

*** #asdf asdf=asdf

asdf asdf asdf asdf
~~~

</td>
<td rowspan="3">

~~~ html
<p>asdf asdf asdf asdf</p>
<hr asdf="asdf" id="asdf" />
<p>asdf asdf asdf asdf</p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
asdf asdf asdf asdf

--- #asdf asdf=asdf

asdf asdf asdf asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
asdf asdf asdf asdf

___ #asdf asdf=asdf

asdf asdf asdf asdf
~~~

</td>
</tr>
</tbody>
</table>

There is one special case regarding how they treat value-less attributes (attributes without the `=` part). Wrapped
attributes will treat value-less attributes as boolen attributes, whereas naked attributes will simply reject the entire
attribute list:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
asdf asdf asdf asdf
============== asdf
~~~

</td>
<td>

~~~ html
<p>asdf asdf asdf asdf ============== asdf</p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
asdf asdf asdf asdf
============ {asdf}
~~~

</td>
<td>

~~~ html
<h1 asdf>asdf asdf asdf asdf</h1>
~~~

</td>
</tr>
</tbody>
</table>

An exception to the fenced code block syntax is that value-less attributes in a naked attribute list will be treated as
a class suffix instead. This feature is implemented for compatibility with other Markdown parsers, which will generally
treat the info string as a code class with a `language-` prefix:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~~ md
~~~ asdf
asdf asdf asdf asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="language-asdf">asdf asdf asdf asdf
</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ .asdf asdf
asdf asdf asdf asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="asdf language-asdf">asdf asdf asdf asdf
</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ {asdf}
asdf asdf asdf asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code asdf>asdf asdf asdf asdf
</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ {.asdf asdf}
asdf asdf asdf asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code asdf class="asdf">asdf asdf asdf asdf
</code></pre>
~~~

</td>
</tr>
</tbody>
</table>

Most Markdown parsers that support attribute syntax do not explicitly state that they support attribute syntax in the
form of a fenced code block’s info string (my parser called it “naked attributes”) in a _setext_ header or thematic
break. This is just my own take on [this discussion](https://talk.commonmark.org/t/info-strings-elsewhere/2610).

### Code Block

I try to avoid incompatibilities between different Markdown dialects and support whatever dialect you are using. For
example, I am adapted to Markdown Extra’s fenced code block syntax, which employs an info string with a dot prefix.
However, this is not supported by Parsedown. Parsedown simply appends a `language-` prefix to the info string without
giving any special consideration to the pattern of the info string. CommonMark also does not specify any special rules
for processing info strings in fenced code block syntax.

Here’s how the code block results compare across each Markdown converter:

#### Markdown Extra

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~~ md
~~~ asdf
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="asdf">asdf
</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ .asdf
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="asdf">asdf
</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ asdf asdf
asdf
~~~
~~~~

</td>
<td>

_Invalid._

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ .asdf.asdf
asdf
~~~
~~~~

</td>
<td>

_Invalid._

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ {#asdf.asdf}
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="asdf" id="asdf">asdf
</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ {#asdf.asdf asdf=asdf}
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code asdf="asdf" class="asdf" id="asdf">asdf
</code></pre>
~~~

</td>
</tr>
</tbody>
</table>

#### Parsedown Extra

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~~ md
~~~ asdf
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="language-asdf">asdf</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ .asdf
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="language-.asdf">asdf</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ asdf asdf
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="language-asdf">asdf</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ .asdf.asdf
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="language-.asdf.asdf">asdf</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ {#asdf.asdf}
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="language-{#asdf.asdf}">asdf</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ {#asdf.asdf asdf=asdf}
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="language-{#asdf.asdf">asdf</code></pre>
~~~

</td>
</tr>
</tbody>
</table>

#### Mine

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~~ md
~~~ asdf
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="language-asdf">asdf
</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ .asdf
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="asdf">asdf
</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ asdf asdf
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="language-asdf">asdf
</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ .asdf.asdf
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="asdf">asdf
</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ {#asdf.asdf}
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code class="asdf" id="asdf">asdf
</code></pre>
~~~

</td>
</tr>
<tr>
<td>

~~~~ md
~~~ {#asdf.asdf asdf=asdf}
asdf
~~~
~~~~

</td>
<td>

~~~ html
<pre><code asdf="asdf" class="asdf" id="asdf">asdf
</code></pre>
~~~

</tr>
</tbody>
</table>

### HTML Block

CommonMark doesn’t care about the DOM and therefore also doesn’t care if a HTML element is perfectly balanced or not.
Unlike the original Markdown syntax specification which doesn’t allow you to convert Markdown syntax inside a HTML
block, the CommonMark specification doesn’t limit such a case. It cares about blank lines around the lines that look
like a HTML block tag, as specified in [Section 4.6](https://spec.commonmark.org/0.31.2#html-blocks), type 6.

Any text that comes after the opening and/or closing of a HTML block is treated as raw text and is not processed as
Markdown syntax. A blank line is required to end the raw HTML block state:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
<div> asdf asdf *asdf* asdf
</div> asdf asdf *asdf* asdf
~~~

</td>
<td>

~~~ html
<div> asdf asdf *asdf* asdf
</div> asdf asdf *asdf* asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
<div>
asdf asdf *asdf* asdf

</div>
asdf asdf *asdf* asdf
~~~

</td>
<td>

~~~ html
<div>
asdf asdf *asdf* asdf</div>
asdf asdf *asdf* asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
<div>

asdf asdf *asdf* asdf

</div>

asdf asdf *asdf* asdf
~~~

</td>
<td>

~~~ html
<div>
<p>asdf asdf <em>asdf</em> asdf</p>
</div>
<p>asdf asdf <em>asdf</em> asdf</p>
~~~

</td>
</tr>
</tbody>
</table>

Exception for types 1, 2, 3, 4, and 5. A line break is enough to end the raw HTML block state:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
<!-- asdf asdf *asdf* asdf --> asdf asdf *asdf* asdf
~~~

</td>
<td>

~~~ html
<!-- asdf asdf *asdf* asdf --> asdf asdf *asdf* asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
<!-- asdf asdf *asdf* asdf -->
asdf asdf *asdf* asdf
~~~

</td>
<td>

~~~ html
<!-- asdf asdf *asdf* asdf -->
<p>asdf asdf <em>asdf</em> asdf</p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
<!-- asdf asdf *asdf* asdf -->

asdf asdf *asdf* asdf
~~~

</td>
<td>

~~~ html
<!-- asdf asdf *asdf* asdf -->
<p>asdf asdf <em>asdf</em> asdf</p>
~~~

</td>
</tr>
</tbody>
</table>

The examples below will generate a predictable HTML code, but not because this converter cares about the existing HTML
tag balance:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
<nav>
<ul>
<li>
<a>asdf</a>
</li>
<li>
<a>asdf</a>
</li>
<li>
<a>asdf</a>
</li>
</ul>
</nav>

asdf asdf *asdf* asdf
~~~

</td>
<td>

~~~ html
<nav>
<ul>
<li>
<a>asdf</a>
</li>
<li>
<a>asdf</a>
</li>
<li>
<a>asdf</a>
</li>
</ul>
</nav>
<p>asdf asdf <em>asdf</em> asdf</p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
<nav>
  <ul>
    <li>
      <a>asdf</a>
    </li>
    <li>
      <a>asdf</a>
    </li>
    <li>
      <a>asdf</a>
    </li>
  </ul>
</nav>

asdf asdf *asdf* asdf
~~~

</td>
<td>

~~~ html
<nav>
  <ul>
    <li>
      <a>asdf</a>
    </li>
    <li>
      <a>asdf</a>
    </li>
    <li>
      <a>asdf</a>
    </li>
  </ul>
</nav>
<p>asdf asdf <em>asdf</em> asdf</p>
~~~

</td>
</tr>
</tbody>
</table>

You will understand why when you add a number of blank lines at any point in the HTML block:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
<nav>
<ul>
<li>
<a>

asdf</a>
</li>

<li>
<a>asdf</a>
</li>
<li>
<a>asdf</a>
</li>
</ul>
</nav>

asdf asdf *asdf* asdf
~~~

</td>
<td>

~~~ html
<nav>
<ul>
<li>
<a>
<p>asdf</a></p>
</li>
<li>
<a>asdf</a>
</li>
<li>
<a>asdf</a>
</li>
</ul>
</nav>
<p>asdf asdf <em>asdf</em> asdf</p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
<nav>
  <ul>
    <li>
      <a>

      asdf</a>
    </li>

    <li>
      <a>asdf</a>
    </li>
    <li>
      <a>asdf</a>
    </li>
  </ul>
</nav>

asdf asdf *asdf* asdf
~~~

</td>
<td>

~~~ html
<nav>
  <ul>
    <li>
      <a>
<pre><code>  asdf&lt;/a&gt;
&lt;/li&gt;

&lt;li&gt;
&lt;a&gt;asdf&lt;/a&gt;
&lt;/li&gt;
&lt;li&gt;
&lt;a&gt;asdf&lt;/a&gt;
&lt;/li&gt;
</code></pre>
  </ul>
</nav>
<p>asdf asdf <em>asdf</em> asdf</p>
~~~

</td>
</tr>
</tbody>
</table>

Markdown Extra features the `markdown` attribute on HTML to allow you to convert Markdown syntax to HTML in a HTML
block. In this converter, the feature will not work. For now, I have no plans to add such feature to avoid DOM parsing
tasks as much as possible. This also ensured me to avoid on using [PHP `dom`](https://www.php.net/book.dom).

However, if you add a blank line, it’s as if the feature works (although the `markdown` attribute is still there, it
doesn’t affect the HTML when rendered in the browser window). If you’re used to adding a blank line after the opening
HTML block tag and before the closing HTML block tag, you should be okay.

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
<div markdown="1">
asdf asdf *asdf* asdf
</div>
~~~

</td>
<td>

~~~ html
<div markdown="1">
asdf asdf *asdf* asdf
</div>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
<div markdown="1">

asdf asdf *asdf* asdf
</div>
~~~

</td>
<td>

~~~ html
<div markdown="1">
<p>asdf asdf <em>asdf</em> asdf</p>
</div>
~~~

</td>
</tr>
</tbody>
</table>

Opening an inline HTML element will not trigger the raw HTML block state unless the opening and closing tags stand alone
on a single line. This is explained in [Section 4.6](https://spec.commonmark.org/0.31.2#html-blocks), type 7:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
<del>asdf asdf *asdf*</del> asdf *asdf* asdf
~~~

</td>
<td>

~~~ html
<p><del>asdf asdf <em>asdf</em></del> asdf <em>asdf</em> asdf</p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
<del>
asdf asdf *asdf*
</del>
asdf *asdf* asdf
~~~

</td>
<td>

~~~ html
<del>
asdf asdf *asdf*
</del>
asdf *asdf* asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
<del>

asdf asdf *asdf*

</del>

asdf *asdf* asdf
~~~

</td>
<td>

~~~ html
<del>
<p>asdf asdf <em>asdf</em></p>
</del>
<p>asdf <em>asdf</em> asdf</p>
~~~

</td>
</tr>
</tbody>
</table>

Since CommonMark doesn’t care about HTML structure, the examples below will also conform to the specification, even if
they result in broken HTML. However, these are very rarely intentionally written by hand, so such cases are very
unlikely to occur:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
<h1>

asdf asdf *asdf* asdf

</h1>
~~~

</td>
<td>

~~~ html
<h1>
<p>asdf asdf <em>asdf</em> asdf</p>
</h1>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
<p>

asdf asdf *asdf* asdf

</p>
~~~

</td>
<td>

~~~ html
<p>
<p>asdf asdf <em>asdf</em> asdf</p>
</p>
~~~

</td>
</tr>
</tbody>
</table>

### Image Block

Markdown was introduced before the HTML5 era. When the `<figure>` element was introduced, people started to use it to
display images with captions. Most Markdown parsers would convert an image syntax that stands alone on a single line
into an image element wrapped in a paragraph element. However, my parser would instead wrap it in a figure element. For
now, it seems that a figure element would be more desirable in this situation.

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
asdf asdf asdf asdf

![asdf](asdf)

asdf asdf asdf asdf
~~~

</td>
<td>

~~~ html
<p>asdf asdf asdf asdf</p>
<figure>
  <img alt="asdf" src="asdf" />
</figure>
<p>asdf asdf asdf asdf</p>
~~~

</td>
</tr>
</tbody>
</table>

A paragraph that immediately follows the image syntax will be treated as the image caption:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
asdf asdf asdf asdf

![asdf](asdf)
asdf asdf asdf asdf

asdf asdf asdf asdf
~~~

</td>
<td>

~~~ html
<p>asdf asdf asdf asdf</p>
<figure>
  <img alt="asdf" src="asdf" />
  <figcaption>asdf asdf asdf asdf</figcaption>
</figure>
<p>asdf asdf asdf asdf</p>
~~~

</td>
</tr>
</tbody>
</table>

Block elements other than paragraph can also be used as the image caption. To make this work, you need to indent the
portions you want to include in the caption with spaces:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
asdf asdf asdf asdf

![asdf](asdf)
 # asdf asdf asdf asdf

asdf asdf asdf asdf
~~~

</td>
<td>

~~~ html
<p>asdf asdf asdf asdf</p>
<figure>
  <img alt="asdf" src="asdf" />
  <figcaption>
    <h1>asdf asdf asdf asdf</h1>
  </figcaption>
</figure>
<p>asdf asdf asdf asdf</p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
asdf asdf asdf asdf

![asdf](asdf)

  asdf asdf asdf asdf

asdf asdf asdf asdf
~~~

</td>
<td>

~~~ html
<p>asdf asdf asdf asdf</p>
<figure>
  <img alt="asdf" src="asdf" />
  <figcaption>
    <p>asdf asdf asdf asdf</p>
  </figcaption>
</figure>
<p>asdf asdf asdf asdf</p>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
asdf asdf asdf asdf

![asdf](asdf)

  asdf asdf asdf asdf
  -------------------

  asdf asdf asdf asdf

asdf asdf asdf asdf
~~~

</td>
<td>

~~~ html
<p>asdf asdf asdf asdf</p>
<figure>
  <img alt="asdf" src="asdf" />
  <figcaption>
    <h2>asdf asdf asdf asdf</h2>
    <p>asdf asdf asdf asdf</p>
  </figcaption>
</figure>
<p>asdf asdf asdf asdf</p>
~~~

</td>
</tr>
</tbody>
</table>

### List Block

My parser supports list items numbered with Latin letters and Roman numerals. While this satisfies the HTML5
specification for [the `type` attribute of `<ol>` element](https://html.spec.whatwg.org/multipage/grouping-content.html#attr-ol-type),
it does not satisfy the CommonMark rules. Instead, it “extends” them.

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
1) asdf asdf asdf asdf
2) asdf asdf asdf asdf
3) asdf asdf asdf asdf
~~~

</td>
<td rowspan="2">

~~~ html
<ol>
  <li>asdf asdf asdf asdf</li>
  <li>asdf asdf asdf asdf</li>
  <li>asdf asdf asdf asdf</li>
</ol>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
1. asdf asdf asdf asdf
2. asdf asdf asdf asdf
3. asdf asdf asdf asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
A) asdf asdf asdf asdf
B) asdf asdf asdf asdf
C) asdf asdf asdf asdf
~~~

</td>
<td rowspan="2">

~~~ html
<ol type="A">
  <li>asdf asdf asdf asdf</li>
  <li>asdf asdf asdf asdf</li>
  <li>asdf asdf asdf asdf</li>
</ol>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
A. asdf asdf asdf asdf
B. asdf asdf asdf asdf
C. asdf asdf asdf asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
a) asdf asdf asdf asdf
b) asdf asdf asdf asdf
c) asdf asdf asdf asdf
~~~

</td>
<td rowspan="2">

~~~ html
<ol type="a">
  <li>asdf asdf asdf asdf</li>
  <li>asdf asdf asdf asdf</li>
  <li>asdf asdf asdf asdf</li>
</ol>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
a. asdf asdf asdf asdf
b. asdf asdf asdf asdf
c. asdf asdf asdf asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
I) asdf asdf asdf asdf
II) asdf asdf asdf asdf
III) asdf asdf asdf asdf
~~~

</td>
<td rowspan="2">

~~~ html
<ol type="I">
  <li>asdf asdf asdf asdf</li>
  <li>asdf asdf asdf asdf</li>
  <li>asdf asdf asdf asdf</li>
</ol>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
I. asdf asdf asdf asdf
II. asdf asdf asdf asdf
III. asdf asdf asdf asdf
~~~

</td>
</tr>
<tr>
<td>

~~~ md
i) asdf asdf asdf asdf
ii) asdf asdf asdf asdf
iii) asdf asdf asdf asdf
~~~

</td>
<td rowspan="2">

~~~ html
<ol type="i">
  <li>asdf asdf asdf asdf</li>
  <li>asdf asdf asdf asdf</li>
  <li>asdf asdf asdf asdf</li>
</ol>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
i. asdf asdf asdf asdf
ii. asdf asdf asdf asdf
iii. asdf asdf asdf asdf
~~~

</td>
</tr>
</tbody>
</table>

Since the list block marker can now be any lower-case or upper-case characters and will also treat the character “I” and
“i” as the start of a list block with Roman numerals, the rules for this type of list block have been strictly enforced:

 1. A **type “A”** list block can only start with the prefix `A) ` or `A. `.

    <table>
    <thead>
    <tr>
    <th>Markdown</th>
    <th>HTML</th>
    <tr>
    </thead>
    <tbody>
    <tr>
    <td>

    ~~~ md
    A) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="A">
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    B) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <p>B) asdf asdf asdf asdf</p>
    ~~~

    </td>
    </tr>
    </tbody>
    </table>

 1. A **type “a”** list block can only start with the prefix `a) ` or `a. `.
 1. A **type “I”** list block can only start with the prefix `I) ` or `I. `.

    <table>
    <thead>
    <tr>
    <th>Markdown</th>
    <th>HTML</th>
    <tr>
    </thead>
    <tbody>
    <tr>
    <td>

    ~~~ md
    I) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="I">
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    II) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <p>II) asdf asdf asdf asdf</p>
    ~~~

    </td>
    </tr>
    </tbody>
    </table>

 1. A **type “i”** list block can only start with the prefix `i) ` or `i. `.

Those list types don’t support custom `start` attribute (they will always start from 1), and like the `1) ` and `1. `
prefixes, they can interrupt the paragraph:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
<tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
asdf asdf asdf asdf
A) asdf asdf asdf asdf
~~~

</td>
<td>

~~~ html
<p>asdf asdf asdf asdf</p>
<ol type="A">
  <li>asdf asdf asdf asdf</li>
</ol>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
asdf asdf asdf asdf
B) asdf asdf asdf asdf
~~~

</td>
<td>

~~~ html
<p>asdf asdf asdf asdf B) asdf asdf asdf asdf</p>
~~~

</td>
</tr>
</tbody>
</table>

The list item continuation “number” is enforced as follows:

 1. A list item continuation can only use the current number or the next number after it.

    <table>
    <thead>
    <tr>
    <th>Markdown</th>
    <th>HTML</th>
    <tr>
    </thead>
    <tbody>
    <tr>
    <td>

    ~~~ md
    1) asdf asdf asdf asdf
    2) asdf asdf asdf asdf
    3) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    1) asdf asdf asdf asdf
    1) asdf asdf asdf asdf
    1) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    2) asdf asdf asdf asdf
    2) asdf asdf asdf asdf
    2) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol start="2">
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    1) asdf asdf asdf asdf
    2) asdf asdf asdf asdf
    2) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    1) asdf asdf asdf asdf
    3) asdf asdf asdf asdf
    4) asdf asdf asdf asdf
    5) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol>
      <li>asdf asdf asdf asdf 3) asdf asdf asdf asdf 4) asdf asdf asdf asdf 5) asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    1) asdf asdf asdf asdf
    3) asdf asdf asdf asdf
    2) asdf asdf asdf asdf
    3) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="A">
      <li>asdf asdf asdf asdf 3) asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    1) asdf asdf asdf asdf
    3) asdf asdf asdf asdf
    1) asdf asdf asdf asdf
    1) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol>
      <li>asdf asdf asdf asdf 3) asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    </tbody>
    </table>

 1. A list item continuation of **type “A”** list block can only use the current character or the next character after
    it. After the character “Z”, the list continues with “AA”, “AB”, “AC”, and so on.

    <table>
    <thead>
    <tr>
    <th>Markdown</th>
    <th>HTML</th>
    <tr>
    </thead>
    <tbody>
    <tr>
    <td>

    ~~~ md
    A) asdf asdf asdf asdf
    B) asdf asdf asdf asdf
    C) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="A">
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    A) asdf asdf asdf asdf
    A) asdf asdf asdf asdf
    A) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="A">
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    B) asdf asdf asdf asdf
    B) asdf asdf asdf asdf
    B) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <p>B) asdf asdf asdf asdf B) asdf asdf asdf asdf B) asdf asdf asdf asdf</p>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    A) asdf asdf asdf asdf
    B) asdf asdf asdf asdf
    B) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="A">
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    A) asdf asdf asdf asdf
    C) asdf asdf asdf asdf
    D) asdf asdf asdf asdf
    E) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="A">
      <li>asdf asdf asdf asdf C) asdf asdf asdf asdf D) asdf asdf asdf asdf E) asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    A) asdf asdf asdf asdf
    C) asdf asdf asdf asdf
    B) asdf asdf asdf asdf
    C) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="A">
      <li>asdf asdf asdf asdf C) asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    A) asdf asdf asdf asdf
    C) asdf asdf asdf asdf
    A) asdf asdf asdf asdf
    A) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="A">
      <li>asdf asdf asdf asdf C) asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    </tbody>
    </table>

 1. A list item continuation of **type “a”** list block can only use the current character or the next character after
    it. After the character “z”, the list continues with “aa”, “ab”, “ac”, and so on.
 1. A list Item continuation of **type “I”** list block can only use the current Roman numeral or the next Roman numeral
    after it.

    <table>
    <thead>
    <tr>
    <th>Markdown</th>
    <th>HTML</th>
    <tr>
    </thead>
    <tbody>
    <tr>
    <td>

    ~~~ md
    I) asdf asdf asdf asdf
    II) asdf asdf asdf asdf
    III) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="I">
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    I) asdf asdf asdf asdf
    I) asdf asdf asdf asdf
    I) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="I">
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    II) asdf asdf asdf asdf
    II) asdf asdf asdf asdf
    II) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <p>II) asdf asdf asdf asdf II) asdf asdf asdf asdf II) asdf asdf asdf asdf</p>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    I) asdf asdf asdf asdf
    II) asdf asdf asdf asdf
    II) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="I">
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    I) asdf asdf asdf asdf
    III) asdf asdf asdf asdf
    IV) asdf asdf asdf asdf
    V) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="I">
      <li>asdf asdf asdf asdf III) asdf asdf asdf asdf IV) asdf asdf asdf asdf V) asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    I) asdf asdf asdf asdf
    III) asdf asdf asdf asdf
    II) asdf asdf asdf asdf
    III) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="I">
      <li>asdf asdf asdf asdf III) asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    I) asdf asdf asdf asdf
    III) asdf asdf asdf asdf
    I) asdf asdf asdf asdf
    I) asdf asdf asdf asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <ol type="I">
      <li>asdf asdf asdf asdf III) asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
      <li>asdf asdf asdf asdf</li>
    </ol>
    ~~~

    </td>
    </tr>
    </tbody>
    </table>

 1. A list Item continuation of **type “i”** list block can only use the current Roman numeral or the next Roman numeral
    after it.

Those rules should effectively prevent results that most people don’t expect. This usually happens when a paragraph
continuation contains a sequence of letters followed by a `) ` or a `. `.

Consider the following examples. Without stricter rules for the list block, my parser would mistakenly treat `asdf) `
and `asdf. ` as the start of another list item:

~~~
a) asdf (asdf
asdf) asdf
b) asdf asdf
c) asdf asdf
~~~

~~~
a) asdf (asdf
   asdf) asdf
b) asdf asdf
c) asdf asdf
~~~

~~~
a. asdf asdf
asdf. asdf
b. asdf asdf
c. asdf asdf
~~~

~~~
a. asdf asdf
   asdf. asdf
b. asdf asdf
c. asdf asdf
~~~

Also, consider the following example which is taken from [this discussion](https://talk.commonmark.org/t/bad-interaction-between-laziness-rule-and-ordered-lists/9085?u=taufik-nurrohman):

~~~
1. Before the end of the paragraph, I invite you to
consider a very large number. For example,
45000000. Are you thinking about it?
~~~

With my stricter rules, the result will be more desirable:

<table>
<thead>
<tr>
<th>CommonMark</th>
<th>Mine</th>
<tr>
</thead>
<tbody>
<tr>
<td>

~~~ html
<ol>
  <li>Before the end of the paragraph, I invite you to
  consider a very large number. For example,</li>
  <li>Are you thinking about it?</li>
</ol>
~~~

</td>
<td>

~~~ html
<ol>
  <li>Before the end of the paragraph, I invite you to consider a very large number. For example, 45000000. Are you thinking about it?</li>
</ol>
~~~

</td>
</tr>
</tbody>
</table>

Please note that this is just my idea of how CommonMark could improve its implementation. For best compatibility with
other Markdown parsers, I would still recommend you to write the list item numbers in order, just in case you want to
switch in the future. Alternatively, if you’re too lazy or expect that the list will grow over time, you can always
reuse the number from the last list item.

### Notes

Notes follow [Markdown Extra’s syntax for notes](https://michelf.ca/projects/php-markdown/extra#footnotes), but with
slightly different HTML output to match [Mecha](https://github.com/mecha-cms)’s common naming style. Unlike Markdown
Extra, multi-line notes don’t have to be indented by four spaces. A space or tab is suffice to continue the note.

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
asdf [^1]

[^1]: asdf
~~~

</td>
<td>

~~~ html
<p>asdf <sup id="from:1"><a href="#to:1" role="doc-noteref">1</a></sup></p>
<div role="doc-endnotes">
  <hr />
  <ol>
    <li id="to:1" role="doc-endnote">
      <p>asdf&#xa0;<a href="#from:1" role="doc-backlink">&#x21a9;</a></p>
    </li>
  </ol>
</div>
~~~

</td>
</tr>
<tr>
<td>

~~~ md
asdf [^1]

[^1]:

  asdf
  ----

  asdf
  asdf

      asdf

  asdf
  asdf

asdf
~~~

</td>
<td>

~~~ html
<p>asdf <sup id="from:1"><a href="#to:1" role="doc-noteref">1</a></sup></p>
<p>asdf</p>
<div role="doc-endnotes">
  <hr />
  <ol>
    <li id="to:1" role="doc-endnote">
      <h2>asdf</h2>
      <p>asdf asdf</p>
      <pre><code>asdf
      </code></pre>
      <p>asdf asdf&#xa0;<a href="#from:1" role="doc-backlink">&#x21a9;</a></p>
    </li>
  </ol>
</div>
~~~

</td>
</tr>
</tbody>
</table>

### Soft Break

[Soft breaks](https://spec.commonmark.org/0.31.2#softbreak) are collapsed to a space in non-critical parts, such as
within paragraphs and list items:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
asdf asdf asdf asdf
asdf asdf asdf asdf

asdf asdf asdf asdf
~~~

</td>
<td>

~~~ html
<p>asdf asdf asdf asdf asdf asdf asdf asdf</p>
<p>asdf asdf asdf asdf</p>
~~~

</td>
</tr>
</tbody>
</table>

### Table Block

Table blocks follow [Markdown Extra’s syntax for table blocks](https://michelf.ca/projects/php-markdown/extra#table),
but with a few additional features and rules:

 1. The actual number of columns for each row in a table is determined by the number of columns in the table header
    separator.

 1. If the number of columns in the table header and/or table data exceeds the actual number of columns, the excess
    literal pipe characters will be treated as plain text so that they won’t create unnecessary columns. Conversely, if
    the number of columns in the table header and/or table data is less than the actual number of columns, several empty
    columns will be inserted to the right automatically.

 1. Literal pipe characters in table columns must be properly escaped. The only exceptions are those that appear in
    [auto-link](https://spec.commonmark.org/0.31.2#autolink), [code span](https://spec.commonmark.org/0.31.2#code-span),
    and the attribute values of [raw HTML](https://spec.commonmark.org/0.31.2#raw-html) tags:

    <table>
    <thead>
    <tr>
    <th>Markdown</th>
    <th>HTML</th>
    <tr>
    </thead>
    <tbody>
    <tr>
    <td>

    ~~~ md
    asdf         | asdf \| asdf
    ------------ | ------------
    asdf \| asdf | asdf
    asdf         | asdf \| asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <table>
      <thead>
        <tr>
          <th>asdf</th>
          <th>asdf | asdf</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>asdf | asdf</td>
          <td>asdf</td>
        </tr>
        <tr>
          <td>asdf</td>
          <td>asdf | asdf</td>
        </tr>
      </tbody>
    </table>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    asdf                    | <asdf:asdf?asdf=|asdf|>
    ----------------------- | -----------------------
    <asdf:asdf?asdf=|asdf|> | asdf
    asdf                    | <asdf:asdf?asdf=|asdf|>
    ~~~

    </td>
    <td>

    ~~~ html
    <table>
      <thead>
        <tr>
          <th>asdf</th>
          <th><a href="asdf:asdf?asdf=%7Casdf%7C">asdf:asdf?asdf=|asdf|</a></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><a href="asdf:asdf?asdf=%7Casdf%7C">asdf:asdf?asdf=|asdf|</a></td>
          <td>asdf</td>
        </tr>
        <tr>
          <td>asdf</td>
          <td><a href="asdf:asdf?asdf=%7Casdf%7C">asdf:asdf?asdf=|asdf|</a></td>
        </tr>
      </tbody>
    </table>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    asdf          | `asdf | asdf`
    ------------- | -------------
    `asdf | asdf` | asdf
    asdf          | `asdf | asdf`
    ~~~

    </td>
    <td>

    ~~~ html
    <table>
      <thead>
        <tr>
          <th>asdf</th>
          <th><code>asdf | asdf</code></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><code>asdf | asdf</code></td>
          <td>asdf</td>
        </tr>
        <tr>
          <td>asdf</td>
          <td><code>asdf | asdf</code></td>
        </tr>
      </tbody>
    </table>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    asdf            | <asdf asdf="|">
    --------------- | ---------------
    <asdf asdf="|"> | asdf
    asdf            | <asdf asdf="|">
    ~~~

    </td>
    <td>

    ~~~ html
    <table>
      <thead>
        <tr>
          <th>asdf</th>
          <th><asdf asdf="|"></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><asdf asdf="|"></td>
          <td>asdf</td>
        </tr>
        <tr>
          <td>asdf</td>
          <td><asdf asdf="|"></td>
        </tr>
      </tbody>
    </table>
    ~~~

    </td>
    </tr>
    </tbody>
    </table>

 1. Header-less table is supported, but may not be compatible with other Markdown parsers. Consider using this feature
    as rarely as possible, unless you have no plans to switch to other Markdown parsers in the future:

    <table>
    <thead>
    <tr>
    <th>Markdown</th>
    <th>HTML</th>
    <tr>
    </thead>
    <tbody>
    <tr>
    <td>

    ~~~ md
    asdf | asdf
    ---- | ----
    ~~~

    </td>
    <td>

    ~~~ html
    <table>
      <thead>
        <tr>
          <th>asdf</th>
          <th>asdf</th>
        </tr>
      </thead>
    </table>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    ---- | ----
    asdf | asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <table>
      <tbody>
        <tr>
          <td>asdf</td>
          <td>asdf</td>
        </tr>
      </tbody>
    </table>
    ~~~

    </td>
    </tr>
    </tbody>
    </table>

 1. Table captions are supported and can be written in the same way as image captions are written:

    <table>
    <thead>
    <tr>
    <th>Markdown</th>
    <th>HTML</th>
    <tr>
    </thead>
    <tbody>
    <tr>
    <td>

    ~~~ md
    | asdf | asdf |
    | ---- | ---- |
    | asdf | asdf |
    asdf

    asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <table>
      <caption>asdf</caption>
      <thead>
        <tr>
          <th>asdf</th>
          <th>asdf</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>asdf</td>
          <td>asdf</td>
        </tr>
      </tbody>
    </table>
    <p>asdf</p>
    ~~~

    </td>
    </tr>
    <tr>
    <td>

    ~~~ md
    | asdf | asdf |
    | ---- | ---- |
    | asdf | asdf |

      asdf
      ----

      asdf

    asdf
    ~~~

    </td>
    <td>

    ~~~ html
    <table>
      <caption>
        <h2>asdf</h2>
        <p>asdf</p>
      </caption>
      <thead>
        <tr>
          <th>asdf</th>
          <th>asdf</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>asdf</td>
          <td>asdf</td>
        </tr>
      </tbody>
    </table>
    <p>asdf</p>
    ~~~

    </td>
    </tr>
    </tbody>
    </table>

### Tabs

Unlike CommonMark, this parser does not preserve tabs. This is probably the hardest part. Once it’s solved, the parser
will be 100% compliant with the CommonMark rules. However, I am not currently an expert in this area. Initially, it does
seem possible. I was able to preserve the tab characters, but it turns out that this only works for top-level blocks.
Once I enter a container block to parse its content, I then lose track of the correct column position because the
container block’s markers have been removed.

The recommended CommonMark parsing strategy is to parse the inner blocks of the current container block immediately,
producing nested blocks instantly.

Given this input:

~~~ md
asdf asdf asdf asdf

> asdf asdf asdf asdf
>
> 1. asdf asdf asdf asdf
>
>    asdf asdf asdf asdf
~~~

CommonMark would parse the input as follows:

 1. At line 1, got a paragraph block.
 2. At line 2, got a blank line which marks the end of the paragraph.
 3. At line 3, got a quote block.
    1. At line 3, got a paragraph block.
    2. At line 4, got a blank line which marks the end of the paragraph block.
       1. At line 5, got a list block.
          1. At line 5, got a paragraph block.
          2. At line 6, got a blank line which marks the end of the paragraph block.
          3. At line 7, got a paragraph block.
          4. At the end of the line, all open blocks will be closed.

My parser doesn’t work that way. Instead, it extracts the inner blocks as plain Markdown text. Once all top-level blocks
have been processed, it moves on to parse the inner blocks:

 1. At line 1, got a paragraph block.
 2. At line 2, got a blank line which marks the end of the paragraph; push it to the array.
 3. At line 3, got a quote block.
 4. At line 4 up to line 7, got a quote block continuation.
 5. At the end of the line, push the last block (the quote block) to the array.
 6. Iterate over the array to find the container blocks. Then, repeat this process within those blocks.

Due to the way I parse, it is hard to keep track of the current column position, though it can be done with more effort.
However, doing so would make the parser overly complex. The least complex way to correctly store white space column
positions is to convert all tab sequences to spaces. This ensures that, when the container block markers are omitted,
the white space column positions of the child blocks will be shifted correctly.

All CommonMark white space column rules are passed. The only limitation is that it is currently not possible to preserve
tab characters.

For tab characters in code blocks, you can preserve them [this way](). Though, it would be more accurate to call it “tab
normalization” than “tab preservation”.

XSS
---

This converter is intended only to convert Markdown syntax to HTML based on the
[CommonMark](https://spec.commonmark.org/0.31.2) specification. It doesn’t care about your user input. I have no
intention of adding any special security features in the future, sorry. The attribute syntax feature may be a security
risk for you if you want to use this converter on your comment entries, for example:

<table>
<thead>
<tr>
<th>Markdown</th>
<th>HTML</th>
</tr>
</thead>
<tbody>
<tr>
<td>

~~~ md
![asdf](asdf){onerror="alert('Yo!')"}
~~~

</td>
<td>

~~~ html
<figure>
  <img alt="asdf" onerror="alert('Yo!')" src="asdf" />
</figure>
~~~

</td>
</tr>
</tbody>
</table>

There should be many specialized PHP applications already that have specific tasks to deal with XSS, so consider
post-processing the generated HTML markup before putting it out to the web:

 - [ezyang/htmlpurifier](https://github.com/ezyang/htmlpurifier)
 - [voku/anti-xss](https://github.com/voku/anti-xss)

Tools
-----

Clone this repository into the root of your web server that supports PHP and then you can open the `tools/test/from.php`
and `tools/try.php` file with your browser to see the result and the performance of this converter in various cases.

Tweaks
------

_TODO_