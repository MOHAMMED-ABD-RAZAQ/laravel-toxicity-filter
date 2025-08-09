<?php

namespace Packages\ToxicityFilter\Facades;

use Illuminate\Support\Facades\Facade;
use Packages\ToxicityFilter\ValueObjects\ToxicityResult;

/**
 * @method static ToxicityResult analyze(string $content, ?string $provider = null, array $options = [])
 * @method static bool shouldBlock(string $content, ?string $provider = null)
 * @method static bool shouldFlag(string $content, ?string $provider = null)
 * @method static bool shouldWarn(string $content, ?string $provider = null)
 * @method static array getAvailableProviders()
 * @method static void setDefaultProvider(string $provider)
 */
class ToxicityFilter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'toxicity-filter';
    }
}
