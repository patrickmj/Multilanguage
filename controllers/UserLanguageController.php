<?php

class Multilanguage_UserLanguageController extends Omeka_Controller_AbstractActionController
{
    public function init()
    {
        $this->_helper->db->setDefaultModelName('MultilanguageUserLanguage');
    }
    
    public function userLanguageAction()
    {
        $user = current_user();
        
        if (! $user) {
            //go away
        } else {
            $prefLanguages = $this->_helper->db->getTable('MultilanguageUserLanguage')->findBy(array('user_id' => $user->id));
            if (empty($prefLanguages)) {
                $this->view->lang = null;
            } else {
                $prefLanguage = $prefLanguages[0];
                $this->view->lang = $prefLanguage->lang;
            }
        }

        if ($this->getRequest()->isPost()) {
            if (! isset($prefLanguage) ) {
                $prefLanguage = new MultilanguageUserLanguage;
                $prefLanguage->user_id = $user->id;
            }
            $prefLanguage->lang = $_POST['multilanguage_language_code'];
            $prefLanguage->save();
            $this->view->lang = $prefLanguage->lang;
        }
    }
}