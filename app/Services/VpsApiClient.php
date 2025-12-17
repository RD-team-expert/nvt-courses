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
        Log::info('=== VpsApiClient::sendTranscodeRequest CALLED ===', [
            'base_url' => $this->baseUrl,
            'api_key_set' => !empty($this->apiKey),
            'project_key' => $this->projectKey,
            'request_data' => $data,
        ]);
        
        $url = "{$this->baseUrl}/api/transcode";
        
        Log::info('Sending HTTP POST request:', [
            'url' => $url,
            'headers' => [
                'X-API-Key' => substr($this->apiKey, 0, 10) . '...',
                'Accept' => 'application/json',
            ],
        ]);
        
        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->post($url, $data);
            
            Log::info('VPS HTTP response:', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                throw new \Exception("VPS request failed with status {$response->status()}: " . $response->body());
            }

            return $response->json();
            
        } catch (\Exception $e) {
            Log::error('VPS request exception:', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
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
