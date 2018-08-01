<?php if ($locales):
    $currentLocale = Zend_Registry::get('bootstrap')->getResource('Locale')->toString();
    $request = Zend_Controller_Front::getInstance()->getRequest();
    $currentUrl = $request ->getRequestUri();
    $query = array();
    // Append the record for managed plugins in public front-end.
    if (!is_admin_theme()):
        $module =  $request->getModuleName();
        switch ($module):
            case 'exhibit-builder':
                $action = $request->getActionName();
                switch ($action):
                    case 'summary':
                        $query['record_type'] = 'Exhibit';
                        $exhibit = get_current_record('exhibit');
                        $query['id'] = $exhibit->id;
                        break;
                endswitch;
                break;
            case 'simple-pages':
                $query['record_type'] = 'SimplePagesPage';
                $query['id'] = $request->getParam('id');
                break;
        endswitch;
    endif;
    ?>
    <ul class="locale-switcher">
        <?php foreach ($locales as $locale): ?>
            <?php $country = $this->localeToCountry($locale); ?>
            <li>
                <?php if ($currentLocale == $locale): ?>
                    <span class="active flag-icon flag-icon-<?php echo strtolower($country); ?>"></span>
                <?php else: ?>
                    <?php $language = Zend_Locale::getTranslation(substr($locale, 0, 2), 'language'); ?>
                    <?php $url = url('setlocale', array('locale' => $locale, 'redirect' => $currentUrl) + $query); ?>
                    <a href="<?php echo $url ; ?>" title="<?php echo locale_human($locale); ?>"><span class="flag-icon flag-icon-<?php echo strtolower($country); ?>"></span></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
