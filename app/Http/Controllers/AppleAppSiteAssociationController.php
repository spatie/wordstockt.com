<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class AppleAppSiteAssociationController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'webcredentials' => [
                'apps' => ['97KRXCRMAY.com.wordstockt.app'],
            ],
            'applinks' => [
                'apps' => [],
                'details' => [
                    [
                        'appID' => '97KRXCRMAY.com.wordstockt.app',
                        'paths' => ['/invite/*'],
                    ],
                ],
            ],
        ]);
    }
}
