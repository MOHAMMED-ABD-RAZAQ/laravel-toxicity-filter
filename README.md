# Laravel AI Toxicity Filter Package

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/mohammed-abd-razaq/laravel-toxicity-filter)
[![PHP Version](https://img.shields.io/badge/php-%5E8.0-brightgreen.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E9.0%7C%5E10.0%7C%5E11.0%7C%5E12.0-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](https://opensource.org/licenses/MIT)

A professional Laravel library that integrates AI-based toxicity detection engines to automatically evaluate, moderate, and filter user-generated content such as comments, posts, messages, and reviews within your application.

## Features

- 🤖 **Multiple AI Providers**: Support for OpenAI Moderation API, Google Perspective API, and extensible for more
- ⚡ **Laravel Integration**: Seamless integration with Laravel facades, service providers, and middleware
- 🛡️ **Automatic Filtering**: Middleware for automatic content moderation on routes
- 🎛️ **Configurable Thresholds**: Customizable toxicity thresholds for blocking, flagging, and warning
- 📊 **Detailed Analytics**: Comprehensive logging and database storage of toxicity detection results
- 🚀 **Queue Support**: Async processing for bulk or large content moderation
- 💾 **Caching**: Redis/database caching to reduce API calls and improve performance
- 🔧 **Extensible**: Easy to add new AI providers through clean interfaces
- 🔒 **Privacy First**: Content hashing for privacy protection
- 📈 **Performance Optimized**: Built-in rate limiting and content optimization
- 🛠️ **Developer Friendly**: Rich testing utilities and comprehensive error handling
- 🌐 **Multi-language Support**: Works with content in multiple languages

## Requirements

- PHP 8.0 or higher
- Laravel 9.0, 10.0, 11.0, or 12.0
- OpenAI API key (for OpenAI provider)
- Google Perspective API key (for Perspective provider)

## Installation

1. Install via Composer:

```bash
composer require mohammed-abd-razaq/laravel-toxicity-filter
```

Or if using the local package, update your root `composer.json`:

```json
{
    "require": {
        "packages/toxicity-filter": "^1.0"
    }
}
```

2. Run composer update:

```bash
composer update
```

3. Publish the configuration file:

```bash
php artisan vendor:publish --tag=toxicity-filter-config
```

4. Publish and run migrations:

```bash
php artisan vendor:publish --tag=toxicity-filter-migrations
php artisan migrate
```

5. Clear configuration cache:

```bash
php artisan config:clear
```

## Configuration

Set up your AI provider API keys in `.env`:

```env
# OpenAI Configuration
OPENAI_API_KEY=your_openai_api_key
OPENAI_MODERATION_MODEL=text-moderation-latest

# Google Perspective API Configuration
PERSPECTIVE_API_KEY=your_perspective_api_key

# Toxicity Thresholds (0.0 - 1.0)
TOXICITY_BLOCK_THRESHOLD=0.8
TOXICITY_FLAG_THRESHOLD=0.6
TOXICITY_WARN_THRESHOLD=0.4

# Caching
TOXICITY_CACHE_ENABLED=true
TOXICITY_CACHE_TTL=3600

# Logging
TOXICITY_LOGGING_ENABLED=true
TOXICITY_STORE_CONTENT=false
```

## Usage

### Basic Usage with Facade

```php
use Packages\ToxicityFilter\Facades\ToxicityFilter;

// Analyze content
$result = ToxicityFilter::analyze("This is some content to check");

echo $result->getToxicityScore(); // 0.85
echo $result->getProvider(); // 'openai'
var_dump($result->getCategories()); // ['harassment', 'hate']

// Quick checks
if (ToxicityFilter::shouldBlock($content)) {
    // Block the content
}

if (ToxicityFilter::shouldFlag($content)) {
    // Flag for manual review
}

if (ToxicityFilter::shouldWarn($content)) {
    // Show warning to user
}
```

### Using Specific Providers

```php
// Use OpenAI specifically
$result = ToxicityFilter::analyze($content, 'openai');

// Use Perspective API specifically
$result = ToxicityFilter::analyze($content, 'perspective');

// Get available providers
$providers = ToxicityFilter::getAvailableProviders();
```

### Optional Middleware Usage

The package includes optional middleware for automatic content filtering. To use it, you need to manually register it first.

#### Register the Middleware

Add to your `app/Http/Kernel.php`:

```php
// In app/Http/Kernel.php

protected $routeMiddleware = [
    // ... other middleware
    'toxicity-filter' => \Packages\ToxicityFilter\Middleware\ToxicityFilterMiddleware::class,
];
```

#### Apply to Routes

```php
// In your routes file
Route::post('/comments', [CommentController::class, 'store'])
    ->middleware('toxicity-filter');

// Or specify fields to check
Route::post('/posts', [PostController::class, 'store'])
    ->middleware('toxicity-filter:title,content,description');
```

The middleware will:
- Automatically block toxic content (returns 422 error)
- Flag moderately toxic content for review
- Add warnings to the request for mildly toxic content
- Log all detections to the database

### Advanced Usage

```php
use Packages\ToxicityFilter\Contracts\ToxicityFilterInterface;

class ContentModerationService
{
    public function __construct(
        private ToxicityFilterInterface $toxicityFilter
    ) {}

    public function moderateComment(string $content, User $user): array
    {
        $result = $this->toxicityFilter->analyze($content);
        
        $response = [
            'allowed' => true,
            'message' => null,
            'requires_review' => false,
        ];
        
        if ($result->shouldBlock(0.8)) {
            $response['allowed'] = false;
            $response['message'] = 'Content blocked due to inappropriate language';
        } elseif ($result->shouldFlag(0.6)) {
            $response['requires_review'] = true;
            $response['message'] = 'Content flagged for review';
        }
        
        return $response;
    }
}
```

### Queue Processing

For async processing, you can dispatch jobs:

```php
use Packages\ToxicityFilter\Jobs\AnalyzeToxicityJob;

// Process large content asynchronously
AnalyzeToxicityJob::dispatch($content, $userId, $options);
```

## Supported AI Providers

### OpenAI Moderation API
- **Pros**: High accuracy, fast response, multiple toxicity categories
- **Cons**: Requires API key, has usage costs
- **Content Limit**: ~32,000 characters

### Google Perspective API
- **Pros**: Free tier available, detailed attribute scoring
- **Cons**: Limited free quota, requires Google Cloud setup
- **Content Limit**: 3,000 characters

## Configuration Options

The package offers extensive configuration options:

- **Providers**: Configure multiple AI providers with failover
- **Thresholds**: Set different toxicity thresholds for various actions
- **Caching**: Cache results to reduce API calls and costs
- **Logging**: Comprehensive logging with configurable storage
- **Queue**: Async processing for better performance
- **Bypass Rules**: Skip filtering for trusted users or content

## Database Schema

The package creates a `toxicity_detections` table to log all analysis results:

```sql
- id (primary key)
- provider (string, indexed)
- toxicity_score (decimal, indexed)
- categories (json)
- content_hash (text, indexed)
- content (text, optional)
- metadata (json)
- action_taken (string, indexed)
- user_id (bigint, nullable, indexed)
- ip_address, user_agent, request_path
- timestamps
```

## Extending the Package

### Adding New AI Providers

Implement the `ToxicityProviderInterface`:

```php
use Packages\ToxicityFilter\Contracts\ToxicityProviderInterface;
use Packages\ToxicityFilter\ValueObjects\ToxicityResult;

class CustomProvider implements ToxicityProviderInterface
{
    public function analyze(string $content, array $options = []): ToxicityResult
    {
        // Implement your provider logic
    }
    
    public function getName(): string
    {
        return 'custom';
    }
    
    // ... implement other interface methods
}
```

## Testing

```bash
# Run package tests
cd packages/toxicity-filter
composer test

# Run with coverage
composer test-coverage

# Run specific test file
vendor/bin/phpunit tests/Unit/ToxicityFilterServiceTest.php

# Run tests with debug output
vendor/bin/phpunit --debug
```

### Test Configuration

Create a `.env.testing` file for test environment:

```env
TOXICITY_CACHE_ENABLED=false
TOXICITY_LOGGING_ENABLED=false
OPENAI_API_KEY=test_key
PERSPECTIVE_API_KEY=test_key
```

## Troubleshooting

### Common Issues

**1. Configuration not loaded**
```bash
php artisan config:clear
php artisan config:cache
```

**2. Provider API errors**
- Verify API keys are correctly set in `.env`
- Check API rate limits and quotas
- Ensure network connectivity to provider endpoints

**3. Migration issues**
```bash
php artisan migrate:rollback
php artisan vendor:publish --tag=toxicity-filter-migrations --force
php artisan migrate
```

**4. Cache issues**
```bash
php artisan cache:clear
php artisan config:clear
```

### Debug Mode

Enable debug logging in your configuration:

```php
'debug' => env('TOXICITY_DEBUG', false),
'log_level' => env('TOXICITY_LOG_LEVEL', 'info'),
```

## Performance Considerations

- **Caching**: Enable caching to reduce API calls for duplicate content
- **Queue**: Use async processing for bulk content or non-blocking operations
- **Rate Limiting**: Configure rate limits to stay within API quotas
- **Content Optimization**: Pre-filter very short content or obvious spam

## Security & Privacy

- **Content Hashing**: Store MD5 hashes instead of actual content for privacy
- **API Key Management**: Store API keys securely in environment variables
- **User Bypass**: Allow trusted users to bypass filtering when appropriate
- **Audit Trail**: Comprehensive logging for compliance and debugging

## Changelog

### Version 1.0.0

**Initial Release**
- ✅ OpenAI Moderation API integration
- ✅ Google Perspective API integration
- ✅ Laravel facade and service provider
- ✅ Configurable toxicity thresholds
- ✅ Middleware for automatic filtering
- ✅ Database logging and analytics
- ✅ Caching support
- ✅ Queue processing
- ✅ Extensible provider system
- ✅ Comprehensive test suite

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Copy `.env.example` to `.env` and configure
4. Run tests: `composer test`

## Support

- 📧 **Email**: [mohammedalrazaq61@gmail.com](mailto:mohammedalrazaq61@gmail.com)
- 🐛 **Issues**: [GitHub Issues](https://github.com/mohammed-abd-razaq/laravel-toxicity-filter/issues)
- 📖 **Documentation**: [GitHub Repository](https://github.com/mohammed-abd-razaq/laravel-toxicity-filter)

## License

This package is open-sourced software licensed under the [MIT License](https://opensource.org/licenses/MIT).

## Author

**Mohammed Abd Razaq**
- GitHub: [@mohammed-abd-razaq](https://github.com/mohammed-abd-razaq)
- Email: [mohammedalrazaq61@gmail.com](mailto:mohammedalrazaq61@gmail.com)

---

⭐ If you find this package helpful, please consider giving it a star on GitHub!
