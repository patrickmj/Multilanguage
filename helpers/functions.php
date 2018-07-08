<?php
// From plugin LocaleSwitcher.

function locale_human($locale)
{
    $parts = explode('_', $locale);
    if (isset($parts[1])) {
        $langCode = $parts[0];
        $regionCode = $parts[1];
        $language = Zend_Locale::getTranslation($langCode, 'language');
        $region = Zend_Locale::getTranslation($regionCode, 'territory');
    } else {
        $region = '';
        $language = Zend_Locale::getTranslation($locale, 'language');
    }
    if ($region != '') {
        $region = " - $region";
    }

    return ucfirst($language) . $region;
}
