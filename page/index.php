<?php
class page_index extends Page
{
    function init()
    {
        parent::init();

        $m = $this->add('Model_SourceFile');
        $cr=$this->add('CRUD');
        $cr->setModel($m);

        $cr->addAction('refresh','column');
        $cr->addAction('getDocLocation',['column'=>true,'toolbar'=>false,'descr'=>'Get doc location']);
    }
}
