<?php

namespace Packages\ToxicityFilter\Services\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Packages\ToxicityFilter\Contracts\ToxicityProviderInterface;
use Packages\ToxicityFilter\Exceptions\ContentTooLongException;
use Packages\ToxicityFilter\Exceptions\ProviderApiException;
use Packages\ToxicityFilter\ValueObjects\ToxicityResult;

class OpenAIProvider implements ToxicityProviderInterface
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
            $response = $this->client->post($this->config['endpoint'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'input' => $content,
                    'model' => $this->config['model'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return $this->parseResponse($data, $content);
        } catch (RequestException $e) {
            throw new ProviderApiException('OpenAI', $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getName(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['api_key']) && !empty($this->config['endpoint']);
    }

    public function getMaxContentLength(): ?int
    {
        return 32000; // OpenAI's approximate token limit
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
        $result = $data['results'][0] ?? [];
        $categories = $result['categories'] ?? [];
        $categoryScores = $result['category_scores'] ?? [];
        
        // Calculate overall toxicity score (max of all category scores)
        $toxicityScore = 0.0;
        $detectedCategories = [];
        
        foreach ($categoryScores as $category => $score) {
            if ($score > $toxicityScore) {
                $toxicityScore = $score;
            }
            
            if ($categories[$category] ?? false) {
                $detectedCategories[] = $category;
            }
        }

        return new ToxicityResult(
            toxicityScore: $toxicityScore,
            categories: $detectedCategories,
            provider: $this->getName(),
            explanation: null,
            metadata: [
                'category_scores' => $categoryScores,
                'flagged' => $result['flagged'] ?? false,
                'model' => $this->config['model'],
            ]
        );
    }
}
