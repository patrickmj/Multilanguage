<?php if ($locales): ?>
    <?php $currentLocale = Zend_Registry::get('bootstrap')->getResource('Locale')->toString(); ?>
    <?php $currentUrl = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri(); ?>
    <ul class="locale-switcher">
        <?php foreach ($locales as $locale): ?>
            <?php $country = $this->localeToCountry($locale); ?>
            <li>
                <?php if ($currentLocale == $locale): ?>
                    <span class="active flag-icon flag-icon-<?php echo strtolower($country); ?>"></span>
                <?php else: ?>
                    <?php $language = Zend_Locale::getTranslation(substr($locale, 0, 2), 'language'); ?>
                    <?php $url = url('setlocale', array('locale' => $locale, 'redirect' => $currentUrl)); ?>
                    <a href="<?php echo $url ; ?>" title="<?php echo locale_description($locale); ?>"><span class="flag-icon flag-icon-<?php echo strtolower($country); ?>"></span></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
