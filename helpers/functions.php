<?php
// From plugin LocaleSwitcher.

/**
 * Convert a standard locale string (en_US)  into the language and region.
 *
 * @param string $locale
 * @return string
 */
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

/**
 * Get the related translated record (page or exhibit) from a record.
 *
 * @param Omeka_Record_AbstractRecord $record
 * @param string $locale
 * @return SimplePagesPage|Exhibit|null The translated record if exists.
 */
function locale_record($record, $locale = null)
{
    return locale_record_from_id_or_slug(get_class($record), $record->id, $locale);
}

/**
 * Get the related translated record (page or exhibit) from a record id or slug.
 *
 * @param string $recordType
 * @param int|string $recordIdOrSlug The record id if numeric, else a slug.
 * @param string $locale
 * @return SimplePagesPage|Exhibit|null The translated record if exists.
 */
function locale_record_from_id_or_slug($recordType, $recordIdOrSlug, $locale = null)
{
    static $currentLocale;

    if (empty($locale)) {
        if (is_null($currentLocale)) {
            $currentLocale = Zend_Registry::get('bootstrap')->getResource('Locale')->toString();
        }
        $locale = $currentLocale;
    }

    $translated = get_db()
        ->getTable('MultilanguageRelatedRecord')
        ->findRelatedSourceRecordForLocale($recordType, $recordIdOrSlug, $locale);
    return $translated;
}

