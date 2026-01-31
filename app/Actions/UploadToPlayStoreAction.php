<?php

namespace App\Actions;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class UploadToPlayStoreAction
{
    protected string $packageName = 'com.wordstockt.app';

    protected string $baseUrl = 'https://androidpublisher.googleapis.com/androidpublisher/v3/applications';

    protected ?string $accessToken = null;

    public function execute(string $aabPath, string $serviceAccountKeyPath, string $track = 'internal'): array
    {
        if (! file_exists($aabPath)) {
            throw new RuntimeException("AAB file not found: {$aabPath}");
        }

        if (! file_exists($serviceAccountKeyPath)) {
            throw new RuntimeException("Service account key not found: {$serviceAccountKeyPath}");
        }

        $this->authenticate($serviceAccountKeyPath);

        $editId = $this->createEdit();

        $versionCode = $this->uploadBundle($editId, $aabPath);

        $this->assignToTrack($editId, $track, $versionCode);

        $this->commitEdit($editId);

        return [
            'success' => true,
            'version_code' => $versionCode,
            'track' => $track,
        ];
    }

    protected function authenticate(string $serviceAccountKeyPath): void
    {
        $credentials = json_decode(file_get_contents($serviceAccountKeyPath), true);

        $jwt = $this->createJwt($credentials);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("Authentication failed: {$response->body()}");
        }

        $this->accessToken = $response->json('access_token');
    }

    protected function createJwt(array $credentials): string
    {
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $now = time();
        $payload = [
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/androidpublisher',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signatureInput = "{$headerEncoded}.{$payloadEncoded}";

        openssl_sign($signatureInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

        $signatureEncoded = $this->base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function createEdit(): string
    {
        $response = $this->api()->post("{$this->baseUrl}/{$this->packageName}/edits");

        if (! $response->successful()) {
            throw new RuntimeException("Failed to create edit: {$response->body()}");
        }

        return $response->json('id');
    }

    protected function uploadBundle(string $editId, string $aabPath): int
    {
        $response = Http::withToken($this->accessToken)
            ->timeout(600)
            ->withHeaders([
                'Content-Type' => 'application/octet-stream',
            ])
            ->withBody(file_get_contents($aabPath), 'application/octet-stream')
            ->post("https://androidpublisher.googleapis.com/upload/androidpublisher/v3/applications/{$this->packageName}/edits/{$editId}/bundles?uploadType=media");

        if (! $response->successful()) {
            throw new RuntimeException("Failed to upload bundle: {$response->body()}");
        }

        return $response->json('versionCode');
    }

    protected function assignToTrack(string $editId, string $track, int $versionCode): void
    {
        $response = $this->api()->put(
            "{$this->baseUrl}/{$this->packageName}/edits/{$editId}/tracks/{$track}",
            [
                'track' => $track,
                'releases' => [
                    [
                        'versionCodes' => [$versionCode],
                        'status' => 'completed',
                    ],
                ],
            ]
        );

        if (! $response->successful()) {
            throw new RuntimeException("Failed to assign to track: {$response->body()}");
        }
    }

    protected function commitEdit(string $editId): void
    {
        $response = $this->api()->post("{$this->baseUrl}/{$this->packageName}/edits/{$editId}:commit");

        if (! $response->successful()) {
            throw new RuntimeException("Failed to commit edit: {$response->body()}");
        }
    }

    protected function api(): PendingRequest
    {
        return Http::withToken($this->accessToken)->acceptJson();
    }
}
