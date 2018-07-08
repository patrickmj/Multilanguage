<?php
class Multilanguage_TranslationsController extends Omeka_Controller_AbstractActionController
{
    public function translateAction()
    {
        $db = get_db();
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $this->_helper->json(false);
            return;
        }

        $post = $request->getParams();
        if (empty($post['record_id'])) {
            $this->_helper->json(false);
            return;
        }

        if (isset($post['translation_id'])) {
            $translation = $db->getTable('MultilanguageTranslation')
                ->find( ['translation_id']);
        } else {
            $translation = new MultilanguageTranslation;
        }

        $translation->element_id = $post['element_id'];
        $translation->record_id = $post['record_id'];
        $translation->record_type = $post['record_type'];
        $translation->text = $post['text'];
        $translation->translation = $post['translation'];
        $translation->locale_code = $post['locale_code'];
        $translation->save();
        $this->_helper->json(true);
    }

    public function translationAction()
    {
        $db = get_db();

        if (isset($_GET['text']) && isset($_GET['record_id'])) {
            $translation = $db->getTable('MultilanguageTranslation')
                ->getTranslation(
                    $_GET['record_id'],
                    $_GET['record_type'],
                    $_GET['element_id'],
                    $_GET['locale_code'],
                    $_GET['text']
                );
            if ($translation) {
                $translation = $translation->toArray();
            } else {
                $translation = array('translation' => '');
            }
        } else {
            $translation = array('translation' => '');
        }

        $this->_helper->json($translation);
    }

    public function contentLanguageAction()
    {
        $db = get_db();
        if (plugin_is_active('ExhibitBuilder')) {
            $exhibits = $db->getTable('Exhibit')->findAll();
            $this->view->exhibits = $exhibits;
        }

        if (plugin_is_active('SimplePages')) {
            $simplePages = $db->getTable('SimplePagesPage')->findAll();
            $this->view->simple_pages = $simplePages;
        }

        if ($this->getRequest()->isPost()) {
            $exhibitLangs = $this->getParam('exhibits');
            foreach ($exhibitLangs as $recordId => $lang) {
                $this->updateContentLang('Exhibit', $recordId, $lang);
            }
            $simplePages = $this->getParam('simple_pages_page');
            foreach ($simplePages as $recordId => $lang) {
                $this->updateContentLang('SimplePagesPage', $recordId, $lang);
            }
        }
    }

    protected function updateContentLang($recordType, $recordId, $lang)
    {
        $contentLanguage = $this->fetchContentLanguageRecord($recordType, $recordId);
        $contentLanguage->record_type = $recordType;
        $contentLanguage->record_id = $recordId;
        $contentLanguage->lang = $lang;
        $contentLanguage->save();
    }

    protected function fetchContentLanguageRecord($recordType, $recordId)
    {
        $table = $this->_helper->db->getTable('MultilanguageContentLanguage');
        $select = $table->getSelectForFindBy(
            array(
                'record_type' => $recordType,
                'record_id' => $recordId,
            )
        );
        $contentLanguage = $table->fetchObject($select);
        if ($contentLanguage) {
            return $contentLanguage;
        } else {
            return new MultilanguageContentLanguage;
        }
    }
}
