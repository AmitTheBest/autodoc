<?php
class page_index extends Page
{
    function init()
    {
        parent::init();

        $cr=$this->add('CRUD');
        $cr->setModel('SourceFile');

        $cr->addAction('refresh','column');
    }
}
