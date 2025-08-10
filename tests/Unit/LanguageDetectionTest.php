<?php

namespace Packages\ToxicityFilter\Tests\Unit;

use Packages\ToxicityFilter\Services\LanguageDetectionService;
use PHPUnit\Framework\TestCase;

class LanguageDetectionTest extends TestCase
{
    private LanguageDetectionService $languageDetector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->languageDetector = new LanguageDetectionService();
    }

    public function testDetectsArabicText()
    {
        $arabicText = 'مرحبا بالعالم';
        $language = $this->languageDetector->detectLanguage($arabicText);
        
        $this->assertEquals('ar', $language);
        $this->assertTrue($this->languageDetector->isArabic($arabicText));
        $this->assertFalse($this->languageDetector->isEnglish($arabicText));
    }

    public function testDetectsEnglishText()
    {
        $englishText = 'Hello world';
        $language = $this->languageDetector->detectLanguage($englishText);
        
        $this->assertEquals('en', $language);
        $this->assertTrue($this->languageDetector->isEnglish($englishText));
        $this->assertFalse($this->languageDetector->isArabic($englishText));
    }

    public function testDetectsMultilingualText()
    {
        $multilingualText = 'Hello مرحبا world';
        $this->assertTrue($this->languageDetector->isMultilingual($multilingualText));
        
        // Should prioritize English since it has more characters
        $primaryLanguage = $this->languageDetector->getPrimaryLanguage($multilingualText);
        $this->assertEquals('en', $primaryLanguage);
    }

    public function testDetectsMultilingualTextWithMoreArabic()
    {
        $multilingualText = 'Hello مرحبا بالعالم world';
        $this->assertTrue($this->languageDetector->isMultilingual($multilingualText));
        
        // Should prioritize Arabic since it has more characters
        $primaryLanguage = $this->languageDetector->getPrimaryLanguage($multilingualText);
        $this->assertEquals('ar', $primaryLanguage);
    }

    public function testNormalizesArabicText()
    {
        $arabicWithDiacritics = 'مَرْحَباً بِالعَالَمِ';
        $normalized = $this->languageDetector->normalizeArabicText($arabicWithDiacritics);
        
        // Should remove diacritics
        $this->assertNotEquals($arabicWithDiacritics, $normalized);
        $this->assertTrue($this->languageDetector->isArabic($normalized));
    }

    public function testHandlesEmptyContent()
    {
        $language = $this->languageDetector->detectLanguage('');
        $this->assertEquals('en', $language);
    }

    public function testHandlesMixedContent()
    {
        $mixedText = 'Hello 123 !@#';
        $language = $this->languageDetector->detectLanguage($mixedText);
        $this->assertEquals('en', $language);
    }
}
