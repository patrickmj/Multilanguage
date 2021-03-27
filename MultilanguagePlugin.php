<?php

require_once __DIR__ . '/helpers/functions.php';

class MultilanguagePlugin extends Omeka_Plugin_AbstractPlugin
{
	protected $_hooks = array(
		'initialize',
		'install',
		'uninstall',
		'upgrade',
		'config',
		'config_form',
		'define_routes',
		'admin_head',
		'admin_footer',
		'public_head',
		'public_header',
		// 'admin_users_browse_each',
		'users_form',
		'after_save_user',
		// No hook for simple page form.
		'after_save_simple_pages_page',
		// No hook for exhibit form.
		'after_save_exhibit',
		'after_save_exhibit_page',
		'after_delete_simple_pages_page',
		'after_delete_exhibit',
		'after_delete_exhibit_page',
		// No hook to browse exhibit page: the language is the exhibit one.
		'exhibits_browse_sql',
		'simple_pages_pages_browse_sql',
	);

	protected $_filters = array(
		// Note: the filter locale adds some filters.
		'locale',
		'admin_navigation_global',
		'guest_user_links',
	);

	protected $_options = array(
		'multilanguage_append_header' => true,
		'multilanguage_display_mode' => '',
		// The value is: serialize(array('en'))
		'multilanguage_locales' => 'a:1:{i:0;s:2:"en";}',
		'multilanguage_locales_admin' => 'a:1:{i:0;s:2:"en";}',
		'multilanguage_elements' => 'a:1:{s:11:"Dublin Core";a:3:{i:0;s:5:"Title";i:1;s:11:"Description";i:2;s:7:"Subject";}}',
	);

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
CREATE TABLE IF NOT EXISTS $db->MultilanguageRelatedRecord (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `record_type` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  `related_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `record_type_record_id` (`record_type`(190),`record_id`),
  KEY `record_type_related_id` (`record_type`(190),`related_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
		";

		$db->query($sql);

		$sql = "
CREATE TABLE IF NOT EXISTS $db->MultilanguageUserLanguage (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `lang` tinytext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `$db->User` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
		";

		$db->query($sql);

		$this->_installOptions();
	}

	public function hookUninstall()
	{
		$db = $this->_db;
		$sql = "DROP TABLE $db->MultilanguageTranslation ";
		$db->query($sql);

		$sql = "DROP TABLE $db->MultilanguageContentLanguage ";
		$db->query($sql);

		$sql = "DROP TABLE $db->MultilanguageRelatedRecord";
		$db->query($sql);

		$sql = "DROP TABLE $db->MultilanguageUserLanguage";
		$db->query($sql);

		$this->_uninstallOptions();
	}

	public function hookUpgrade($args)
	{
		$oldVersion = $args['old_version'];
		$newVersion = $args['new_version'];

		if (version_compare($oldVersion, '1.1', '<')) {
			$db = $this->_db;

			// Remove deleted records.
			$sql = "
DELETE FROM $db->MultilanguageUserLanguage
WHERE user_id NOT IN (SELECT id FROM $db->User);
			";
			$db->query($sql);

			// Add a automatic deletion for users.
			$sql = "
ALTER TABLE `$db->MultilanguageUserLanguage`
ADD FOREIGN KEY (`user_id`) REFERENCES `omeka_users` (`id`) ON DELETE CASCADE;
			";
			$db->query($sql);
		}

		if (version_compare($oldVersion, '1.2', '<')) {
			set_option('multilanguage_append_header', $this->_options ['multilanguage_append_header']);
			set_option('multilanguage_locales', get_option('multilanguage_language_codes'));
			set_option('multilanguage_locales_admin', get_option('multilanguage_language_codes'));
			delete_option('multilanguage_language_codes');
		}

		if (version_compare($oldVersion, '1.3', '<')) {
			$db = $this->_db;

			$sql = "
CREATE TABLE IF NOT EXISTS $db->MultilanguageRelatedRecord (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `record_type` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  `related_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `record_type_record_id` (`record_type`(190),`record_id`),
  KEY `record_type_related_id` (`record_type`(190),`related_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
			";
			$db->query($sql);
		}

		if (version_compare($oldVersion, '1.4', '<')) {
			// Set language of all exhibit pages to the exhibit’s one.
			$exhibits = get_records('Exhibit', array(), 0);
			foreach ($exhibits as $exhibit) {
				$contentLanguage = $this->localeCodeRecord($exhibit);
				if ($contentLanguage) {
					$this->updateOrDeleteExhibitPagesLang($exhibit->id, $contentLanguage->lang);
				}
			}
		}

		// TODO Remove deleted records from MultilanguageContentLanguage.
	}

	public function hookConfigForm()
	{
		$view = get_view();

		$locales = get_option('multilanguage_locales');
		$locales = $locales ? unserialize($locales) : array();

		$localesAdmin = get_option('multilanguage_locales_admin');
		$localesAdmin = $localesAdmin ? unserialize($localesAdmin) : array();

		$files = scandir(BASE_DIR . '/application/languages');
		foreach ($files as $file) {
			if (strpos($file, '.mo') !== false) {
				$code = str_replace('.mo', '', $file);
				$codes[$code] = locale_human($code) . " ($code)";
			}
		}
		// Set default "en" and instead of "en_US" to avoid issues.
		$codes['en'] = ucfirst(Zend_Locale::getTranslation('en', 'language')) . ' (en)';
		asort($codes);

		$translatableElements = get_option('multilanguage_elements');
		$translatableElements = $translatableElements ? unserialize($translatableElements) : array();
		$elTable = get_db()->getTable('Element');
		$translatableElementIds = array();
		foreach ($translatableElements as $elSet => $elements) {
			foreach ($elements as $element) {
				$elObject = $elTable->findByElementSetNameAndElementName($elSet, $element);
				$translatableElementIds[] = $elObject->id;
			}
		}

		echo $view->partial('plugins/multilanguage-config-form.php', array(
			'locales' => $locales,
			'localesAdmin' => $localesAdmin,
			'codes' => $codes,
			'translatableElementIds' => $translatableElementIds,
		));
	}

	public function hookConfig($args)
	{
		$post = $args['post'];

		$elements = array();
		$elTable = get_db()->getTable('Element');
		foreach ($post['multilanguage_elements'] as $elId) {
			$element = $elTable->find($elId);
			$elSet = $element->getElementSet();
			if (!array_key_exists($elSet->name, $elements)) {
				$elements[$elSet->name] = array();
			}
			$elements[$elSet->name][] = $element->name;
		}
		$post['multilanguage_elements'] = $elements;

		if (!empty($post['multilanguage_translations_reset'])) {
			$cache = Zend_Registry::get('Zend_Translate');
			$cache::clearCache();
		}

		$post = array_intersect_key($post, $this->_options);
		foreach ($post as $optionKey => $optionValue) {
			if (isset($post[$optionKey])) {
				switch ($optionKey) {
					case 'multilanguage_locales':
					case 'multilanguage_locales_admin':
					case 'multilanguage_elements':
						$post[$optionKey] = serialize($post[$optionKey]);
						break;
				}
				set_option($optionKey, $post[$optionKey]);
			}
		}
	}

	public function hookDefineRoutes($args)
	{
		$router = $args['router'];
		$router->addRoute(
			'multilanguage-setlocale',
			new Zend_Controller_Router_Route(
				'setlocale',
				array(
					'module' => 'multilanguage',
					'controller' => 'setlocale',
					'action' => 'index',
				)
			)
		);
	}

	public function hookAdminHead()
	{
		queue_css_file('multilanguage');
		queue_js_file('multilanguage');
		$enabledLocales = unserialize(get_option('multilanguage_locales_admin'));
		if ($enabledLocales) {
			queue_css_file('flag-icon-css/css/flag-icon.min');
		}
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

	public function hookPublicHead()
	{
		queue_css_file('locale-switcher');
		queue_css_file('flag-icon-css/css/flag-icon.min');
	}

	public function hookPublicHeader($args)
	{
		if (get_option('multilanguage_append_header')) {
			echo $args['view']->localeSwitcher();
		}
	}

	public function hookUsersForm($args)
	{
		$form = $args['form'];
		$user = $args['user'];

		$defaultCode = $this->getDefaultLocaleCode();
		$userLang = ($user && $user->id) ? $this->getUserLang($user, $defaultCode) : $defaultCode;
		$availableCodes = $this->prepareLocaleCodes($userLang);

		$form->addElement('select', 'multilanguage_locale_code', array(
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
		if (isset($post['multilanguage_locale_code'])) {
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
			$userLang = $post['multilanguage_locale_code'];
			$prefLanguage->lang = isset($availableCodes[$userLang])
				? $userLang
				: $defaultCode;
			$prefLanguage->save();
		}
	}

	public function hookAfterSaveSimplePagesPage($args)
	{
		$this->saveMultilangueRecord($args);
	}

	public function hookAfterSaveExhibit($args)
	{
		$this->saveMultilangueRecord($args);

		// Set language of all exhibit pages to the exhibit’s one.
		$exhibit = $args['record'];
		$contentLanguage = $this->localeCodeRecord($exhibit);
		$lang = $contentLanguage->lang ? $contentLanguage->lang : null;
		$this->updateOrDeleteExhibitPagesLang($exhibit->id, $lang);
	}

	public function hookAfterSaveExhibitPage($args)
	{
		// Force the lang of the exhibit page.
		$record = $args['record'];
		$exhibit = get_record_by_id('Exhibit', $record->exhibit_id);
		$contentLanguage = $this->localeCodeRecord($exhibit);
		$lang = $contentLanguage->lang ? $contentLanguage->lang : null;
		$post = $args['post'];
		$post['locale_code'] = $lang;
		$args['post'] = $post;
		$this->saveMultilangueRecord($args);
	}

	public function hookAfterDeleteSimplePagesPage($args)
	{
		$this->deleteMultilangueRecord($args);
	}

	public function hookAfterDeleteExhibit($args)
	{
		$this->deleteMultilangueRecord($args);
	}

	public function hookAfterDeleteExhibitPage($args)
	{
		$this->deleteMultilangueRecord($args);
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

	public function filterLocale($locale)
	{
		$enabledLocales = is_admin_theme()
			? unserialize(get_option('multilanguage_locales_admin'))
			: unserialize(get_option('multilanguage_locales'));

		// Get the locale from url argument if any, else session, else user, else browser.
		$newLocale = null;

		if (empty($enabledLocales)) {
			$newLocale = $this->getDefaultLocaleCode();
		} elseif (isset($_GET['lang'])
			&& ($getLocale = html_escape($_GET['lang']))
			&& in_array($getLocale, $enabledLocales)
		) {
			$newLocale = $getLocale;
		} else {
			// Make sure the session has been configured properly
			Zend_Registry::get('bootstrap')->bootstrap('Session');
			$session = new Zend_Session_Namespace('locale');

			if ($session->locale && in_array($session->locale, $enabledLocales)) {
				$newLocale = $session->locale;
			} else {
				$user = current_user();
				if ($user) {
					$prefLanguages = $this->_db->getTable('MultilanguageUserLanguage')
						->findBy(array('user_id' => $user->id));
					if ($prefLanguages) {
						$userPrefLanguage = reset($prefLanguages);
						$newLocale = $userPrefLanguage->lang;
					}
				}

				// Get the locale from the browser.
				// TODO Get the locale from the browser: to be simplified.
				if (empty($newLocale)) {
					$defaultCode = $this->getDefaultLocaleCode();
					$codes = $enabledLocales;
					//dump the site's default code to the end as a fallback
					$codes[] = $defaultCode;
					$browserCodes = array_keys(Zend_Locale::getBrowser());
					$match = false;
					foreach ($browserCodes as $browserCode) {
						if (in_array($browserCode, $codes)) {
							$newLocale = $browserCode;
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
										$newLocale = $codes[$i];
										break 2;
									}
								}
							}
						}
					}

					// From plugin Locale Switcher.
					if (empty($newLocale)) {
						if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
							$languages = array_map(function ($l) {
								list($lang, $q) = array_pad(explode(';', $l), 2, null);
								return str_replace('-', '_', trim($lang));
							}, explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']));

							foreach ($languages as $language) {
								if (in_array($language, $enabledLocales)) {
									$newLocale = $language;
									break;
								}
							}
						}

						if (empty($newLocale)) {
							$newLocale = $defaultCode;
						}
					}
				}
			}
		}

		$this->locale_code = $newLocale;

		//weird to be adding filters here, but translations weren't happening consistently when it was in setUp
		//@TODO: check if this oddity is due to setting the priority high
		$this->addFilterElements();

		return $this->locale_code;
	}

	public function filterAdminNavigationGlobal($nav)
	{
		$displayMode = get_option('multilanguage_display_mode');
		$enabledLocales = unserialize(get_option('multilanguage_locales_admin'));
		if (empty($enabledLocales)) {
			return $nav;
		}

		$currentLocale = Zend_Registry::get('bootstrap')->getResource('Locale')->toString();
		$currentUrl = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
		$view = get_view();

		foreach ($enabledLocales as $locale) {
			$language = Zend_Locale::getTranslation(substr($locale, 0, 2), 'language');
			$country = $view->localeToCountry($locale);

			$url = url('setlocale', array('locale' => $locale, 'redirect' => $currentUrl));
			$title = locale_human($locale);
			$class = '';
			$label = null;
			
			switch ($displayMode) {
				case 'code':
					$label = '[' . strtolower($country) . ']';
					break;
				case 'name':
					$label = locale_human($locale, true);
					break;
				default:
					$class = 'flag-icon flag-icon-' . strtolower($country);
			}
			
			if ($locale === $currentLocale) {
				$class .= ' active';
			}
			$link = array(
				'label' => $label,
				'uri' => $url,
				'class' => $class,
				'title' => $title,
			);
			$nav[] = $link;
		}

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

	protected function addFilterElements()
	{
		$translatableElements = unserialize(get_option('multilanguage_elements'));
		if (is_array($translatableElements)) {
			foreach ($translatableElements as $elementSet => $elements) {
				foreach ($elements as $element) {
					add_filter(array('Display', 'Item', $elementSet, $element), array($this, 'filterTranslate'), 1);
					add_filter(array('ElementInput', 'Item', $elementSet, $element), array($this, 'filtertranslateField'), 1);
					add_filter(array('Display', 'Collection', $elementSet, $element), array($this, 'filterTranslate'), 1);
					add_filter(array('ElementInput', 'Collection', $elementSet, $element), array($this, 'filterTranslateField'), 1);
					add_filter(array('Display', 'File', $elementSet, $element), array($this, 'filterTranslate'), 1);
					add_filter(array('ElementInput', 'File', $elementSet, $element), array($this, 'filterTranslateField'), 1);
				}
			}
		}
	}

	public function filterTranslateField($components, $args)
	{
		$record = $args['record'];
		$element = $args['element'];
		$type = get_class($record);
		$languages = unserialize(get_option('multilanguage_locales'));
		$html = __('Translate to:');
		foreach ($languages as $code) {
			$html .= sprintf(
				' <li data-element-id="%s" data-code="%s" data-record-id="%s" data-record-type="%s" class="multilanguage-code">%s</li>',
				$element->id,
				$code,
				$record->id,
				$type,
				$code
			);
		}
		$components['form_controls'] .= '<ul class="multilanguage">' . $html . '</ul>';
		return $components;
	}

	public function filterTranslate($translateText, $args)
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

		$codes = unserialize(get_option('multilanguage_locales')) ?: array();
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

	protected function saveMultilangueRecord($args)
	{
		$post = $args['post'];
		$record = $args['record'];

		if (array_key_exists('locale_code', $post)) {
			$this->processLocaleCodeRecord($record, $post);
		}

		if (array_key_exists('related_records', $post)) {
			$this->processRelatedRecordsRecord($record, $post);
		}
	}

	protected function processLocaleCodeRecord($record, $post)
	{
		$localeCode = $post['locale_code'];

		// Remove the locale code of the record if wanted and if any.
		if (empty($localeCode)) {
			if ($post['insert']) {
				return;
			}
			$localeCodeRecord = $this->localeCodeRecord($record);
			if ($localeCodeRecord) {
				$localeCodeRecord->delete();
				return;
			}
		}

		// Check the locale code.
		$availableCodes = $this->prepareLocaleCodes();
		if (!isset($availableCodes[$localeCode])) {
			return;
		}

		$localeCodeRecord = $this->localeCodeRecord($record);

		// Don't update the locale code if unchanged.
		if ($localeCodeRecord && $localeCodeRecord->lang === $localeCode) {
			return;
		}

		if (empty($localeCodeRecord)) {
			$localeCodeRecord = new MultilanguageContentLanguage;
			$localeCodeRecord->record_type = get_class($record);
			$localeCodeRecord->record_id = (int) $record->id;
		}

		$localeCodeRecord->lang = $localeCode;
		$localeCodeRecord->save();
	}

	protected function processRelatedRecordsRecord($record, $post)
	{
		// No relation can be set for a new record.
		if ($post['insert']) {
			return;
		}

		$relatedRecordIds = array_filter(array_map('intval', $post['related_records']));
		sort($relatedRecordIds);

		$recordType = get_class($record);
		$recordId = (int) $record->id;

		// To avoid multiple checks, all related records are removed and saved.
		// Anyway, they are a few usually.
		$relatedRecords = $this->relatedRecords($record, null, true);
		foreach ($relatedRecords as $relatedRecord) {
			$relatedRecord->delete();
		}

		// All related records or new related records should be removed too.
		// TODO Just remove the related record from the list of its related records.
		foreach ($relatedRecordIds as $relatedRecordId) {
			$relatedRecords = $this->relatedRecords($recordType, $relatedRecordId, true);
			foreach ($relatedRecords as $relatedRecord) {
				$relatedRecord->delete();
			}
		}

		// Create remaining (new) related records.
		foreach ($relatedRecordIds as $relatedRecordId) {
			if ($relatedRecordId == $recordId) {
				continue;
			}
			$relatedRecord = new MultilanguageRelatedRecord;
			$relatedRecord->record_type = $recordType;
			// Keep record id lower than related id to simplify search.
			if ($recordId < $relatedRecordId) {
				$relatedRecord->record_id = $recordId;
				$relatedRecord->related_id = $relatedRecordId;
			} else {
				$relatedRecord->record_id = $relatedRecordId;
				$relatedRecord->related_id = $recordId;
			}
			$relatedRecord->save();
		}
	}

	protected function deleteMultilangueRecord($args)
	{
		$record = $args['record'];
		$localeCodeRecord = $this->localeCodeRecord($record);
		if ($localeCodeRecord) {
			$localeCodeRecord->delete();
		}
		$relatedRecords = $this->relatedRecords($record, null, true);
		foreach ($relatedRecords as $relatedRecord) {
			$relatedRecord->delete();
		}
	}

	/**
	 * Get the locale code of a record, if any.
	 *
	 * @param Omeka_Record_AbstractRecord $record
	 * @return MultilanguageContentLanguage|null
	 */
	protected function localeCodeRecord(Omeka_Record_AbstractRecord $record)
	{
		$table = get_db()->getTable('MultilanguageContentLanguage');
		$select = $table->getSelectForFindBy(
			array(
				'record_type' => get_class($record),
				'record_id' => $record->id,
			)
		);
		$contentLanguage = $table->fetchObject($select);
		return $contentLanguage;
	}

	/**
	 * Get the related records of a record, if any.
	 *
	 * @param Omeka_Record_AbstractRecord|string $record Record or record type.
	 * @param int $recordId
	 * @param bool $included Include the specified record to the list.
	 * @return MultilanguageRelatedRecord[]|null
	 */
	protected function relatedRecords($record, $recordId = null, $included = false)
	{
		if (is_object($record)) {
			$recordType = get_class($record);
			$recordId = $record->id;
		} else {
			$recordType = $record;
		}

		$records = get_db()->getTable('MultilanguageRelatedRecord')
			->findRelatedRecords($recordType, $recordId, $included);
		return $records;
	}

	/**
	 * Force the language of all pages to the exhibit's one.
	 *
	 * @param int $exhibitId
	 * @param string|null $lang
	 */
	protected function updateOrDeleteExhibitPagesLang($exhibitId, $lang)
	{
		$db = $this->_db;
		if (empty($lang)) {
			$sql = "
DELETE FROM `{$db->MultilanguageContentLanguage}`
WHERE `id` in (
	SELECT DISTINCT `id` FROM `{$db->ExhibitPage}`
	WHERE `exhibit_id` = $exhibitId;
);
			";
		} else {
			$sql = "
INSERT INTO `{$db->MultilanguageContentLanguage}` (`record_type`, `record_id`, `lang`)
SELECT 'ExhibitPage', `id`, '$lang'
FROM `{$db->ExhibitPage}`
WHERE `exhibit_id` = $exhibitId;
			";
		}
		$db->query($sql);
	}
}
