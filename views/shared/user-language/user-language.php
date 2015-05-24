<?php
echo head(array('title' => __('Preferred Language')));

$codes = unserialize( get_option('multilanguage_language_codes') );
$availableCodes = array();
$defaultCodes = Zend_Locale::getDefault();
$defaultCode = current(array_keys($defaultCodes));

if (! isset($lang)) {
    $lang = $defaultCode;
}

if (plugin_is_active('Locale')) {
    $plugin = new LocalePlugin();
    $defaultCode = $plugin->filterLocale(null);
}

array_unshift($codes, $defaultCode);
foreach ($codes as $code) {
    $parts = explode('_', $code);
    if (isset($parts[1])) {
        $langCode = $parts[0];
        $regionCode = $parts[1];
        $language = Zend_Locale::getTranslation($langCode, 'language', $langCode);
        $region = Zend_Locale::getTranslation($regionCode, 'territory', $langCode);
    } else {
        $region = '';
        $language = Zend_Locale::getTranslation($code, 'language', $code);
    }
    if ($region != '') {
        $region = " - $region";
    }
    $availableCodes[$code] = ucfirst($language) . $region . " ($code)";
}

?>
<form method='POST'>
    <div class="field">
        <div class="two columns alpha">
            <label><?php echo __('Select your prefered language'); ?></label>
        </div>
        <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('The default language is %s', $availableCodes[$defaultCode]);  ?> </p>
            <div class="input-block">
                <?php echo get_view()->formSelect('multilanguage_language_code', $lang, null, $availableCodes);   ?> 
            </div>
        </div>
    </div>
    
<section class="three columns omega">
    <div class="panel" id="save">
        <input type="submit" class="submit big green button" value="Save Changes" id="save-changes" name="submit">
    </div>
</section>

</form>
<?php echo foot(); ?>