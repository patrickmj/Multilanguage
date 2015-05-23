<?php $view = get_view();?>

<p>
<?php echo __('Select the languages into which your site can be translated.'); ?>
</p>
<?php

$multilanguageCodes = unserialize(get_option('multilanguage_language_codes'));

$files = scandir(BASE_DIR . '/application/languages');

foreach ($files as $file) {
    if (strpos($file, '.mo') !== false) {
        $code = str_replace('.mo', '', $file);
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
        $codes[$code] = ucfirst($language) . $region . " ($code)";
    }
}
$codes['en'] = ucfirst( Zend_Locale::getTranslation('en', 'language', 'en' ) ) . " (en)";
asort($codes);

?>

<div class="field languages">
    <div class="two columns alpha">
        <label><?php echo __('Language'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("The languages available to translate content. To learn more about translating the core interfaces, <a href='http://omeka.org/codex/Translate_Omeka'>read this</a>."); ?> </p>
        <div class="input-block">
            <?php echo get_view()->formMultiCheckbox('multilanguage_language_codes', $multilanguageCodes, null, $codes);   ?> 
        </div>
    </div>
</div>


<p><?php echo __('Check the metadata fields that you want to make translatable.'); ?></p>

<?php
$elTable = get_db()->getTable('Element');
$data = $elTable->findPairsForSelectForm();
$translatableElements = unserialize(get_option('multilanguage_elements'));
$view = get_view();
$values = array();
if(is_array($translatableElements)) {
    foreach($translatableElements as $elSet=>$elements) {
        foreach($elements as $element) {
            $elObject = $elTable->findByElementSetNameAndElementName($elSet, $element);
            $values[] = $elObject->id;
        }
    }
}

if (get_option('show_element_set_headings') ) {
    foreach($data as $elSet=>$options) {
        echo "<div class='field elements'>";
        echo "<h2>$elSet</h2>";
        echo $view->formMultiCheckbox('element_sets', $values, null, $options, '');
        echo "</div>";
    }
} else {
    echo "<div class='field no-headings elements'>";
    echo $view->formMultiCheckbox('element_sets', $values, null, $data, '');
    echo "</div>";
}
