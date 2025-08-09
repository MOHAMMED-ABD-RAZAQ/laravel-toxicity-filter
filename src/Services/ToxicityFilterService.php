<?php

namespace Packages\ToxicityFilter\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Packages\ToxicityFilter\Contracts\ToxicityFilterInterface;
use Packages\ToxicityFilter\Contracts\ToxicityProviderInterface;
use Packages\ToxicityFilter\Exceptions\ProviderNotFoundException;
use Packages\ToxicityFilter\Services\Providers\OpenAIProvider;
use Packages\ToxicityFilter\Services\Providers\PerspectiveProvider;
use Packages\ToxicityFilter\ValueObjects\ToxicityResult;

class ToxicityFilterService implements ToxicityFilterInterface
{
    private array $providers = [];
    private string $defaultProvider;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->defaultProvider = $config['default'] ?? 'openai';
        $this->initializeProviders();
    }

    public function analyze(string $content, ?string $provider = null, array $options = []): ToxicityResult
    {
        $provider = $provider ?: $this->defaultProvider;
        
        // Check cache first
        if ($this->config['cache']['enabled'] ?? false) {
            try {
                $cacheKey = $this->getCacheKey($content, $provider);
                $cacheStore = $this->config['cache']['store'] ?? null;
                
                // Use default cache if store is not specified or doesn't exist
                $cache = $cacheStore ? Cache::store($cacheStore) : Cache::store();
                $cachedResult = $cache->get($cacheKey);
                    
                if ($cachedResult) {
                    return unserialize($cachedResult);
                }
            } catch (\Exception $e) {
                // If cache fails, continue without caching
                Log::warning('Cache operation failed in toxicity filter', ['error' => $e->getMessage()]);
            }
        }

        $providerInstance = $this->getProvider($provider);
        $result = $providerInstance->analyze($content, $options);

        // Store in cache
        if ($this->config['cache']['enabled'] ?? false) {
            try {
                $cacheStore = $this->config['cache']['store'] ?? null;
                $cache = $cacheStore ? Cache::store($cacheStore) : Cache::store();
                $cache->put($cacheKey, serialize($result), $this->config['cache']['ttl'] ?? 3600);
            } catch (\Exception $e) {
                // If cache fails, continue without caching
                Log::warning('Cache storage failed in toxicity filter', ['error' => $e->getMessage()]);
            }
        }

        // Log the result if enabled
        if ($this->config['logging']['enabled'] ?? false) {
            $this->logResult($content, $result);
        }

        return $result;
    }

    public function shouldBlock(string $content, ?string $provider = null): bool
    {
        $result = $this->analyze($content, $provider);
        $threshold = $this->config['thresholds']['block'] ?? 0.8;
        
        return $result->shouldBlock($threshold);
    }

    public function shouldFlag(string $content, ?string $provider = null): bool
    {
        $result = $this->analyze($content, $provider);
        $threshold = $this->config['thresholds']['flag'] ?? 0.6;
        
        return $result->shouldFlag($threshold);
    }

    public function shouldWarn(string $content, ?string $provider = null): bool
    {
        $result = $this->analyze($content, $provider);
        $threshold = $this->config['thresholds']['warn'] ?? 0.4;
        
        return $result->shouldWarn($threshold);
    }

    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }

    public function setDefaultProvider(string $provider): void
    {
        if (!isset($this->providers[$provider])) {
            throw new ProviderNotFoundException($provider);
        }
        
        $this->defaultProvider = $provider;
    }

    public function getProvider(string $name): ToxicityProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new ProviderNotFoundException($name);
        }

        return $this->providers[$name];
    }

    private function initializeProviders(): void
    {
        $providersConfig = $this->config['providers'] ?? [];

        foreach ($providersConfig as $name => $config) {
            $provider = $this->createProvider($name, $config);
            
            if ($provider && $provider->isConfigured()) {
                $this->providers[$name] = $provider;
            }
        }
    }

    private function createProvider(string $name, array $config): ?ToxicityProviderInterface
    {
        return match ($name) {
            'openai' => new OpenAIProvider($config),
            'perspective' => new PerspectiveProvider($config),
            default => null,
        };
    }

    private function getCacheKey(string $content, string $provider): string
    {
        $prefix = $this->config['cache']['prefix'] ?? 'toxicity_filter:';
        return $prefix . $provider . ':' . md5($content);
    }

    private function logResult(string $content, ToxicityResult $result): void
    {
        $logLevel = $this->config['logging']['log_level'] ?? 'info';
        $storeContent = $this->config['logging']['store_content'] ?? false;
        
        $logData = [
            'toxicity_score' => $result->getToxicityScore(),
            'provider' => $result->getProvider(),
            'categories' => $result->getCategories(),
        ];

        if ($storeContent) {
            $logData['content'] = $content;
        }

        Log::log($logLevel, 'Toxicity detection result', $logData);
    }
}
