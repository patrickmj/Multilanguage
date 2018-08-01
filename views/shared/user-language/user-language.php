<?php
// Mainly used for the plugin Guest user.
echo head(array('title' => __('Preferred Language')));
?>
<form method='POST'>
    <div class="field">
        <div class="two columns alpha">
            <?php echo get_view()->formLabel('multilanguage_locale_code', __('Select your preferred language')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation"><?php echo __('The default language is %s', $availableCodes[$defaultCode]); ?> </p>
            <div class="input-block">
                <?php echo get_view()->formSelect('multilanguage_locale_code', $lang, null, $availableCodes); ?>
            </div>
        </div>
    </div>

<section class="three columns omega">
    <div class="panel" id="save">
        <input type="submit" class="submit big green button" value="<?php echo __('Save Changes'); ?>" id="save-changes" name="submit">
    </div>
</section>

</form>
<?php echo foot(); ?>
