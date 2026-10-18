<?php

namespace App\Models\Concerns;

/**
 * Champ traduit avec repli sur le français : localized('title', 'ar') lit title_ar, sinon title.
 */
trait HasLocalizedFields
{
    public function localized(string $field, ?string $language): mixed
    {
        if (in_array($language, ['en', 'ar'], true)) {
            $value = $this->getAttribute("{$field}_{$language}");
            if (filled($value)) {
                return $value;
            }
        }

        return $this->getAttribute($field);
    }

    /** Langue demandée par ?lang= (fr par défaut). */
    public static function requestLanguage(): string
    {
        $language = request()->query('lang');

        return in_array($language, ['en', 'ar'], true) ? $language : 'fr';
    }
}
