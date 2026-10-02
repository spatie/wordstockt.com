<?php

use Illuminate\Support\Facades\Storage;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

it('can build an s3 disk for Laravel Cloud object storage', function (): void {
    $disk = Storage::build([
        'driver' => 's3',
        'key' => 'key',
        'secret' => 'secret',
        'bucket' => 'bucket',
        'region' => 'auto',
        'endpoint' => 'https://example.r2.cloudflarestorage.com',
    ]);

    expect($disk->getAdapter())->toBeInstanceOf(AwsS3V3Adapter::class);
});
