<?php

it('advertises a sitemap containing only the public information pages', function (): void {
    $sitemap = simplexml_load_file(public_path('sitemap.xml'));
    $sitemap->registerXPathNamespace('sitemap', 'http://www.sitemaps.org/schemas/sitemap/0.9');

    $urls = collect($sitemap->xpath('/sitemap:urlset/sitemap:url/sitemap:loc'))
        ->map(fn (SimpleXMLElement $location): string => (string) $location)
        ->all();

    expect($urls)->toBe([
        'https://wordstockt.com/',
        'https://wordstockt.com/privacy',
        'https://wordstockt.com/support',
        'https://wordstockt.com/delete-account',
    ]);

    foreach ($urls as $url) {
        $this->get($url)
            ->assertSuccessful()
            ->assertSee('<link rel="canonical" href="'.rtrim($url, '/').'">', escape: false);
    }

    expect(file_get_contents(public_path('robots.txt')))
        ->toContain('Sitemap: https://wordstockt.com/sitemap.xml');
});

it('publishes the IndexNow ownership key at the host root', function (): void {
    $key = 'a2485a4c7761ba8cb8480fee635e1ea0';

    expect(trim(file_get_contents(public_path("{$key}.txt"))))->toBe($key);
});
