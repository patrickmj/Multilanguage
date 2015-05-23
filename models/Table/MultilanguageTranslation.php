<?php

class Table_MultilanguageTranslation extends Omeka_Db_Table
{
    public function getTranslation($recordId, $recordType, $elementId, $locale_code, $text)
    {
        $params = array(
                      'record_id'   => $recordId,
                      'record_type' => $recordType,
                      'element_id'  => $elementId,
                      'locale_code' => $locale_code,
                      'text'        => $text
                );
        $select = $this->getSelectForFindBy($params);
        return $this->fetchObject($select);
    }
}