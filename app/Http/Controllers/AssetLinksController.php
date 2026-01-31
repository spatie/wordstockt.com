<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class AssetLinksController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            [
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => 'com.wordstockt.app',
                    'sha256_cert_fingerprints' => [
                        // Add your SHA256 fingerprint here after building the app
                    ],
                ],
            ],
        ]);
    }
}
