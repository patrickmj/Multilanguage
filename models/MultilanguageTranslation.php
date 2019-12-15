<?php

class MultilanguageTranslation extends Omeka_Record_AbstractRecord
{
    public $element_id;

    public $record_id;

    public $record_type;

    public $locale_code;

    public $text;

    public $translation;

    protected function beforeSave($args)
    {
        // The str_replace() allows to fix Apple and Windows copy/paste.
        $this->text = str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $this->text);
    }
}
