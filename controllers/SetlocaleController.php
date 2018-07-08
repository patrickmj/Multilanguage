<?php

class Multilanguage_SetlocaleController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $locale = $this->getParam('locale');

        if (Zend_Locale::isLocale($locale)) {
            $session = new Zend_Session_Namespace('locale');
            $session->locale = $this->getParam('locale');
        }

        $referer = $this->getRequest()->getHeader('Referer');
        $url = $this->getParam('redirect', $referer) ?: '/';
        $this->getHelper('Redirector')->setPrependBase(false)->goToUrl($url);
    }
}
