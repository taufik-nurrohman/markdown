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

From time to time, the history of Mecha slowly forms the dialect of my Markdown writing style. The Markdown extension
used by Mecha [was first](https://github.com/mecha-cms/mecha/tree/v1.2.2) built with
[Michel Fortin’s Markdown converter](https://michelf.ca/projects/php-markdown) (which I believe is the very first port
of a PHP-based Markdown converter originally written in Perl by
[John Gruber](https://daringfireball.net/projects/markdown)). Until the release of
[Mecha version 1.2.3](https://github.com/mecha-cms/mecha/tree/v1.2.3), I decided to switch to
[Parsedown](https://github.com/erusev/parsedown) because it was quite popular at the time. It can also do the conversion
process much faster. Emanuil Rusev’s way of detecting the block type
[by reading the first character](https://github.com/erusev/parsedown/tree/1.7.4#questions) is, in my opinion, very
clever and efficient.

### Attributes

My Markdown converter supports a more extensive attribute syntax, including a mix of `.class` and `#id` attribute
syntax, and a mix of `key=value` attribute syntax:

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code># asdf {#asdf}</code></pre></td>
      <td><pre><code>&lt;h1 id="asdf"&gt;asdf&lt;/h1&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code># asdf {#asdf.asdf}</code></pre></td>
      <td><pre><code>&lt;h1 class="asdf" id="asdf"&gt;asdf&lt;/h1&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code># asdf {#asdf.asdf asdf=asdf}</code></pre></td>
      <td><pre><code>&lt;h1 asdf="asdf" class="asdf" id="asdf"&gt;asdf&lt;/h1&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

### Code Block

I aim to avoid conflict between different Markdown dialects and try to support whatever dialect you are using. For
example, since I originally used Markdown Extra, I am used to adding info string with a dot prefix to the fenced code
block syntax. This is not supported by Parsedown (or rather, Parsedown doesn’t care about the pattern of the given info
string and simply appends `language-` prefix to it, since CommonMark also doesn’t give implementors special rules for
processing info string in fenced code block syntax).

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
      <td><pre><code>~~~ asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="asdf"&gt;asdf&#10;&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ .asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="asdf"&gt;asdf&#10;&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ asdf asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><em>Invalid.</em></td>
    </tr>
    <tr>
      <td><pre><code>~~~ .asdf.asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><em>Invalid.</em></td>
    </tr>
    <tr>
      <td><pre><code>~~~ {#asdf.asdf}&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="asdf" id="asdf"&gt;asdf&#10;&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ {#asdf.asdf asdf=asdf}&#10;asdf&#10;~~~</code></pre></td>
      <td><em>Invalid.</em></td>
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
      <td><pre><code>~~~ asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ .asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-.asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ asdf asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ .asdf.asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-.asdf.asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ {#asdf.asdf}&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-{#asdf.asdf}"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ {#asdf.asdf asdf=asdf}&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-{#asdf.asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</tr>
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
      <td><pre><code>~~~ asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ .asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ asdf asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-asdf language-asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ .asdf.asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="asdf asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ {#asdf.asdf}&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="asdf" id="asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ {#asdf.asdf asdf=asdf}&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code asdf="asdf" class="asdf" id="asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</tr>
  </tbody>
</table>

### Foot Notes

_TODO_

### Image Block

_TODO_

### Raw Block

CommonMark doesn’t care about the DOM and therefore also doesn’t care if a HTML element is perfectly balanced or not.
Unlike the original Markdown syntax specification which doesn’t allow you to convert Markdown syntax inside a HTML
block, the CommonMark specification doesn’t limit such a case. It cares about blank lines around the lines that look
like an HTML block tag, as specified in [Section 4.6](https://spec.commonmark.org/0.30#html-blocks), type 6.

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
      <td><pre><code>&lt;div&gt; asdf asdf &#42;asdf&#42; asdf&#10;&lt;/div&gt; asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;div&gt; asdf asdf &#42;asdf&#42; asdf&#10;&lt;/div&gt; asdf asdf &#42;asdf&#42; asdf</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;div&gt;&#10;asdf asdf &#42;asdf&#42; asdf&#10;&#10;&lt;/div&gt;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;div&gt;&#10;asdf asdf &#42;asdf&#42; asdf&lt;/div&gt;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;div&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf&#10;&#10;&lt;/div&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;div&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;&lt;/div&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
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
      <td><pre><code>&lt;!-- asdf asdf &#42;asdf&#42; asdf --&gt; asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;!-- asdf asdf &#42;asdf&#42; asdf --&gt; asdf asdf &#42;asdf&#42; asdf</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;!-- asdf asdf &#42;asdf&#42; asdf --&gt;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;!-- asdf asdf &#42;asdf&#42; asdf --&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;!-- asdf asdf &#42;asdf&#42; asdf --&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;!-- asdf asdf &#42;asdf&#42; asdf --&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
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
      <td><pre><code>&lt;nav&gt;&#10;&lt;ul&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;/ul&gt;&#10;&lt;/nav&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;nav&gt;&#10;&lt;ul&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;/ul&gt;&#10;&lt;/nav&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;nav&gt;&#10;  &lt;ul&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;  &lt;/ul&gt;&#10;&lt;/nav&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;nav&gt;&#10;  &lt;ul&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;  &lt;/ul&gt;&#10;&lt;/nav&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
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
      <td><pre><code>&lt;nav&gt;&#10;&lt;ul&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;&#10;&#10;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;/ul&gt;&#10;&lt;/nav&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;nav&gt;&#10;&lt;ul&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;&lt;p&gt;asdf&lt;/a&gt;&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;/ul&gt;&#10;&lt;/nav&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;nav&gt;&#10;  &lt;ul&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;&#10;&#10;      asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;  &lt;/ul&gt;&#10;&lt;/nav&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;nav&gt;&#10;  &lt;ul&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;&lt;pre&gt;&lt;code&gt;  asdf&amp;lt;/a&amp;gt;&#10;&amp;lt;/li&amp;gt;&#10;&#10;&amp;lt;li&amp;gt;&#10;  &amp;lt;a&amp;gt;asdf&amp;lt;/a&amp;gt;&#10;&amp;lt;/li&amp;gt;&#10;&amp;lt;li&amp;gt;&#10;  &amp;lt;a&amp;gt;asdf&amp;lt;/a&amp;gt;&#10;&amp;lt;/li&amp;gt;&#10;&lt;/code&gt;&lt;/pre&gt;&lt;/ul&gt;&#10;&lt;/nav&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

Markdown Extra features the `markdown` attribute on HTML to allow you to convert Markdown syntax to HTML in a HTML
block. In this converter, the feature will not work. For now, I have no plans to add such feature to avoid DOM parsing
tasks as much as possible. This also ensured me to avoid on using [PHP `dom`](https://www.php.net/book.dom).

However, if you add a blank line, it’s as if the feature works (although the `markdown` attribute is still there, it
doesn’t affect the HTML when rendered in the browser window). If you’re used to adding a blank line after the opening
HTML block tag and before the closing HTML block tag, don’t bother!

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>&lt;div markdown="1"&gt;&#10;asdf asdf &#42;asdf&#42; asdf&#10;&lt;/div&gt;</code></pre></td>
      <td><pre><code>&lt;div markdown="1"&gt;&#10;asdf asdf &#42;asdf&#42; asdf&#10;&lt;/div&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;div markdown="1"&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf&#10;&lt;/div&gt;</code></pre></td>
      <td><pre><code>&lt;div markdown="1"&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;&lt;/div&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

Opening an inline HTML element will not trigger the raw HTML block state unless the opening and closing tags stand alone
on a single line. This is explained in [Section 4.6](https://spec.commonmark.org/0.30#html-blocks), type 7:

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>&lt;span&gt;asdf &#42;asdf&#42;&lt;/span&gt; asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;p&gt;&lt;span&gt;asdf &lt;em&gt;asdf&lt;/em&gt;&lt;/span&gt; asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;span&gt;&#10;asdf &#42;asdf&#42;&#10;&lt;/span&gt;&#10;asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;span&gt;&#10;asdf &#42;asdf&#42;&#10;&lt;/span&gt;&#10;asdf &#42;asdf&#42; asdf</code></pre></td>
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
      <td><pre><code>&lt;h1&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf&#10;&#10;&lt;/h1&gt;</code></pre></td>
      <td><pre><code>&lt;h1&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;&lt;/h1&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;p&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf&#10;&#10;&lt;/p&gt;</code></pre></td>
      <td><pre><code>&lt;p&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;&lt;/p&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

### Soft Break

Soft breaks that are present in non-crucial parts such as in paragraphs and list items will be collapsed into space
characters:

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>asdf asdf asdf asdf&#10;asdf asdf asdf asdf&#10;&#10;asdf asdf asdf asdf</code></pre></td>
      <td><pre><code>&lt;p&gt;asdf asdf asdf asdf asdf asdf asdf asdf&lt;/p&gt;&lt;p&gt;asdf asdf asdf asdf&lt;/p&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

XSS
---

This converter is intended only to convert Markdown syntax to HTML based on the
[CommonMark](https://spec.commonmark.org/0.30) specification. It doesn’t care about your user input. I have no intention
of adding any special security features in the future, sorry. The attribute syntax feature may be a security risk for
you if you want to use this converter on your comment entries, for example:

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code class="language-markdown">![asdf](asdf.asdf) {onerror="alert('Yo!')"}</code></pre></td>
      <td><pre><code class="language-html">&lt;img alt="asdf" onerror="alert(&amp;apos;Yo!&amp;apos;)" src="asdf.asdf" /&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

There should be many specialized PHP applications already that have specific tasks to deal with XSS, so consider
post-processing the generated HTML markup before putting it out to the web:

 - [ezyang/htmlpurifier](https://github.com/ezyang/htmlpurifier)
 - [voku/anti-xss](https://github.com/voku/anti-xss)

Tests
-----

Clone this repository into the root of your web server that supports PHP and then you can open the `test.php` file with
your browser to see the result and the performance of this converter in various cases.