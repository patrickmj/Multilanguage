<?php

class Table_MultilanguageContentLanguage extends Omeka_Db_Table
{
    /**
     * Get the locale code for all objects of a specific record type (simple
     * page, exhibit…).
     *
     * @param string $recordType
     * @return array Associative array with record id as key and language code
     * as value.
     */
    public function findLocaleCodes($recordType)
    {
        $options = array(
            'record_type' => $recordType,
        );
        $columnPairs = array('record_id', 'lang');
        // Similar to findPairsForSelectForm().
        $select = $this->getSelectForFindBy($options);
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->from(array(), $columnPairs);
        $pairs = $this->getDb()->fetchPairs($select);
        return $pairs;
    }
}
