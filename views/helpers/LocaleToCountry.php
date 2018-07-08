<?php

class Multilanguage_View_Helper_LocaleToCountry extends Zend_View_Helper_Abstract
{
    protected $countriesByLocale = array(
        'ar' => 'SA',
        'cs' => 'CZ',
        'en' => 'GB',
        'es' => 'ES',
        'et' => 'EE',
        'eu' => 'ES',
        'fr' => 'FR',
        'gl' => 'ES',
        'he' => 'IL',
        'hr' => 'HR',
        'id' => 'ID',
        'is' => 'IS',
        'it' => 'IT',
        'ja' => 'JP',
        'lt' => 'LT',
        'mn' => 'MN',
        'nb' => 'NO',
        'pl' => 'PL',
        'ro' => 'RO',
        'ru' => 'RU',
        'ta' => 'LK',
        'th' => 'TH',
        'uk' => 'UA',
    );

    public function localeToCountry($locale)
    {
        if (strlen($locale) == 2 && isset($this->countriesByLocale[$locale])) {
            return $this->countriesByLocale[$locale];
        }

        $matches = array();
        if (preg_match('/[a-z]{2}_([A-Z]{2})/', $locale, $matches)) {
            return $matches[1];
        }
    }
}
