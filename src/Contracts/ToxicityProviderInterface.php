<?php

namespace Packages\ToxicityFilter\Contracts;

use Packages\ToxicityFilter\ValueObjects\ToxicityResult;

interface ToxicityProviderInterface
{
    /**
     * Analyze content for toxicity.
     *
     * @param string $content The content to analyze
     * @param array $options Additional options for the provider
     * @return ToxicityResult The toxicity analysis result
     */
    public function analyze(string $content, array $options = []): ToxicityResult;

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if the provider is properly configured.
     *
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * Get the maximum content length supported by this provider.
     *
     * @return int|null
     */
    public function getMaxContentLength(): ?int;
}
