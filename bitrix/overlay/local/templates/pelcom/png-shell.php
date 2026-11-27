<?php

function pelcomPngComponent(string $html, bool $isAbout): string
{
    $html = str_replace(['href="#top"', 'href="#engineering"'], ['href="/"', 'href="/#engineering"'], $html);
    if (!$isAbout) {
        $html = str_replace('href="#group"', 'href="/#group"', $html);
    }
    return preg_replace_callback(
        '~\bclass="([^"]+)"~',
        static fn ($match) => 'class="png-'.str_replace(' ', ' png-', $match[1]).'"',
        $html
    );
}

function pelcomPngHeader(string $page, string $original): string
{
    $id = preg_match('~<header\b[^>]*\bid=(["\']?)(hd|hdr)\1(?=[\s>])~i', $original, $match) ? $match[2] : 'top';
    $header = file_get_contents(__DIR__.'/parts/pelcom-landing-header.php');
    $header = str_replace('id="top"', 'id="'.$id.'"', $header);
    $active = match (true) {
        $page === 'products', $page === 'catalog', preg_match('~^/catalog/~', $_SERVER['REQUEST_URI'] ?? '') === 1 => '/catalog/',
        $page === 'solutions', str_starts_with($page, 'pelcom-solution-') => '/solutions/',
        $page === 'software', str_starts_with($page, 'pelcom-software-') => '/software/',
        $page === 'pelcom-about' => '/about/#group',
        $page === 'pelcom-service' => '/service/',
        $page === 'contacts' => '/contacts/',
        default => null,
    };
    if ($active !== null) {
        $header = preg_replace('~(\bhref="'.preg_quote($active, '~').'")~', '$1 aria-current="location"', $header, 1);
    }
    return pelcomPngComponent($header, $page === 'pelcom-about');
}

function pelcomPngFooter(string $page): string
{
    $parts = __DIR__.'/parts/';
    return pelcomPngComponent(
        file_get_contents($parts.'pelcom-landing-footer.php'),
        $page === 'pelcom-about'
    );
}
