<?php
/**
 * Model implementation
 */
class Model_SourceFile extends Model
{
    public $table="sourcefile";

    /**
     * init model
     *
     * @return void
     */
    function init()
    {
        parent::init();

        $this->setSource('SQLite');

        $this->addField('file');
        $this->addField('last_imported')->type('date');
        $this->addField('contents')->type('text');

        $this->add('dynamic_model/Controller_AutoCreator_SQLite');
    }

    function refresh() {
        return $this->api->locate('atk_source',$this['file']);
    }
}
