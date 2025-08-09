<?php

namespace Packages\ToxicityFilter\Services\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Packages\ToxicityFilter\Contracts\ToxicityProviderInterface;
use Packages\ToxicityFilter\Exceptions\ContentTooLongException;
use Packages\ToxicityFilter\Exceptions\ProviderApiException;
use Packages\ToxicityFilter\ValueObjects\ToxicityResult;

class PerspectiveProvider implements ToxicityProviderInterface
{
    private Client $client;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'timeout' => $config['timeout'] ?? 30,
        ]);
    }

    public function analyze(string $content, array $options = []): ToxicityResult
    {
        $this->validateContent($content);

        try {
            $requestData = [
                'comment' => ['text' => $content],
                'requestedAttributes' => [],
                'languages' => ['en'],
            ];

            // Add requested attributes
            foreach ($this->config['attributes'] as $attribute) {
                $requestData['requestedAttributes'][$attribute] = [];
            }

            $response = $this->client->post($this->config['endpoint'], [
                'query' => ['key' => $this->config['api_key']],
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $requestData,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return $this->parseResponse($data, $content);
        } catch (RequestException $e) {
            throw new ProviderApiException('Perspective API', $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getName(): string
    {
        return 'perspective';
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['api_key']) && !empty($this->config['endpoint']);
    }

    public function getMaxContentLength(): ?int
    {
        return 3000; // Perspective API limit
    }

    private function validateContent(string $content): void
    {
        $maxLength = $this->getMaxContentLength();
        if ($maxLength && strlen($content) > $maxLength) {
            throw new ContentTooLongException($maxLength, strlen($content));
        }
    }

    private function parseResponse(array $data, string $content): ToxicityResult
    {
        $attributeScores = $data['attributeScores'] ?? [];
        
        // Get the highest toxicity score among all attributes
        $toxicityScore = 0.0;
        $detectedCategories = [];
        $scores = [];
        
        foreach ($attributeScores as $attribute => $scoreData) {
            $score = $scoreData['summaryScore']['value'] ?? 0.0;
            $scores[$attribute] = $score;
            
            if ($score > $toxicityScore) {
                $toxicityScore = $score;
            }
            
            // Consider it detected if score is above 0.5
            if ($score > 0.5) {
                $detectedCategories[] = strtolower(str_replace('_', ' ', $attribute));
            }
        }

        return new ToxicityResult(
            toxicityScore: $toxicityScore,
            categories: $detectedCategories,
            provider: $this->getName(),
            explanation: null,
            metadata: [
                'attribute_scores' => $scores,
                'detailed_scores' => $attributeScores,
                'languages' => $data['languages'] ?? ['en'],
            ]
        );
    }
}
