<?php
echo head(array('title' => __('Content Languages')));
$tempCodes = unserialize(get_option('multilanguage_language_codes'));

$defaultCode = Zend_Registry::get('bootstrap')->getResource('Config')->locale->name;

if (plugin_is_active('Locale')) {
    $plugin = new LocalePlugin();
    $defaultCode = $plugin->filterLocale(null);
}

$codes = array();
foreach ($tempCodes as $code) {
    $codes[$code] = $code;
}

$codes = array($defaultCode => $defaultCode) + $codes;
?>

<form method='POST'>

<section class='seven columns alpha'>

<p><?php echo __('Default language is %s', $defaultCode); ?></p>
<h2><?php echo __('Exhibits'); ?></h2>

<?php if (isset($exhibits)): ?>
    <ul>
    <?php foreach ($exhibits as $exhibit):?>
    <?php
        $code = MultilanguageContentLanguage::lang('Exhibit', $exhibit->id);
    ?>
    <li>
    <?php echo get_view()->formSelect("exhibits[$exhibit->id]", $code, null, $codes);   ?>
    <?php echo $exhibit->title; ?>

    </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2><?php echo __('Simple Pages'); ?></h2>
<?php if (isset($simple_pages)): ?>
    <ul>
    <?php foreach ($simple_pages as $page):?>
    <?php
        $code = MultilanguageContentLanguage::lang('SimplePagesPage', $page->id);
    ?>
    <li>
    <?php echo get_view()->formSelect("simple_pages_page[$page->id]", $code, null, $codes);   ?>
    <?php echo $page->title; ?>

    </li>
    <?php endforeach; ?>
    </ul>

<?php endif;?>

</section>

<section class="three columns omega">
    <div class="panel" id="save">
        <input type="submit" class="submit big green button" value="Save Changes" id="save-changes" name="submit">
    </div>
</section>

</form>


<?php
echo foot();
?>
