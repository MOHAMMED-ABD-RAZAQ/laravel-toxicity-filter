# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2024-12-19

### Added
- **Arabic Language Support**: Full support for Arabic content detection and moderation
- **Language Detection Service**: New service to automatically detect Arabic and English content
- **Arabic Text Normalization**: Automatic normalization of Arabic text for better AI analysis
- **Language-Specific Thresholds**: Separate toxicity thresholds for Arabic and English content
- **Multilingual Content Support**: Ability to handle mixed Arabic-English content
- **Arabic Diacritics Removal**: Option to remove Arabic diacritics for improved detection
- **Language Detection Configuration**: Configurable language detection settings

### Changed
- **Updated Arabic Thresholds**: More sensitive thresholds for Arabic content detection
  - Arabic Block: `0.8` → `0.1`
  - Arabic Flag: `0.6` → `0.05`
  - Arabic Warn: `0.4` → `0.02`
- **Enhanced ToxicityFilterService**: Now includes language detection and normalization
- **Improved AI Provider Integration**: OpenAI and Perspective providers now use language context
- **Updated Configuration Structure**: Added `languages` section to configuration

### Technical Details
- **Language Detection**: Character-based detection using Unicode ranges
- **Arabic Normalization**: Handles Alef, Waw, Ya, and Hamza variations
- **Caching**: Language-aware caching with language-specific cache keys
- **Threshold Management**: Automatic language-specific threshold application

### Configuration
New environment variables available:
- `TOXICITY_ARABIC_BLOCK_THRESHOLD` (default: 0.1)
- `TOXICITY_ARABIC_FLAG_THRESHOLD` (default: 0.05)
- `TOXICITY_ARABIC_WARN_THRESHOLD` (default: 0.02)
- `TOXICITY_ENGLISH_BLOCK_THRESHOLD` (default: 0.8)
- `TOXICITY_ENGLISH_FLAG_THRESHOLD` (default: 0.6)
- `TOXICITY_ENGLISH_WARN_THRESHOLD` (default: 0.4)

### Files Added
- `src/Services/LanguageDetectionService.php`
- `tests/Unit/LanguageDetectionTest.php`
- `tests/Unit/ToxicityFilterArabicTest.php`
- `CHANGELOG.md`

### Files Modified
- `src/Services/ToxicityFilterService.php`
- `src/Services/Providers/OpenAIProvider.php`
- `src/Services/Providers/PerspectiveProvider.php`
- `src/config/toxicity-filter.php`
- `composer.json` (version update)
- `README.md` (documentation updates)

## [1.0.0] - 2024-12-18

### Added
- Initial release of Laravel Toxicity Filter package
- Support for OpenAI and Google Perspective API providers
- Basic toxicity detection and moderation functionality
- Caching support for improved performance
- Logging and storage capabilities
- Queue support for asynchronous processing
- Rate limiting to prevent API abuse
- Middleware for automatic content moderation
- Console commands for testing and management
- Comprehensive test suite
- Laravel Facade for easy integration

### Features
- Multiple AI provider support
- Configurable toxicity thresholds
- Content type monitoring
- Response action configuration
- Bypass rules for trusted users
- Comprehensive error handling
- Detailed documentation and examples
