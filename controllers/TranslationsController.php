<?php
class Multilanguage_TranslationsController extends Omeka_Controller_AbstractActionController
{
    public function translateAction()
    {
        $db = get_db();
        if ($this->getRequest()->isPost()) {
            if (isset($_POST['translation_id'])) {
                $translation = $db->
                    getTable('MultilanguageTranslation')->find($_POST['translation_id']);
            } else {
                $translation = new MultilanguageTranslation;
            }
            
            $translation->element_id = $_POST['element_id'];
            $translation->record_id = $_POST['record_id'];
            $translation->record_type = $_POST['record_type'];
            $translation->text = $_POST['text'];
            $translation->translation = $_POST['translation'];
            $translation->locale_code = $_POST['locale_code'];
            $translation->save();
            $this->_helper->json('');
        }
    }
    
    public function translationAction()
    {
        $db = get_db();
        
        if (isset($_GET['text'])) {
            $translation = $db->
                getTable('MultilanguageTranslation')
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
            foreach ($exhibitLangs as $recordId=>$lang) {
                $this->updateContentLang('Exhibit', $recordId, $lang);
            }
            $simplePages = $this->getParam('simple_pages_page');
            foreach ($simplePages as $recordId=>$lang) {
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
                array('record_type' => $recordType,
                      'record_id'   => $recordId,
                ));
        $contentLanguage = $table->fetchObject($select);
        if ($contentLanguage) {
            return $contentLanguage;
        } else {
            return new MultilanguageContentLanguage;
        }
    }
}