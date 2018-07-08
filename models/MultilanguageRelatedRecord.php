<?php

class MultilanguageRelatedRecord extends Omeka_Record_AbstractRecord
{
    public $record_type;

    public $record_id;

    public $related_id;

    // TODO Add some code to skip when record id is the same than related id.
    // TODO Add some code to save the smaller id as record id.
}
