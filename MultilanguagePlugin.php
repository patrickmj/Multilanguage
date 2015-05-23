<?php
class MultilanguagePlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
            'install',
            'uninstall',
            'config',
            'config_form',
            'admin_head',
            'admin_footer',
            'exhibits_browse_sql',
            'simple_pages_pages_browse_sql'
            );
    
    protected $_filters = array(
            'locale',
            'guest_user_links',
            'admin_navigation_main',
            );
    
    protected $_translationTable = null;
    
    protected $locale_code;
    
    
    public function hookExhibitsBrowseSql($args)
    {
        $this->modelBrowseSql($args, 'Exhibit');
    }

    public function hookSimplePagesPagesBrowseSql($args)
    {
        $this->modelBrowseSql($args, 'SimplePagesPage');
    }
    
    public function modelBrowseSql($args, $model)
    {
        if (! is_admin_theme()) {
            $select = $args['select'];
            $db = get_db();
            $alias = $db->getTable('MultilanguageContentLanguage')->getTableAlias();
            $spAlias = $db->getTable($model)->getTableAlias();
            $select->join(array($alias => $db->MultilanguageContentLanguage),
                            "$alias.record_id = $spAlias.id", array());
            $select->where("$alias.record_type = ?", $model);
            $select->where("$alias.lang = ?", $this->locale_code);
        }
    }
    public function filterGuestUserLinks($links)
    {
        $links['Multilanguage'] = array('label' => __('Preferred Language'), 'uri' => url('multilanguage/user-language/user-language'));
        return $links;
    }
    
    public function filterAdminNavigationMain($nav)
    {
        $nav['Multilanguage'] = array('label' => __('Preferred Language'), 'uri' => url('multilanguage/user-language/user-language'));
        $nav['Multilanguage_content'] = array(
                'label' => __('Multilanguage Content'),
                'uri'   => url('multilanguage/translations/content-language')
                );
        return $nav;
    }
    
    public function filterLocale($locale)
    {
        
        $this->_translationTable = $this->_db->getTable('MultilanguageTranslation');
        $user = current_user();
        $userPrefLanguageCode = false;
        $userPrefLanguage = false;
        if ($user) {
            debug('user');
            $prefLanguages = $this->_db->getTable('MultilanguageUserLanguage')->findBy(array('user_id' => $user->id));
            if ( ! empty($prefLanguages)) {
                debug('wtf');
                $userPrefLanguage = $prefLanguages[0];
                $userPrefLanguageCode = $userPrefLanguage->lang;
                $this->locale_code = $userPrefLanguageCode;
            }
        }
        if (! $userPrefLanguageCode) {
            debug('no user code');
            $codes = unserialize( get_option('multilanguage_language_codes') );
            $browserCodes = array_keys(Zend_Locale::getBrowser());
            $defaultCodes = Zend_Locale::getDefault();
            $this->locale_code = current(array_keys($defaultCodes));
            foreach ($browserCodes as $browserCode) {
                if (in_array($browserCode, $codes)) {
                    $this->locale_code = $browserCode;
                    continue;
                }
            }
        }

        //weird to be adding filters here, but translations weren't happening consistently when it was in setUp
        
        $translatableElements = unserialize(get_option('multilanguage_elements'));
        if(is_array($translatableElements)) {
            foreach($translatableElements as $elementSet=>$elements) {
                foreach($elements as $element) {
                    add_filter(array('Display', 'Item', $elementSet, $element), array($this, 'translate'), 1);
                    add_filter(array('ElementInput', 'Item', $elementSet, $element), array($this, 'translateField'), 1);
                    add_filter(array('Display', 'Collection', $elementSet, $element), array($this, 'translate'), 1);
                    add_filter(array('ElementInput', 'Collection', $elementSet, $element), array($this, 'translateField'), 1);
                    
                }
            }
        }
        
        return $this->locale_code;
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
    
    public function hookAdminHead()
    {
        queue_css_file('multilanguage');
        queue_js_file('multilanguage');
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

    public function hookConfig($args)
    {
        $post = $args['post'];
        $elements = array();
        $elTable = get_db()->getTable('Element');
        foreach($post['element_sets'] as $elId) {
            $element = $elTable->find($elId);
            $elSet = $element->getElementSet();
            if(!array_key_exists($elSet->name, $elements)) {
                $elements[$elSet->name] = array();
            }
            $elements[$elSet->name][] = $element->name;
        }
        set_option('multilanguage_elements', serialize($elements));
        set_option('multilanguage_language_codes', serialize($post['multilanguage_language_codes']));
    }

    public function hookConfigForm()
    {
        include('config_form.php');
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
        //or any other ActsAsElementText, I risk getting null values here
        //after the filter happens for 'element_text'
        if (! empty($args['element_text'])) {
            $elementText = $args['element_text'];
        
            $elementId = $elementText->element_id;
            
            $translation = $db->getTable('MultilanguageTranslation')->getTranslation($record->id, get_class($record), $elementId, $this->locale_code, $translateText);
            if ($translation) {
                $translateText = $translation->translation;
            }
        }
        return $translateText;
    }
}