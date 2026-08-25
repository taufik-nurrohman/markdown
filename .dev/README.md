PHP Markdown Parser
===================

![from.php] ![to.php]

[from.php]: https://img.shields.io/github/size/taufik-nurrohman/markdown/from.php?branch=main&color=%234f5d95&label=from.php&labelColor=%231f2328&style=flat-square
[to.php]: https://img.shields.io/github/size/taufik-nurrohman/markdown/to.php?branch=main&color=%234f5d95&label=to.php&labelColor=%231f2328&style=flat-square

With 99% compliance to [CommonMark 0.31.2](https://spec.commonmark.org/0.31.2) specifications.

Motivation
----------

<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://github.com/taufik-nurrohman/markdown/assets/1669261/0a488f4a-0450-4e0a-8137-196a4b0657b0">
  <source media="(prefers-color-scheme: light)" srcset="https://github.com/taufik-nurrohman/markdown/assets/1669261/164b592d-e8db-4e28-be5d-467522f65f0d">
  <img alt="Why?" src="https://github.com/taufik-nurrohman/markdown/assets/1669261/164b592d-e8db-4e28-be5d-467522f65f0d">
</picture>

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
if I want to upgrade, it might be more optimal to just switch to CommonMark.

I’m not into things like that. As someone who needs a function to convert Markdown syntax to HTML, that kind of
flexibility is completely unnecessary to me. I just want to convert Markdown syntax to HTML for once and then move on.
It was fulfilled by [Parsedown version 1.8](https://github.com/erusev/parsedown/tree/1.8.x-beta), but it seems that it
is no longer being actively maintained.

The goal of this project is to use it in my [Markdown extension for Mecha](https://github.com/mecha-cms/x.markdown) in
the future. Previously, I wanted to develop this converter directly into the extension, but my friend advised me to
create this project separately as it might have potential to be used by other developers beyond the
[Mecha CMS](https://github.com/mecha-cms) developers.

### Ordered List

My parser supports list items numbered with Latin letters and Roman numerals. This satisfies the HTML5 specification for
the [`type` attribute of the `<ol>` element](https://html.spec.whatwg.org/multipage/grouping-content.html#attr-ol-type)
but does not satisfy the CommonMark specifications. Instead, it “extends” them.

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
        <pre><code>1) asdf asdf asdf asdf&#10;2) asdf asdf asdf asdf&#10;3) asdf asdf asdf asdf</code></pre>
      </td>
      <td rowspan="2">
        <pre><code>&lt;ol&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
      </td>
    </tr>
    <tr>
      <td>
        <pre><code>1. asdf asdf asdf asdf&#10;2. asdf asdf asdf asdf&#10;3. asdf asdf asdf asdf</code></pre>
      </td>
    </tr>
    <tr>
      <td>
        <pre><code>A) asdf asdf asdf asdf&#10;B) asdf asdf asdf asdf&#10;C) asdf asdf asdf asdf</code></pre>
      </td>
      <td rowspan="2">
        <pre><code>&lt;ol type="A"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
      </td>
    </tr>
    <tr>
      <td>
        <pre><code>A. asdf asdf asdf asdf&#10;B. asdf asdf asdf asdf&#10;C. asdf asdf asdf asdf</code></pre>
      </td>
    </tr>
    <tr>
      <td>
        <pre><code>a) asdf asdf asdf asdf&#10;b) asdf asdf asdf asdf&#10;c) asdf asdf asdf asdf</code></pre>
      </td>
      <td rowspan="2">
        <pre><code>&lt;ol type="a"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
      </td>
    </tr>
    <tr>
      <td>
        <pre><code>a. asdf asdf asdf asdf&#10;b. asdf asdf asdf asdf&#10;c. asdf asdf asdf asdf</code></pre>
      </td>
    </tr>
    <tr>
      <td>
        <pre><code>I) asdf asdf asdf asdf&#10;II) asdf asdf asdf asdf&#10;III) asdf asdf asdf asdf</code></pre>
      </td>
      <td rowspan="2">
        <pre><code>&lt;ol type="I"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
      </td>
    </tr>
    <tr>
      <td>
        <pre><code>I. asdf asdf asdf asdf&#10;II. asdf asdf asdf asdf&#10;III. asdf asdf asdf asdf</code></pre>
      </td>
    </tr>
    <tr>
      <td>
        <pre><code>i) asdf asdf asdf asdf&#10;ii) asdf asdf asdf asdf&#10;iii) asdf asdf asdf asdf</code></pre>
      </td>
      <td rowspan="2">
        <pre><code>&lt;ol type="i"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
      </td>
    </tr>
    <tr>
      <td>
        <pre><code>i. asdf asdf asdf asdf&#10;ii. asdf asdf asdf asdf&#10;iii. asdf asdf asdf asdf</code></pre>
      </td>
    </tr>
  </tbody>
</table>

Since the list block marker can now be any lower-case or upper-case characters and will also treat “I” and “i” character
as the start of a list block with Roman numerals, the rules for this type of list block have been strictly enforced:

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
            <pre><code>A) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;ol type="A"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
          </td>
        </tr>
        <tr>
          <td>
            <pre><code>B) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;p&gt;B) asdf asdf asdf asdf&lt;/p&gt;</code></pre>
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
            <pre><code>I) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;ol type="I"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
          </td>
        </tr>
        <tr>
          <td>
            <pre><code>II) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;p&gt;II) asdf asdf asdf asdf&lt;/p&gt;</code></pre>
          </td>
        </tr>
      </tbody>
    </table>

 1. A **type “i”** list block can only start with the prefix `i) ` or `i. `.

Those list types don’t support custom `start` attribute (they will always start from 1), and like the `1) ` and `1. `
prefixes, they can interrupt the paragraph.

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
        <pre><code>asdf asdf asdf asdf&#10;A) asdf asdf asdf asdf</code></pre>
      </td>
      <td>
        <pre><code>&lt;p&gt;asdf asdf asdf asdf&lt;/p&gt;&#10;&lt;ol type="A"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
      </td>
    </tr>
    <tr>
      <td>
        <pre><code>asdf asdf asdf asdf&#10;B) asdf asdf asdf asdf</code></pre>
      </td>
      <td>
        <pre><code>&lt;p&gt;asdf asdf asdf asdf B) asdf asdf asdf asdf&lt;/p&gt;</code></pre>
      </td>
    </tr>
  </tbody>
</table>

 1. A list item continuation of **type “A”** list block can only use the previous character or the next character after
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
            <pre><code>A) asdf asdf asdf asdf&#10;B) asdf asdf asdf asdf&#10;C) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;ol type="A"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
          </td>
        </tr>
        <tr>
          <td>
            <pre><code>A) asdf asdf asdf asdf&#10;A) asdf asdf asdf asdf&#10;A) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;ol type="A"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
          </td>
        </tr>
        <tr>
          <td>
            <pre><code>B) asdf asdf asdf asdf&#10;B) asdf asdf asdf asdf&#10;B) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;p&gt;B) asdf asdf asdf asdf B) asdf asdf asdf asdf B) asdf asdf asdf asdf&lt;/p&gt;</code></pre>
          </td>
        </tr>
        <tr>
          <td>
            <pre><code>A) asdf asdf asdf asdf&#10;B) asdf asdf asdf asdf&#10;B) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;ol type="A"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
          </td>
        </tr>
        <tr>
          <td>
            <pre><code>A) asdf asdf asdf asdf&#10;C) asdf asdf asdf asdf&#10;D) asdf asdf asdf asdf&#10;E) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;ol type="A"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf C) asdf asdf asdf asdf D) asdf asdf asdf asdf E) asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
          </td>
        </tr>
        <tr>
          <td>
            <pre><code>A) asdf asdf asdf asdf&#10;C) asdf asdf asdf asdf&#10;B) asdf asdf asdf asdf&#10;C) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;ol type="A"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf C) asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
          </td>
        </tr>
        <tr>
          <td>
            <pre><code>A) asdf asdf asdf asdf&#10;C) asdf asdf asdf asdf&#10;A) asdf asdf asdf asdf&#10;A) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;ol type="A"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf C) asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
          </td>
        </tr>
      </tbody>
    </table>

 1. A list item continuation of **type “a”** list block can only use the previous character or the next character after
    it. After the character “z”, the list continues with “aa”, “ab”, “ac”, and so on.
 1. A list Item continuation of **type “I”** list block can only use the previous Roman numeral or the next Roman
    numeral after it.

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
            <pre><code>I) asdf asdf asdf asdf&#10;II) asdf asdf asdf asdf&#10;III) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;ol type="I"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
          </td>
        </tr>
        <tr>
          <td>
            <pre><code>I) asdf asdf asdf asdf&#10;I) asdf asdf asdf asdf&#10;I) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;ol type="I"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
          </td>
        </tr>
        <tr>
          <td>
            <pre><code>II) asdf asdf asdf asdf&#10;II) asdf asdf asdf asdf&#10;II) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;p&gt;II) asdf asdf asdf asdf II) asdf asdf asdf asdf II) asdf asdf asdf asdf&lt;/p&gt;</code></pre>
          </td>
        </tr>
        <tr>
          <td>
            <pre><code>I) asdf asdf asdf asdf&#10;II) asdf asdf asdf asdf&#10;II) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;ol type="I"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
          </td>
        </tr>
        <tr>
          <td>
            <pre><code>I) asdf asdf asdf asdf&#10;III) asdf asdf asdf asdf&#10;IV) asdf asdf asdf asdf</code></pre>
          </td>
          <td>
            <pre><code>&lt;ol type="I"&gt;&#10;  &lt;li&gt;asdf asdf asdf asdf III) asdf asdf asdf asdf IV) asdf asdf asdf asdf&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
          </td>
        </tr>
      </tbody>
    </table>

 1. A list Item continuation of **type “i”** list block can only use the previous Roman numeral or the next Roman
    numeral after it.

Those rules should be effective to avoid results that most people likely don’t want. This typically occurs when a
paragraph continuation text contains a sequence of letters that end with a `) ` or a `. `.

Consider the following examples:

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

Without stricter rules for the list block, my parser would mistakenly treat `asdf) ` and `asdf. ` as the start of
another list item block.

Also consider the following example, which is taken from [this discussion](https://talk.commonmark.org/t/bad-interaction-between-laziness-rule-and-ordered-lists/9085?u=taufik-nurrohman):

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
        <pre><code>&lt;ol&gt;&#10;&lt;li&gt;Before the end of the paragraph, I invite you to&#10;consider a very large number. For example,&lt;/li&gt;&#10;&lt;li&gt;Are you thinking about it?&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
      </td>
      <td>
        <pre><code>&lt;ol&gt;&#10;  &lt;li&gt;Before the end of the paragraph, I invite you to consider a very large number. For example, 45000000. Are you thinking about it?&lt;/li&gt;&#10;&lt;/ol&gt;</code></pre>
      </td>
    </tr>
  </tbody>
</table>

Please note that this is just my suggestion for how CommonMark could improve its implementation. I would still recommend
you to write the list item numbers in order for best compatibility with other Markdown parsers in case you want to
switch in the future. Or, if you’re too lazy or the list tends to grow over time, just reuse number from the previous
list item.

### Tabs

Unlike CommonMark, this converter does not preserve tabs. This is probably the hardest part. Once it’s solved, the
parser will be 100% compliant with the CommonMark specifications. However, I am not currently an expert in this area.
Initially, it does seem possible. I was able to preserve the tab characters, but it turns out that this only works for
top-level blocks. Once I enter a container block to parse its content, I then lose track of the correct column position
because the container block’s markers have been removed.

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