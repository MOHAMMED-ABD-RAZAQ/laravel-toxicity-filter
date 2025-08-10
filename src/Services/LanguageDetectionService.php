<?php

namespace Packages\ToxicityFilter\Services;

class LanguageDetectionService
{
    /**
     * Detect the language of the given content
     */
    public function detectLanguage(string $content): string
    {
        // Remove extra whitespace and normalize
        $content = trim($content);
        
        if (empty($content)) {
            return 'en'; // Default to English for empty content
        }

        // Check for Arabic characters (Unicode range for Arabic)
        $arabicPattern = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u';
        
        if (preg_match($arabicPattern, $content)) {
            return 'ar';
        }

        // Check for English characters (basic Latin)
        $englishPattern = '/[a-zA-Z]/';
        
        if (preg_match($englishPattern, $content)) {
            return 'en';
        }

        // Default to English if no clear language detected
        return 'en';
    }

    /**
     * Check if content contains Arabic text
     */
    public function isArabic(string $content): bool
    {
        return $this->detectLanguage($content) === 'ar';
    }

    /**
     * Check if content contains English text
     */
    public function isEnglish(string $content): bool
    {
        return $this->detectLanguage($content) === 'en';
    }

    /**
     * Check if content is multilingual (contains both Arabic and English)
     */
    public function isMultilingual(string $content): bool
    {
        $arabicPattern = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u';
        $englishPattern = '/[a-zA-Z]/';
        
        return preg_match($arabicPattern, $content) && preg_match($englishPattern, $content);
    }

    /**
     * Get the primary language for analysis (for multilingual content)
     */
    public function getPrimaryLanguage(string $content): string
    {
        if ($this->isMultilingual($content)) {
            // For multilingual content, prioritize Arabic if it has more characters
            $arabicPattern = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u';
            $englishPattern = '/[a-zA-Z]/';
            
            preg_match_all($arabicPattern, $content, $arabicMatches);
            preg_match_all($englishPattern, $content, $englishMatches);
            
            $arabicCount = count($arabicMatches[0]);
            $englishCount = count($englishMatches[0]);
            
            return $arabicCount >= $englishCount ? 'ar' : 'en';
        }
        
        return $this->detectLanguage($content);
    }

    /**
     * Normalize Arabic text for better analysis
     */
    public function normalizeArabicText(string $content): string
    {
        // Normalize Arabic characters
        $content = $this->normalizeArabicCharacters($content);
        
        // Remove diacritics (tashkeel) for better matching
        $content = $this->removeArabicDiacritics($content);
        
        return $content;
    }

    /**
     * Normalize Arabic characters
     */
    private function normalizeArabicCharacters(string $content): string
    {
        // Normalize different forms of Arabic characters
        $replacements = [
            // Normalize Alef variations
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            
            // Normalize Waw variations
            'ؤ' => 'و',
            
            // Normalize Ya variations
            'ئ' => 'ي',
            'ى' => 'ي', // Alif Maqsura to Ya
            
            // Normalize Hamza
            'ء' => '',
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Remove Arabic diacritics (tashkeel)
     */
    private function removeArabicDiacritics(string $content): string
    {
        // Remove Arabic diacritics (Unicode range 064B-065F and 0670)
        return preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $content);
    }
}
