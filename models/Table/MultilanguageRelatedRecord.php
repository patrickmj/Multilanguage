<?php

class Table_MultilanguageRelatedRecord extends Omeka_Db_Table
{
    /**
     * Get all the records related to another record (simple page, exhibit…).
     *
     * @param string $recordType
     * @param int $recordId
     * @param bool $included Include the specified record to the list.
     * @return array List of record ids.
     */
    public function findRelatedRecords($recordType, $recordId, $included = false)
    {
        $recordIds = $this->findRelatedRecordIds($recordType, $recordId, $included);
        if (empty($recordIds)) {
            return array();
        }
        $recordIdsString = implode(',', $recordIds);
        $select = $this->getSelect()
            ->where($this->getTableAlias() . ".record_id IN ($recordIdsString)")
            ->orWhere($this->getTableAlias() . ".related_id IN ($recordIdsString)");
        return $this->fetchObjects($select);
    }

    /**
     * Get all source records related to a record (simple page, exhibit…).
     *
     * @param string $recordType
     * @param int $recordId
     * @param bool $included Include the specified record to the list.
     * @return Omeka_Record_AbstractRecord[] List of records.
     */
    public function findRelatedSourceRecords($recordType, $recordId, $included = false)
    {
        $recordIds = $this->findRelatedRecordIds($recordType, $recordId, $included);
        if (empty($recordIds)) {
            return array();
        }
        $recordIdsString = implode(',', $recordIds);
        $select = $this->_db->getTable($recordType)
            ->getSelect()
            ->where('id IN (' . $recordIdsString . ')');
        return $this->fetchObjects($select);
    }

    /**
     * Get the related source record (simple page, exhibit…) for a locale.
     *
     * The cuy
     *
     * @param string $recordType
     * @param int $recordId
     * @param string $locale
     * @return Omeka_Record_AbstractRecord
     */
    public function findRelatedSourceRecordForLocale($recordType, $recordId, $locale)
    {
        if (empty($locale)) {
            return;
        }
        $recordIds = $this->findRelatedSourceRecordIdsWithLocale($recordType, $recordId, true);
        if (empty($recordIds)) {
            return;
        }
        $relatedRecordId = array_search($locale, $recordIds);
        if ($relatedRecordId === false) {
            return;
        }
        return $this->_db->getTable($recordType)->find($relatedRecordId);
    }

    /**
     * Get all source record slugs and ids related to a record (simple page,
     * exhibit…).
     *
     * @param string $recordType
     * @param int $recordId
     * @param bool $included Include the specified record to the list.
     * @return array List of record ids by slug.
     */
    public function findRelatedSourceRecordSlugIds($recordType, $recordId, $included = false)
    {
        $recordIds = $this->findRelatedRecordIds($recordType, $recordId, $included);
        if (empty($recordIds)) {
            return array();
        }
        $recordIdsString = implode(',', $recordIds);
        $columns = array('slug', 'id');
        $select = $this->_db->getTable($recordType)
            ->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->from(array(), $columns)
            ->where('id IN (' . $recordIdsString . ')')
            ->order(reset($columns));
        return $this->fetchPairs($select);
    }

    /**
     * Get all records with locale related to a record (simple page, exhibit…).
     *
     * @param string $recordType
     * @param int $recordId
     * @param bool $included Include the specified record to the list.
     * @return array Associative array of record ids and locale, if any.
     */
    public function findRelatedSourceRecordIdsWithLocale($recordType, $recordId, $included = false)
    {
        // Note: The process cannot be done with a simple left joint, since it
        // should be based on record_id or related_id.

        $recordIds = $this->findRelatedRecordIds($recordType, $recordId, $included);
        if (empty($recordIds)) {
            return array();
        }
        $recordIdsString = implode(',', $recordIds);
        $columns = array('record_id', 'lang');
        $select = $this->_db->getTable('MultilanguageContentLanguage')
            ->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->from(array(), $columns)
            ->where('record_type = ?', $recordType)
            ->where('record_id IN (' . $recordIdsString . ')')
            ->order('lang');
        return $this->fetchPairs($select);
    }

    /**
     * Get all the record ids related to another record (simple page, exhibit…).
     *
     * @param string $recordType
     * @param int $recordId
     * @param bool $included Include the specified record to the list.
     * @return array List of record ids.
     */
    public function findRelatedRecordIds($recordType, $recordId, $included = false)
    {
        $recordId = (int) $recordId;

        /*
         // TODO Write the query that merge the three queries.
         $options = array(
             'record_type' => $recordType,
             'record_id' => $recordId,
         );
         $select = $this->getSelectForFindBy($options)
             ->reset(Zend_Db_Select::COLUMNS)
             ->from(array(), array('related_id'))
             ->where('multilanguage_related_records.related_id != ?', $recordId);
         $relatedIds = $this->getDb()->fetchCol($select);

         $options = array(
             'record_type' => $recordType,
             'related_id' => $recordId,
         );
         $select = $this->getSelectForFindBy($options)
             ->reset(Zend_Db_Select::COLUMNS)
             ->from(array(), array('record_id'))
             ->where('multilanguage_related_records.record_id != ?', $recordId);
         $recordIds = $this->getDb()->fetchCol($select);

         // Add indirect related ids when there are more than one relation.
         $recordIds = array_unique(array_merge($relatedIds, $recordIds));
         // TODO Query to get all indirect ids.
         $recordIds = $this->getDb()->fetchCol($select);
         */

        $db = $this->_db;
        $table = $db->MultilanguageRelatedRecord;
        $sql = "
SELECT DISTINCT(IF(record_id = ?, related_id, record_id)) AS related_record_id
FROM `$table`
WHERE record_type = ?
AND record_id = ? OR related_id = ?
ORDER BY related_record_id;
";
        $recordIds = $db->fetchCol($sql, array($recordId, $recordType, $recordId, $recordId));
        if (empty($recordIds)) {
            return array();
        }

        // Add indirect related ids when there are more than one relation.
        $recordIds[] = $recordId;
        $recordIdsString = implode(',', $recordIds);
        $sql = "
SELECT DISTINCT(IF(record_id IN ($recordIdsString), related_id, record_id)) AS related_record_id
FROM `$table`
WHERE record_type = ?
AND record_id IN ($recordIdsString) OR related_id IN ($recordIdsString)
ORDER BY related_record_id;
";
        $result = $db->fetchCol($sql, array($recordType));

        $result = array_unique(array_merge($recordIds, $result));

        if (!$included) {
            $recordIdKey = array_search($recordId, $result);
            if ($recordIdKey !== false) {
                unset($result[$recordIdKey]);
            }
        }

        sort($result);
        return $result;
    }
}
