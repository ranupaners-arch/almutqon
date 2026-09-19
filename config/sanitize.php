<?php
// config/sanitize.php — sanitasi HTML konten artikel (whitelist tag & atribut)

const ALLOWED_TAGS = [
    'p', 'br', 'hr', 'div', 'span',
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'ins', 'mark', 'small', 'sub', 'sup', 'q',
    'ul', 'ol', 'li', 'blockquote', 'pre', 'code',
    'a', 'img',
    'table', 'thead', 'tbody', 'tr', 'th', 'td',
];

const ALLOWED_CSS = [
    'color', 'background-color', 'text-align', 'font-weight',
    'font-style', 'text-decoration', 'font-size',
];

function safe_url(string $url): bool
{
    $url = trim($url);
    if ($url === '') return false;

    // Anchor dalam halaman
    if ($url[0] === '#') return true;

    // Path protokol-relatif (//host) diblokir
    if (strpos($url, '//') === 0) return false;

    // Skema eksplisit hanya boleh http/https/mailto/tel
    if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url)) {
        $scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?? ''));
        if (!in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return false;
        }
    }

    return true;
}

function safe_style(string $style): string
{
    $safe = [];
    foreach (explode(';', $style) as $decl) {
        $parts = array_map('trim', explode(':', $decl, 2));
        if (count($parts) !== 2) continue;

        $prop = strtolower($parts[0]);
        $val = trim($parts[1]);

        if (!in_array($prop, ALLOWED_CSS, true)) continue;
        if (preg_match('#(expression|\bjavascript\b|url\s*\()#i', $val)) continue;

        $safe[] = $prop . ': ' . $val;
    }
    return implode('; ', $safe);
}

function clean_node(DOMNode $node): void
{
    if ($node->nodeType === XML_ELEMENT_NODE) {
        $tag = strtolower($node->nodeName);

        if (!in_array($tag, ALLOWED_TAGS, true)) {
            // Tag tidak diizinkan: buang tag tapi pelihara isinya (unwrap)
            $parent = $node->parentNode;
            if (!$parent) return;

            $moved = [];
            while ($node->firstChild) {
                $child = $node->firstChild;
                $parent->insertBefore($child, $node);
                $moved[] = $child;
            }
            $parent->removeChild($node);

            foreach ($moved as $child) clean_node($child);
            return;
        }

        // Sanitasi atribut
        $attr_names = [];
        foreach ($node->attributes as $attr) {
            $attr_names[] = $attr->name;
        }

        foreach ($attr_names as $attr_name) {
            $aname = strtolower($attr_name);
            $value = (string) $node->getAttribute($aname);

            if ($aname === 'style') {
                $node->removeAttribute($aname);
                $clean = safe_style($value);
                if ($clean !== '') $node->setAttribute('style', $clean);
                continue;
            }

            if ($aname === 'href' || $aname === 'src') {
                if ($value !== '' && safe_url($value)) continue;
                $node->removeAttribute($aname);
                continue;
            }

            if (in_array($aname, ['class', 'dir', 'title', 'alt', 'width', 'height', 'colspan', 'rowspan', 'start'], true)) {
                continue;
            }

            if ($aname === 'rel' && $tag === 'a' && strtolower($value) === 'noopener') {
                continue;
            }

            if ($aname === 'target' && $tag === 'a' && strtolower($value) === '_blank') {
                continue;
            }

            $node->removeAttribute($aname);
        }
    }

    foreach (iterator_to_array($node->childNodes) as $child) {
        clean_node($child);
    }
}

function sanitize_html(string $html): string
{
    if ($html === '') return '';

    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);

    $dom->loadHTML(
        '<?xml encoding="UTF-8"><!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
        LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
    );
    libxml_clear_errors();

    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body) return '';

    foreach (iterator_to_array($body->childNodes) as $child) {
        clean_node($child);
    }

    $out = '';
    foreach ($body->childNodes as $child) {
        $out .= $dom->saveHTML($child);
    }

    return trim($out);
}