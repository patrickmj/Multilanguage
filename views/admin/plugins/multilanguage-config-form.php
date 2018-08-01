<p>
    <?php echo __('To learn more about translating the core interfaces, %sread this%s.', '<a href="https://omeka.org/codex/Translate_Omeka">', '</a>'); ?>
</p>

<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('multilanguage_append_header',
            __('Automatically append to header')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('If checked, the switcher will be automatically displayed via the hook "public_header", else you need to put it in your theme.'); ?></p>
        <?php echo $this->formCheckbox('multilanguage_append_header', true,
            array('checked' => (boolean) get_option('multilanguage_append_header'))); ?>
    </div>
</div>

<div class="field languages">
    <div class="two columns alpha">
        <?php echo $this->formLabel('multilanguage_locales', __('Languages')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('Select the languages into which your site can be translated.'); ?></p>
        <div class="input-block">
            <?php echo $this->formMultiCheckbox('multilanguage_locales', $locales, null, $codes);   ?>
        </div>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('multilanguage_locales_admin', __('Admin languages')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('The languages to use for the admin back-end.'); ?></p>
        <div class="input-block">
            <?php echo $this->formMultiCheckbox('multilanguage_locales_admin', $localesAdmin, null, $codes); ?>
        </div>
    </div>
</div>

<?php
$elementOptions = get_db()->getTable('Element')->findPairsForSelectForm();
?>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('element_sets', __('Record Elements')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('Check the metadata fields that you want to make translatable.'); ?></p>
        <div class="input-block">
<?php
if (get_option('show_element_set_headings')) {
    foreach ($elementOptions as $elSet => $options) {
        echo "<div class='field elements'>";
        echo "<h2>$elSet</h2>";
        echo $this->formMultiCheckbox('element_sets', $translatableElementIds, null, $options, '');
        echo "</div>";
    }
} else {
    echo "<div class='field no-headings elements'>";
    echo $this->formMultiCheckbox('element_sets', $translatableElementIds, null, $elementOptions, '');
    echo "</div>";
}
?>
        </div>
    </div>
</div>
