<?php

namespace Packages\ToxicityFilter\Contracts;

use Packages\ToxicityFilter\ValueObjects\ToxicityResult;

interface ToxicityFilterInterface
{
    /**
     * Analyze content for toxicity using the configured provider.
     *
     * @param string $content
     * @param string|null $provider
     * @param array $options
     * @return ToxicityResult
     */
    public function analyze(string $content, ?string $provider = null, array $options = []): ToxicityResult;

    /**
     * Check if content should be blocked based on toxicity score.
     *
     * @param string $content
     * @param string|null $provider
     * @return bool
     */
    public function shouldBlock(string $content, ?string $provider = null): bool;

    /**
     * Check if content should be flagged for review.
     *
     * @param string $content
     * @param string|null $provider
     * @return bool
     */
    public function shouldFlag(string $content, ?string $provider = null): bool;

    /**
     * Check if content should trigger a warning.
     *
     * @param string $content
     * @param string|null $provider
     * @return bool
     */
    public function shouldWarn(string $content, ?string $provider = null): bool;

    /**
     * Get all available providers.
     *
     * @return array
     */
    public function getAvailableProviders(): array;

    /**
     * Set the default provider.
     *
     * @param string $provider
     * @return void
     */
    public function setDefaultProvider(string $provider): void;
}
