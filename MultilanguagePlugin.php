<?php
class MultilanguagePlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'initialize',
        'install',
        'uninstall',
        'config',
        'config_form',
        'admin_head',
        'admin_footer',
        // 'admin_users_browse_each',
        'users_form',
        'after_save_user',
        'exhibits_browse_sql',
        'simple_pages_pages_browse_sql',
    );

    protected $_filters = array(
        'admin_navigation_main',
        'guest_user_links',
        'locale',
        // Note: the filter locale adds some filters.
    );

    protected $_translationTable = null;

    protected $locale_code;

    public function hookInitialize($args)
    {
        add_translation_source(dirname(__FILE__) . '/languages');
    }

    public function hookInstall()
    {
        $db = $this->_db;
        $sql = "
CREATE TABLE IF NOT EXISTS $db->MultilanguageTranslation (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `element_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `record_type` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `locale_code` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `text` text COLLATE utf8_unicode_ci,
  `translation` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `element_id` (`element_id`,`record_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
                    ";

        $db->query($sql);

        $sql = "
CREATE TABLE IF NOT EXISTS $db->MultilanguageContentLanguage (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `record_type` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  `lang` tinytext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
        ";

        $db->query($sql);

        $sql = "

CREATE TABLE IF NOT EXISTS $db->MultilanguageUserLanguage (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `lang` tinytext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

        ";

        $db->query($sql);
    }

    public function hookUninstall()
    {
        $db = $this->_db;
        $sql = "DROP TABLE $db->MultilanguageTranslation ";
        $db->query($sql);

        $sql = "DROP TABLE $db->MultilanguageContentLanguage ";
        $db->query($sql);


        $sql = "DROP TABLE $db->MultilanguageUserLanguage";
        $db->query($sql);
    }

    public function hookConfigForm()
    {
        include('config_form.php');
    }

    public function hookConfig($args)
    {
        $post = $args['post'];
        $elements = array();
        $elTable = get_db()->getTable('Element');
        foreach ($post['element_sets'] as $elId) {
            $element = $elTable->find($elId);
            $elSet = $element->getElementSet();
            if (!array_key_exists($elSet->name, $elements)) {
                $elements[$elSet->name] = array();
            }
            $elements[$elSet->name][] = $element->name;
        }
        set_option('multilanguage_elements', serialize($elements));
        set_option('multilanguage_language_codes', serialize($post['multilanguage_language_codes']));
    }

    public function hookAdminHead()
    {
        queue_css_file('multilanguage');
        queue_js_file('multilanguage');
    }

    public function hookAdminFooter()
    {
        echo "<div id='multilanguage-modal'>
        <textarea id='multilanguage-translation'></textarea>
        </div>";

        echo "<script type='text/javascript'>
        var baseUrl = '" . WEB_ROOT . "';
        </script>
        ";
    }

    public function hookUsersForm($args)
    {
        $form = $args['form'];
        $user = $args['user'];

        $defaultCode = $this->getDefaultLocaleCode();
        $userLang = ($user && $user->id) ? $this->getUserLang($user, $defaultCode) : $defaultCode;
        $availableCodes = $this->prepareLocaleCodes($userLang);

        $form->addElement('select', 'multilanguage_language_code', array(
            'label' => __('Preferred language'),
            'description' => __('Default language is %s.', $availableCodes[$defaultCode]),
            'multiOptions' => $availableCodes,
            'value' => $userLang,
            'required' => false,
        ));
    }

    public function hookAfterSaveUser($args)
    {
        $post = $args['post'];
        $user = $args['record'];
        if (isset($post['multilanguage_language_code'])) {
            $prefLanguages = get_db()->getTable('MultilanguageUserLanguage')
                ->findBy(array('user_id' => $user->id));
            if (empty($prefLanguages)) {
                $prefLanguage = new MultilanguageUserLanguage;
                $prefLanguage->user_id = $user->id;
            } else {
                $prefLanguage = $prefLanguages[0];
            }

            $defaultCode = $this->getDefaultLocaleCode();
            $availableCodes = $this->prepareLocaleCodes();
            $userLang = $post['multilanguage_language_code'];
            $prefLanguage->lang = isset($availableCodes[$userLang])
                ? $userLang
                : $defaultCode;
            $prefLanguage->save();
        }
    }

    public function hookExhibitsBrowseSql($args)
    {
        $this->modelBrowseSql($args, 'Exhibit');
    }

    public function hookSimplePagesPagesBrowseSql($args)
    {
        $this->modelBrowseSql($args, 'SimplePagesPage');
    }

    protected function modelBrowseSql($args, $model)
    {
        if (!is_admin_theme()) {
            $select = $args['select'];
            $db = get_db();
            $alias = $db->getTable('MultilanguageContentLanguage')->getTableAlias();
            $modelAlias = $db->getTable($model)->getTableAlias();
            $select->joinLeft(
                array($alias => $db->MultilanguageContentLanguage),
                "$alias.record_id = $modelAlias.id",
                array()
            );
            $select->where("$alias.record_type = ?", $model);
            $select->where("$alias.lang = ?", $this->locale_code);
        }
    }
    public function filterAdminNavigationMain($nav)
    {
        $nav['Multilanguage_content'] = array(
            'label' => __('Multilanguage Content'),
            'uri' => url('multilanguage/translations/content-language'),
        );
        return $nav;
    }

    public function filterGuestUserLinks($links)
    {
        $links['Multilanguage'] = array(
            'label' => __('Preferred Language'),
            'uri' => url('multilanguage/user-language/user-language'),
        );
        return $links;
    }

    public function filterLocale($locale)
    {
        $sessionLocale = $this->getLocaleFromGetOrSession($locale);
        $langCodes = unserialize(get_option('multilanguage_language_codes'));
        $validSessionLocale = in_array($sessionLocale, $langCodes);
        $defaultCodes = Zend_Locale::getDefault();
        $defaultCode = current(array_keys($defaultCodes));
        if (!$validSessionLocale) {
            $this->locale_code = $defaultCode;
        } else {
            $this->locale_code = $sessionLocale;
        }
        $this->_translationTable = $this->_db->getTable('MultilanguageTranslation');
        $user = current_user();
        $userPrefLanguageCode = false;
        $userPrefLanguage = false;
        if ($user) {
            $prefLanguages = $this->_db->getTable('MultilanguageUserLanguage')
                ->findBy(array('user_id' => $user->id));
            if (!empty($prefLanguages)) {
                $userPrefLanguage = $prefLanguages[0];
                $userPrefLanguageCode = $userPrefLanguage->lang;
                $this->locale_code = $userPrefLanguageCode;
            }
        }

        if (!$validSessionLocale && !$userPrefLanguageCode) {
            $codes = $langCodes;
            //dump the site's default code to the end as a fallback
            $codes[] = $defaultCode;
            $browserCodes = array_keys(Zend_Locale::getBrowser());
            $match = false;
            foreach ($browserCodes as $browserCode) {
                if (in_array($browserCode, $codes)) {
                    $this->locale_code = $browserCode;
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                // Failed to find browserCode in our language codes.
                // Try to match a two character code and set it to
                // the closest equivalent if available.
                $shortcodes = array();
                foreach ($codes as $c) {
                    $shortcodes[] = substr($c, 0, 2);
                }
                foreach ($browserCodes as $bcode) {
                    if (in_array($bcode, $shortcodes)) {
                        $lenCodes = count($codes);
                        for ($i = 0; $i < $lenCodes; $i++) {
                            if (strcmp($bcode, $shortcodes[$i]) == 0) {
                                $this->locale_code = $codes[$i];
                                break 2;
                            }
                        }
                    }
                }
            }
        }
        //weird to be adding filters here, but translations weren't happening consistently when it was in setUp
        //@TODO: check if this oddity is due to setting the priority high
        $translatableElements = unserialize(get_option('multilanguage_elements'));
        if (is_array($translatableElements)) {
            foreach ($translatableElements as $elementSet=>$elements) {
                foreach ($elements as $element) {
                    add_filter(array('Display', 'Item', $elementSet, $element), array($this, 'translate'), 1);
                    add_filter(array('ElementInput', 'Item', $elementSet, $element), array($this, 'translateField'), 1);
                    add_filter(array('Display', 'Collection', $elementSet, $element), array($this, 'translate'), 1);
                    add_filter(array('ElementInput', 'Collection', $elementSet, $element), array($this, 'translateField'), 1);
                    add_filter(array('Display', 'File', $elementSet, $element), array($this, 'translate'), 1);
                    add_filter(array('ElementInput', 'File', $elementSet, $element), array($this, 'translateField'), 1);
                }
            }
        }
        return $this->locale_code;
    }

    public function translateField($components, $args)
    {
        $record = $args['record'];
        $element = $args['element'];
        $type = get_class($record);
        $languages = unserialize(get_option('multilanguage_language_codes'));
        $html = __('Translate to: ');
        foreach ($languages as $code) {
            $html .= " <li data-element-id='{$element->id}' data-code='$code' data-record-id='{$record->id}' data-record-type='{$type}' class='multilanguage-code'>$code</li>";
        }
        $components['form_controls'] .= "<ul class='multilanguage' >$html</ul>";
        return $components;
    }

    public function translate($translateText, $args)
    {
        $db = $this->_db;
        $record = $args['record'];
        //since I'm being cheap and not differentiating Items vs Collections
        //or any other ActsAsElementText up above in the filter definitions (themselves weird),
        //I risk getting null values here
        //after the filter happens for 'element_text'
        if (!empty($args['element_text'])) {
            $elementText = $args['element_text'];

            $elementId = $elementText->element_id;

            $translation = $db->getTable('MultilanguageTranslation')
                ->getTranslation($record->id, get_class($record), $elementId, $this->locale_code, $translateText);
            if ($translation) {
                $translateText = $translation->translation;
            }
        }
        return $translateText;
    }

    protected function getLocaleFromGetOrSession($locale)
    {
        $session = new Zend_Session_Namespace;

        if (isset($_GET['lang'])) {
            $locale = html_escape($_GET['lang']);
            $session->lang = $locale;
        } elseif (isset($session->lang)) {
            $locale = $session->lang;
        }

        return $locale;
    }

    protected function getDefaultLocaleCode()
    {
        if (plugin_is_active('Locale')) {
            $plugin = new LocalePlugin();
            return $plugin->filterLocale(null);
        }

        $defaultCodes = Zend_Locale::getDefault();
        return current(array_keys($defaultCodes));
    }

    protected function getUserLang($user, $defaultCode = null)
    {
        $prefLanguages = get_db()->getTable('MultilanguageUserLanguage')
            ->findBy(array('user_id' => $user->id));
        $userLang = empty($prefLanguages) ? $defaultCode : $prefLanguages[0]->lang;
        return $userLang;
    }

    /**
     * Get the list of available language code.
     *
     * @param string $lang Add this language code.
     * @return array
     */
    protected function prepareLocaleCodes($lang = null)
    {
        $availableCodes = array();
        $defaultCode = $this->getDefaultLocaleCode();

        if (!isset($lang)) {
            $lang = $defaultCode;
        }

        $codes = unserialize(get_option('multilanguage_language_codes')) ?: array();
        array_unshift($codes, $defaultCode);
        foreach ($codes as $code) {
            $parts = explode('_', $code);
            if (isset($parts[1])) {
                $langCode = $parts[0];
                $regionCode = $parts[1];
                try {
                    $language = Zend_Locale::getTranslation($langCode, 'language', $langCode);
                    $region = Zend_Locale::getTranslation($regionCode, 'territory', $langCode);
                } catch (Exception $e) {
                    $language = $langCode;
                    $region = '';
                }
            } else {
                try {
                    $language = Zend_Locale::getTranslation($code, 'language', $code);
                } catch (Exception $e) {
                    $language = $code;
                }
                $region = '';
            }
            if ($region != '') {
                $region = " - $region";
            }
            $availableCodes[$code] = ucfirst($language) . $region . " ($code)";
        }

        return $availableCodes;
    }
}
