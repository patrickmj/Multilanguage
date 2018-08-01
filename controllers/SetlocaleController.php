<?php

class Multilanguage_SetlocaleController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $locale = $this->getParam('locale');

        if (Zend_Locale::isLocale($locale)) {
            $session = new Zend_Session_Namespace('locale');
            $session->locale = $locale;
        }

        // In public front-end, set locale implies interface and content.
        $url = null;

        // Check if the url has a related record (simple pages, exhibit…) in the
        // specified locale, else do a simple redirect for the interface only.
        $request = $this->getRequest();
        $recordType = $request->getParam('record_type');
        switch ($recordType) {
            case 'Exhibit':
                $recordId = $request->getParam('id');
                $record = get_db()->getTable('MultilanguageRelatedRecord')
                    ->findRelatedSourceRecordForLocale($recordType, $recordId, $locale);
                if ($record) {
                    $url = exhibit_builder_exhibit_uri($record);
                }
                break;

            case 'SimplePagesPage':
                $recordId = $request->getParam('id');
                $record = get_db()->getTable('MultilanguageRelatedRecord')
                    ->findRelatedSourceRecordForLocale($recordType, $recordId, $locale);
                if ($record) {
                    $url = record_url($record);
                }
                break;
        }

        if (empty($url)) {
            $referer = $request->getHeader('Referer');
            $url = $this->getParam('redirect', $referer) ?: '/';
        }

        $this->getHelper('Redirector')->setPrependBase(false)->goToUrl($url);
    }
}
