<?php

namespace Packages\ToxicityFilter\ValueObjects;

class ToxicityResult
{
    public function __construct(
        private float $toxicityScore,
        private array $categories = [],
        private string $provider = '',
        private ?string $explanation = null,
        private array $metadata = []
    ) {}

    public function getToxicityScore(): float
    {
        return $this->toxicityScore;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getExplanation(): ?string
    {
        return $this->explanation;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isToxic(float $threshold = 0.5): bool
    {
        return $this->toxicityScore >= $threshold;
    }

    public function shouldBlock(float $threshold): bool
    {
        return $this->toxicityScore >= $threshold;
    }

    public function shouldFlag(float $threshold): bool
    {
        return $this->toxicityScore >= $threshold;
    }

    public function shouldWarn(float $threshold): bool
    {
        return $this->toxicityScore >= $threshold;
    }

    public function toArray(): array
    {
        return [
            'toxicity_score' => $this->toxicityScore,
            'categories' => $this->categories,
            'provider' => $this->provider,
            'explanation' => $this->explanation,
            'metadata' => $this->metadata,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
