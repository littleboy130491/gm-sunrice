<?php

declare(strict_types=1);

namespace App\StatamicImport;

/**
 * Converts Statamic Bard (ProseMirror) documents to the HTML that
 * Sunrice rich_text fields store.
 */
final class BardToHtml
{
    /** @var callable(string): array{id: int|null, url: string} */
    private mixed $resolveAsset;

    /**
     * @param  callable(string): array{id: int|null, url: string}  $resolveAsset
     */
    public function __construct(callable $resolveAsset)
    {
        $this->resolveAsset = $resolveAsset;
    }

    /**
     * @param  mixed  $doc  a node list or a {type: 'doc', content: [...]} object
     */
    public function toHtml(mixed $doc): string
    {
        if (! is_array($doc)) {
            return '';
        }
        if (isset($doc['type']) && $doc['type'] === 'doc') {
            $doc = $doc['content'] ?? [];
        }
        // Some bard values are stored wrapped one level deep.
        if (isset($doc['content']) && ! isset($doc['type'])) {
            $doc = $doc['content'];
        }
        if (! is_array($doc)) {
            return '';
        }

        return implode('', array_map(fn ($node) => $this->node($node), $doc));
    }

    /** @param mixed $node */
    private function node(mixed $node): string
    {
        if (! is_array($node)) {
            return '';
        }
        $type = (string) ($node['type'] ?? '');
        $content = implode('', array_map(
            fn ($child) => $this->node($child),
            (array) ($node['content'] ?? [])
        ));

        return match ($type) {
            'doc' => $content,
            'paragraph' => '<p>'.$content.'</p>',
            'heading' => $this->heading($node, $content),
            'text' => $this->text($node),
            'hardBreak', 'hard_break' => '<br>',
            'horizontalRule', 'horizontal_rule', 'hr' => '<hr>',
            'blockquote' => '<blockquote>'.$content.'</blockquote>',
            'bulletList', 'bullet_list' => '<ul>'.$content.'</ul>',
            'orderedList', 'ordered_list' => '<ol>'.$content.'</ol>',
            'listItem', 'list_item' => '<li>'.$content.'</li>',
            'codeBlock', 'code_block' => '<pre><code>'.$this->plainText($node).'</code></pre>',
            'table' => '<table>'.$content.'</table>',
            'tableRow', 'table_row' => '<tr>'.$content.'</tr>',
            'tableHeader', 'table_header' => '<th>'.$content.'</th>',
            'tableCell', 'table_cell' => '<td>'.$content.'</td>',
            'image' => $this->image($node),
            'set' => '<!-- bard set dropped -->',
            default => $content !== ''
                ? $content
                : '<!-- unknown bard node: '.e($type).' -->',
        };
    }

    /** @param array<string, mixed> $node */
    private function heading(array $node, string $content): string
    {
        $level = (int) ($node['attrs']['level'] ?? 2);
        $level = max(1, min(6, $level));

        return '<h'.$level.'>'.$content.'</h'.$level.'>';
    }

    /** @param array<string, mixed> $node */
    private function text(array $node): string
    {
        $text = e((string) ($node['text'] ?? ''));
        foreach (array_reverse((array) ($node['marks'] ?? [])) as $mark) {
            if (! is_array($mark)) {
                continue;
            }
            $attrs = (array) ($mark['attrs'] ?? []);
            $text = match ((string) ($mark['type'] ?? '')) {
                'bold' => '<strong>'.$text.'</strong>',
                'italic' => '<em>'.$text.'</em>',
                'strike' => '<s>'.$text.'</s>',
                'underline' => '<u>'.$text.'</u>',
                'code' => '<code>'.$text.'</code>',
                'subscript' => '<sub>'.$text.'</sub>',
                'superscript' => '<sup>'.$text.'</sup>',
                'link' => $this->link($attrs, $text),
                default => $text,
            };
        }

        return $text;
    }

    /** @param array<string, mixed> $attrs */
    private function link(array $attrs, string $inner): string
    {
        $href = $this->safeUrl((string) ($attrs['href'] ?? ''));
        $out = '<a href="'.$href.'"';
        $target = $attrs['target'] ?? null;
        if (is_string($target) && $target !== '') {
            $out .= ' target="'.e($target).'"';
            if ($target === '_blank') {
                $out .= ' rel="noopener"';
            }
        }
        if (isset($attrs['title']) && is_string($attrs['title']) && $attrs['title'] !== '') {
            $out .= ' title="'.e($attrs['title']).'"';
        }

        return $out.'>'.$inner.'</a>';
    }

    /** @param array<string, mixed> $node */
    private function image(array $node): string
    {
        $attrs = (array) ($node['attrs'] ?? []);
        $src = (string) ($attrs['src'] ?? '');
        $resolved = ($this->resolveAsset)($src);

        $out = '<img src="'.e($resolved['url']).'"';
        if (isset($attrs['alt']) && is_string($attrs['alt'])) {
            $out .= ' alt="'.e($attrs['alt']).'"';
        }
        if (isset($attrs['title']) && is_string($attrs['title'])) {
            $out .= ' title="'.e($attrs['title']).'"';
        }
        if ($resolved['id'] !== null) {
            $out .= ' data-asset-id="'.$resolved['id'].'"';
        }

        return $out.'>';
    }

    /** @param array<string, mixed> $node */
    private function plainText(array $node): string
    {
        $out = '';
        foreach ((array) ($node['content'] ?? []) as $child) {
            if (is_array($child) && ($child['type'] ?? '') === 'text') {
                $out .= e((string) ($child['text'] ?? ''));
            }
        }

        return $out;
    }

    private function safeUrl(string $url): string
    {
        if ($url === '') {
            return '#';
        }
        // Allow http(s), relative paths, anchors, mailto:, tel:.
        if (preg_match('~^(https?://|/|\#|mailto:|tel:)~i', $url)) {
            return e($url);
        }

        return '#';
    }
}
