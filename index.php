<?php

    require_once 'vendor/autoload.php';


    class MyModel extends Kosatyi\Mango\Model {
        protected static $db = 'tester';
        protected static $table = 'mymodel';
    }

    $mymodel = new MyModel();


    //$file = fopen('test.txt' ,'r');

    //$stream  =  $mymodel->fs()->uploadFromStream('test.txt', $file);

    $list    = $mymodel->fs()->find(array('filename'=>'test.txt'));

    foreach($list as $item){
        echo '<pre>';
        print_r($item);
        echo '</pre>';
    }