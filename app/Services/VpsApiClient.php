<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VpsApiClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $projectKey;

    public function __construct()
    {
        $this->baseUrl = config('services.transcoding.url');
        $this->apiKey = config('services.transcoding.api_key');
        $this->projectKey = config('services.transcoding.project_key');
    }

    /**
     * Send transcoding request to VPS
     */
    public function sendTranscodeRequest(array $data): array
    {
        $url = "{$this->baseUrl}/api/transcode";
        
        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->post($url, $data);

            if (!$response->successful()) {
                throw new \Exception("VPS request failed with status {$response->status()}: " . $response->body());
            }

            return $response->json();
            
        } catch (\Exception $e) {
            Log::error("VPS request failed: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Download transcoded file from VPS
     */
    public function downloadFile(string $url, string $savePath): bool
    {
        // Create directory if needed
        $directory = dirname($savePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $resource = fopen($savePath, 'wb');

        $response = Http::timeout(3600)
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->withOptions(['sink' => $resource])
            ->get($url);

        fclose($resource);

        return $response->successful();
    }

    public function getProjectKey(): string
    {
        return $this->projectKey;
    }
}
