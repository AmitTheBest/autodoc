<?php
class Admin extends App_Admin {
    function init()
    {
        parent::init();

        $this->pathfinder->base_location->defineContents([ 'addons'=>'atk4-addons']);

        $this->pathfinder->base_location->addRelativeLocation('vendor/atk4/atk4/lib', ['atk_source'=>'.']);


    }
}
