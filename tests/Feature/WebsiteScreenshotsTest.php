<?php

use Dom\HTMLDocument;

it('shows the supported Apple devices with loadable portrait website screenshots', function (): void {
    $response = $this->get('/')
        ->assertSuccessful()
        ->assertSee('WordStockt on iPad')
        ->assertSee('WordStockt on iPhone Duo in portrait')
        ->assertSee('WordStockt game board on iPhone');

    $document = HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);

    foreach ($document->getElementsByTagName('img') as $image) {
        $source = $image->getAttribute('src');

        if (! str_starts_with($source, '/screenshots/')) {
            continue;
        }

        expect(is_file(public_path($source)))->toBeTrue("Missing screenshot: {$source}");
        $dimensions = getimagesize(public_path($source));

        expect($dimensions)->not->toBeFalse();
        expect($dimensions[1])->toBeGreaterThan($dimensions[0], "Screenshot must be portrait: {$source}");
    }
});
