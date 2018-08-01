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

    public function localeCodeRecordAction()
    {
        $recordType = $_GET['record_type'];
        $recordId = $_GET['record_id'];
        $contentLanguage = $this->fetchContentLanguageRecord($recordType, $recordId);
        $result = empty($contentLanguage) ? null : $contentLanguage->lang;
        $this->_helper->json($result);
    }

    public function listLocaleCodesAction()
    {
        $locales = unserialize(get_option('multilanguage_locales'));
        $locales = array_map('locale_human', array_combine($locales, $locales));
        $this->_helper->json($locales);
    }

    public function listLocaleCodesRecordAction()
    {
        if (empty($_GET['record_type'])) {
            $this->_helper->json(array());
            return;
        }
        $db = get_db();
        $languageCodes = $db->getTable('MultilanguageContentLanguage')
            ->findLocaleCodes($_GET['record_type']);
        $this->_helper->json($languageCodes);
    }

    public function listRecordsAction()
    {
        $this->listRecordIds(array('id', 'slug'));
    }

    public function listRecordsBySlugAction()
    {
        $this->listRecordIds(array('slug', 'id'));
    }

    protected function listRecordIds($columns)
    {
        $recordType = empty($_GET['record_type']) ? null : $_GET['record_type'];
        $recordTypes = array(
            'Exhibit',
            'SimplePagesPage',
        );
        $recordType = in_array($recordType, $recordTypes) ? $recordType : null;
        if (empty($recordType)) {
            $this->_helper->json(array());
            return;
        }

        $db = get_db();
        $table = $db->getTable($recordType);
        $alias = $table->getTableAlias();
        $select = $table->getSelectForFindBy()
            ->reset(Zend_Db_Select::COLUMNS)
            ->from(array(), $columns)
            ->order($alias . '.' . reset($columns));
        $result = $db->fetchPairs($select);
        $this->_helper->json($result);
    }

    public function listRelatedRecordsAction()
    {
        $recordType = empty($_GET['record_type']) ? null : $_GET['record_type'];
        $recordTypes = array(
            'Exhibit',
            'SimplePagesPage',
        );
        $recordType = in_array($recordType, $recordTypes) ? $recordType : null;
        $recordId = empty($_GET['record_id']) ? null : (int) $_GET['record_id'];
        if (empty($recordType) || empty($recordId)) {
            $this->_helper->json(array());
            return;
        }

        $db = get_db();
        $result  = $db->getTable('MultilanguageRelatedRecord')
            ->findRelatedSourceRecordSlugIds($recordType, $recordId);
        $this->_helper->json($result);
    }

    /**
     * Update the locale code of a record.
     *
     * @param string $recordType
     * @param int $recordId
     * @param string $lang
     */
    protected function updateContentLang($recordType, $recordId, $lang)
    {
        $contentLanguage = $this->fetchContentLanguageRecord($recordType, $recordId);
        if (empty($contentLanguage)) {
            return;
        }
        $contentLanguage->record_type = $recordType;
        $contentLanguage->record_id = $recordId;
        $contentLanguage->lang = $lang;
        $contentLanguage->save();
    }

    /**
     * Get the matching content language for a record.
     *
     * @param string $recordType
     * @param int $recordId
     * @return MultilanguageContentLanguage|null
     */
    protected function fetchContentLanguageRecord($recordType, $recordId)
    {
        $recordTypes = array(
            'Exhibit',
            'SimplePagesPage',
        );
        $recordType = in_array($recordType, $recordTypes) ? $recordType : null;
        $recordId = (int) $recordId;
        if (empty($recordType) || empty($recordId)) {
            return null;
        }

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
