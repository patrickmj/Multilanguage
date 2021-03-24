<?php
/**
 * @var Omeka_View $this
 * @var array $locales
 * @var array $localesAdmin
 * @var array $codes
 * @var array $translatableElementIds
 */
?>

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
            array('checked' => (bool) get_option('multilanguage_append_header'))); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('multilanguage_translations_reset', __('Reset translations')); ?>
    </div>
    <div class="inputs five columns omega">
        <?php echo $this->formCheckbox('multilanguage_translations_reset', true, array('checked' => false)); ?>
        <p class="explanation">
            <?php echo __('Reset all translations when files in "plugins/Translations/languages" or "themes/my-theme/languages" were updated manually.'); ?>
        </p>
    </div>
</div>

<div class="field languages">
    <div class="two columns alpha">
        <?php echo $this->formLabel('multilanguage_locales', __('Languages')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('Select the languages into which your site can be translated.'); ?></p>
		<table id="hide-elements-table">
			<thead>
				<tr>
					<th class="hide-boxes"><?php echo __('Language'); ?></th>
					<th class="hide-boxes"><?php echo __('Code'); ?></th>
					<th class="hide-boxes"><?php echo __('Public'); ?></th>
					<th class="hide-boxes"><?php echo __('Admin'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$pattern = "/\([^\)]*\)/";
				foreach ($codes as $key=>$code) {
					echo "<tr>";
					echo "<td>" . preg_replace($pattern, "", $code) . "</td>";
					echo "<td>" . $key . "</td>";
					echo "<td class='center'>" . $this->formCheckbox('multilanguage_locales[]', $key, array('id' => 'multilanguage_locales-' . $key, 'disableHidden' => true, 'checked' => in_array($key, $locales))) . "</td>";
					echo "<td class='center'>" . $this->formCheckbox('multilanguage_locales_admin[]', $key, array('id' => 'multilanguage_locales_admin-' . $key,'disableHidden' => true, 'checked' => in_array($key, $localesAdmin))) . "</td>";
					echo "</tr>\n";
				}
				?>
			</tbody>
		</table>
	</div>
</div>

<?php
$elementOptions = get_db()->getTable('Element')->findPairsForSelectForm();
?>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('multilanguage_elements', __('Record Elements')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('Check the metadata fields that you want to make translatable.'); ?></p>
        <div class="input-block">
<?php
if (get_option('show_element_set_headings')) {
    foreach ($elementOptions as $elSet => $options) {
        echo "<div class='field elements'>";
        echo "<h2>$elSet</h2>";
        echo $this->formMultiCheckbox('multilanguage_elements', $translatableElementIds, null, $options, '');
        echo "</div>";
    }
} else {
    echo "<div class='field no-headings elements'>";
    echo $this->formMultiCheckbox('multilanguage_elements', $translatableElementIds, null, $elementOptions, '');
    echo "</div>";
}
?>
        </div>
    </div>
</div>
