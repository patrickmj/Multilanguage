<?php

/**
 * This class is used mainly by the plugin Guest user.
 */
class Multilanguage_UserLanguageController extends Omeka_Controller_AbstractActionController
{
    public function init()
    {
        $this->_helper->db->setDefaultModelName('MultilanguageUserLanguage');
    }

    public function userLanguageAction()
    {
        $defaultCode = $this->getDefaultLocaleCode();
        $availableCodes = $this->prepareLocaleCodes($defaultCode);
        $this->view->defaultCode = $defaultCode;
        $this->view->availableCodes = $availableCodes;
        $this->view->lang = $defaultCode;

        $user = current_user();
        if ($user) {
            $prefLanguages = $this->_helper->db->getTable('MultilanguageUserLanguage')
                ->findBy(array('user_id' => $user->id));
            if (empty($prefLanguages)) {
                $this->view->lang = $defaultCode;
            } else {
                $prefLanguage = $prefLanguages[0];
                $this->view->lang = $prefLanguage->lang;
            }
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            if (! isset($prefLanguage)) {
                $prefLanguage = new MultilanguageUserLanguage;
                $prefLanguage->user_id = $user->id;
            }
            $userLang = $request->getParam('multilanguage_locale_code');
            $prefLanguage->lang = isset($availableCodes[$userLang])
                ? $userLang
                : $defaultCode;
            $prefLanguage->save();
            $this->view->lang = $prefLanguage->lang;
        }
    }

    /* Next methods are duplicated in MultilanguagePlugin. */

    protected function getDefaultLocaleCode()
    {
        if (plugin_is_active('Locale')) {
            $plugin = new LocalePlugin();
            return $plugin->filterLocale(null);
        }

        $defaultCodes = Zend_Locale::getDefault();
        return current(array_keys($defaultCodes));
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
}
