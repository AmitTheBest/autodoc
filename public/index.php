<?php
if(function_exists('xdebug_enable')){
    xdebug_enable();
}
chdir('..');
require_once'vendor/autoload.php';
require_once 'lib/Admin.php';
$api=new Admin('admin');
$api->main();
