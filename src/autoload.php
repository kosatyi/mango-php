<?php

function lib_autoload($className){
    $classPath = explode('_',$className);
    $filePath = dirname(__FILE__).'/'.implode('/', $classPath).'.php';
    if(file_exists($filePath))
        require_once( $filePath );
}

spl_autoload_register('lib_autoload');
