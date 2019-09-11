<?php

class Table_MultilanguageRelatedRecord extends Omeka_Db_Table
{
    /**
     * Get all the records related to another record (simple page, exhibit…).
     *
     * @param string $recordType
     * @param int|string $recordIdOrSlug The record id if numeric, else a slug.
     * @param bool $included Include the specified record to the list.
     * @return array List of record ids.
     */
    public function findRelatedRecords($recordType, $recordIdOrSlug, $included = false)
    {
        $recordIds = $this->findRelatedRecordIds($recordType, $recordIdOrSlug, $included);
        if (empty($recordIds)) {
            return array();
        }
        $recordIdsString = implode(',', $recordIds);
        $tableAlias = $this->getTableAlias();
        $select = $this->getSelect()
            ->where($tableAlias . '.record_type = ?', $recordType)
            ->where("$tableAlias.record_id IN ($recordIdsString) OR $tableAlias.related_id IN ($recordIdsString)");
        return $this->fetchObjects($select);
    }

    /**
     * Get all source records related to a record (simple page, exhibit…).
     *
     * @param string $recordType
     * @param int|string $recordIdOrSlug The record id if numeric, else a slug.
     * @param bool $included Include the specified record to the list.
     * @return Omeka_Record_AbstractRecord[] List of records.
     */
    public function findRelatedSourceRecords($recordType, $recordIdOrSlug, $included = false)
    {
        $recordIds = $this->findRelatedRecordIds($recordType, $recordIdOrSlug, $included);
        if (empty($recordIds)) {
            return array();
        }
        $recordIdsString = implode(',', $recordIds);
        $table = $this->_db->getTable($recordType);
        $alias = $table->getTableAlias();
        $select = $table->getSelect()
            ->where($alias . '.id IN (' . $recordIdsString . ')');
        return $this->fetchObjects($select);
    }

    /**
     * Get the related source record (simple page, exhibit…) for a locale.
     *
     * @param string $recordType
     * @param int|string $recordIdOrSlug The record id if numeric, else a slug.
     * @param string $locale
     * @return Omeka_Record_AbstractRecord
     */
    public function findRelatedSourceRecordForLocale($recordType, $recordIdOrSlug, $locale)
    {
        if (empty($locale)) {
            return;
        }
        $recordIds = $this->findRelatedSourceRecordIdsWithLocale($recordType, $recordIdOrSlug, true);
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
     * @param int|string $recordIdOrSlug The record id if numeric, else a slug.
     * @param bool $included Include the specified record to the list.
     * @return array List of record ids by slug.
     */
    public function findRelatedSourceRecordSlugIds($recordType, $recordIdOrSlug, $included = false)
    {
        if ($recordType === 'ExhibitPage') {
            return $this->findRelatedSourceExhibitPageSlugIds($recordIdOrSlug, $included);
        }

        $recordIds = $this->findRelatedRecordIds($recordType, $recordIdOrSlug, $included);
        if (empty($recordIds)) {
            return array();
        }

        $recordIdsString = implode(',', $recordIds);
        $table = $this->_db->getTable($recordType);
        $alias = $table->getTableAlias();
        // Prepend the alias to avoid issue on some tables with join.
        $columns = array(
            'slug' => $alias . '.slug',
            'id' => $alias . '.id',
        );
        $select = $table->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->from(array(), $columns)
            ->where($alias . '.id IN (' . $recordIdsString . ')')
            ->order(reset($columns));
        return $this->fetchPairs($select);
    }

    /**
     * Get all source record slugs and ids related to an exhibit page.
     *
     * @param int $recordId The record id.
     * @param bool $included Include the specified record to the list.
     * @return array List of record ids by slug.
     */
    public function findRelatedSourceExhibitPageSlugIds($recordId, $included = false)
    {
        $recordType = 'ExhibitPage';
        $recordId = (int) $recordId;
        $recordIds = $this->findRelatedRecordIds($recordType, $recordId, $included);
        if (empty($recordIds)) {
            return array();
        }

        $recordIdsString = implode(',', $recordIds);
        $table = $this->_db->getTable($recordType);
        $alias = $table->getTableAlias();
        $columns = array(
            'slug' => 'exhibit_pages.slug',
            'id' => 'exhibit_pages.id',
            'exhibit' => 'exhibits.slug',
        );
        $select = $table->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->from(array(), $columns)
            ->where($alias . '.id IN (' . $recordIdsString . ')')
            ->order(array('exhibits.slug asc', 'exhibit_pages.slug asc'));
        $result = $this->_db->fetchAll($select);
        $list = array();
        foreach ($result as $v) {
            $list[$v['exhibit'] . ' > ' . $v['slug']] = $v['id'];
        }
        return $list;
    }

    /**
     * Get all records with locale related to a record (simple page, exhibit…).
     *
     * @param string $recordType
     * @param int|string $recordIdOrSlug The record id if numeric, else a slug.
     * @param bool $included Include the specified record to the list.
     * @return array Associative array of record ids and locale, if any.
     */
    public function findRelatedSourceRecordIdsWithLocale($recordType, $recordIdOrSlug, $included = false)
    {
        // Note: The process cannot be done with a simple left joint, since it
        // should be based on record_id or related_id.

        $recordIds = $this->findRelatedRecordIds($recordType, $recordIdOrSlug, $included);
        if (empty($recordIds)) {
            return array();
        }
        $recordIdsString = implode(',', $recordIds);
        $columns = array('record_id', 'lang');
        $table = $this->_db->getTable('MultilanguageContentLanguage');
        $select = $table->getSelect()
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
     * @param int|string $recordIdOrSlug The record id if numeric, else a slug.
     * @param bool $included Include the specified record to the list.
     * @return array List of record ids.
     */
    public function findRelatedRecordIds($recordType, $recordIdOrSlug, $included = false)
    {
        $recordId = is_numeric($recordIdOrSlug)
            ? (int) $recordIdOrSlug
            : $this->findRecordIdFromSlug($recordType, $recordIdOrSlug);
        if (empty($recordId)) {
            return array();
        }

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

        // TODO Write the query that merge the queries.
        $db = $this->_db;
        $table = $db->MultilanguageRelatedRecord;
        $sql = "
SELECT DISTINCT(IF(record_id = ?, related_id, record_id)) AS related_record_id
FROM `$table`
WHERE record_type = ?
AND (record_id = ? OR related_id = ?)
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
AND (record_id IN ($recordIdsString) OR related_id IN ($recordIdsString))
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

    /**
     * Get the record id from a slug with a direct query to avoid filters.
     *
     * @param string $recordType
     * @param string $slug
     * @return int|null
     */
    protected function findRecordIdFromSlug($recordType, $slug)
    {
        $db = $this->_db;
        $table = $this->_db->getTable($recordType);
        $alias = $table->getTableAlias();
        $select = $table->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->from(array(), $alias . '.id')
            ->where($alias . '.slug = ?', $slug)
            ->limit(1)
            ->reset(Zend_Db_Select::ORDER);
        $recordId = $db->fetchOne($select);
        return $recordId;
    }
}
