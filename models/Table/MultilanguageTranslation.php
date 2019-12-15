<?php

class Table_MultilanguageTranslation extends Omeka_Db_Table
{
    /**
     * Get the translation for a specific element text.
     *
     * @param int $recordId
     * @param string $recordType
     * @param int $elementId
     * @param string $locale_code
     * @param string $text
     * @return Omeka_Record_AbstractRecord
     */
    public function getTranslation($recordId, $recordType, $elementId, $locale_code, $text)
    {
        // The str_replace() allows to fix Apple and Windows copy/paste.
        $text = str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $text);
        $params = array(
            'record_id' => $recordId,
            'record_type' => $recordType,
            'element_id' => $elementId,
            'locale_code' => $locale_code,
            'text' => $text,
        );
        $select = $this->getSelectForFindBy($params);
        return $this->fetchObject($select);
    }
}
