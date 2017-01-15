<?php

function mango_class_autoloader($className){
    $classPath = explode('_',$className);
    $filePath = dirname(__FILE__).'/'.implode('/', $classPath).'.php';
    if(file_exists($filePath))
        require_once( $filePath );
}

spl_autoload_register('mango_class_autoloader');
