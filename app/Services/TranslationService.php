<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    private $deeplClient;
    private $deeplKey;

    public function __construct()
    {
        $this->deeplKey = env('DEEPL_KEY');
        if ($this->deeplKey) {
            $this->deeplClient = new \DeepL\DeepLClient($this->deeplKey);
        }
    }

    /**
     * Translate text from Swedish to English using DeepL
     */
    public function translate(string $text, string $targetLocale = 'en'): string
    {
        if ($targetLocale !== 'en' || !$this->deeplClient) {
            return $text;
        }

        $cacheKey = 'deepl:translation:' . md5($text);
        
        return Cache::remember($cacheKey, now()->addDays(7), function () use ($text) {
            try {
                return $this->deeplClient->translateText($text, 'sv', 'en-US')->text;
            } catch (\Exception $e) {
                Log::error('DeepL translation error: ' . $e->getMessage(), [
                    'text' => $text,
                    'error' => $e->getMessage()
                ]);
                return $text; // Return original text on error
            }
        });
    }

    /**
     * Get translated content based on locale
     */
    public function getLocalizedContent($model, string $locale, string $field): string
    {
        // If English is requested and we have English content, use it
        if ($locale === 'en') {
            $enField = str_replace('_sv', '_en', $field);
            if (!empty($model->$enField)) {
                return $model->$enField;
            }
            
            // If no English content exists, translate from Swedish
            if (!empty($model->$field) && $this->deeplClient) {
                return $this->translate($model->$field, $locale);
            }
        }
        
        // Return Swedish content (default)
        return $model->$field ?? '';
    }

    /**
     * Translate an array of content
     */
    public function translateArray(array $data, string $locale): array
    {
        if ($locale !== 'en' || !$this->deeplClient) {
            return $data;
        }

        $translated = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $translated[$key] = $this->translate($value, $locale);
            } elseif (is_array($value)) {
                $translated[$key] = $this->translateArray($value, $locale);
            } else {
                $translated[$key] = $value;
            }
        }

        return $translated;
    }

    /**
     * Clear translation cache
     */
    public function clearCache(): void
    {
        Cache::flush();
    }

    /**
     * Check if DeepL is available
     */
    public function isDeepLAvailable(): bool
    {
        return !empty($this->deeplKey) && $this->deeplClient !== null;
    }
} 