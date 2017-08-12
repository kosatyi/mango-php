<?php

namespace Kosatyi\Mango;

use MongoDB\Client;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\Regex;

class Model {

    protected static $id = 'id';

    protected static $mongo;

    protected static $db;

    protected static $table;

    protected static $fields  = array();

    protected static $indexes = array();

    protected static $connection = 'mongodb:///tmp/mongodb-27017.sock';

    protected static $options = array(
        'typeMap'=> array(
            'root' => 'array',
            'document' => 'array',
            'array' => 'array'
        )
    );

    protected $data  = array();

    protected $error = array();

    public function __construct()
    {

    }

    public function setDatabase($name)
    {
        self::$db = $name;
    }

    public function setConnection($connection)
    {
        self::$connection = $connection;
    }


    public function objectid($value=null){
        if($value instanceof ObjectID) return $value;
        if(is_string($value)) $value = new ObjectID($value);
        else $value = new ObjectID();
        return $value;
    }

    public function regex($value,$flags=''){
        if($value instanceof Regex) return $value;
        if(is_string($value)) $value = new Regex($value,$flags);
        return $value;
    }

    public function utcdatetime($value=null){
        if($value instanceof UTCDateTime) return $value;
        if(is_numeric($value)) $value =  new UTCDateTime($value);
        if(is_string($value)) $value = new UTCDateTime(strtotime($value));
        return $value;
    }

    public function install()
    {
        foreach ($this::$indexes as $index) {
            $this->index($index['name'], $index['type']);
        }
    }

    public function index($name, $type)
    {
        $this->dbc()->createIndex($name, $type);
    }

    protected function connect()
    {
        if (!isset(self::$mongo)) {
            try {
                self::$mongo = new Client(self::$connection);
            } catch (Exception $e) {
                die('db connection error');
            }
        }
        return self::$mongo;
    }


    public function db($collection = NULL)
    {
        $db = $this->connect();
        $db = $db->selectDatabase($this::$db);
        if (isset($collection))
            $db = $db->selectCollection($collection);
        return $db;
    }

    public function dbc()
    {
        return $this->db($this::$table);
    }

    public function id( $value = NULL ){
        if( is_null( $value ) ) {
            return $this->attr($this::$id);
        } else {
            $this->attr($this::$id,$value);
        }
        return $this;
    }

    public function setId($value){
        $this->data[$this::$id] = $this->objectid($value);
    }

    public function getId(){
        return $this->data[$this::$id];
    }

    public function instance($data=array())
    {
        $model = new $this;
        $model->attrs( $data );
        return $model;
    }

    public function attrs($data=array()){
        $data = (array)$data;
        foreach ($data as $key => $value)
            $this->attr($key,$value);
        return $this;
    }

    public function attr($attr, $value = NULL)
    {
        $type = func_num_args() == 1 ? 'get' : 'set';
        $attr = explode('.', $attr);
        $count = count($attr);
        $prop = $attr[0];
        $method = $type . ucwords($prop);
        if (method_exists($this, $method)) {
            $result = call_user_func_array(array($this, $method), array($value));
        } else {
            if (array_key_exists($prop, $this->data)) {
                $result =& $this->data[$prop];
            } else {
                return NULL;
            }
        }
        for ($i = 1; $i < $count; $i++) {
            if (is_object($result)) {
                $result =& $result->$attr[$i];
            } else if (is_array($result)) {
                $result =& $result[$attr[$i]];
            } else {
                return NULL;
            }
        }
        if ($type == 'set') $result = $value;
        return $result;
    }

    public function alt($attr, $default)
    {
        $attr = $this->attr($attr);
        return empty( $attr ) ? $default : $attr;
    }

    public function hasMethod($name)
    {
        return is_string($name) ? method_exists($this, $name) : FALSE;
    }

    public function call( $method , $params = array() )
    {
        return $this->hasMethod($method) ? call_user_func_array(array($this, $method), $params) : FALSE;
    }

    public function currentTimestamp()
    {
        return (time());
    }

    public function setIdField(){
        $this->id($this->objectid());
    }
    public function getMongoDate($value)
    {
        return $this->utcdatetime($value);
    }

    public function setMongoDate($key,$value)
    {
        $this->attr($key,$this->getMongoDate($value));
    }

    public function getErrorCode()
    {
        return $this->error->getCode();
    }

    public function getErrorMessage()
    {
        return $this->error->getMessage();
    }

    public function stringToArray($value,$unique=TRUE){
        if(is_string($value)) $value = explode(',',$value);
        if($unique==TRUE) $value = array_unique((array)$value);
        return $value;
    }

    public function serialize()
    {
        return $this->data;
    }

    public function create()
    {
        try {
            $this->beforeCreate();
            $this->dbc()->insertOne($this->serialize());
            $this->afterCreate();
        } catch (Exception $e) {
            $this->error = $e;
        }
        return $this;
    }
    public function update()
    {
        try {
            $this->beforeUpdate();
            $this->dbc()->updateOne(array($this::$id=>$this->id()),array('$set'=>$this->serialize()));
            $this->afterUpdate();
        } catch (Exception $e) {
            $this->error = $e;
        }
        return $this;
    }
    public function delete()
    {
        try {
            $this->beforeDelete();
            $this->dbc()->deleteOne(array($this::$id=>$this->id()));
            $this->afterDelete();
        } catch (Exception $e) {
            $this->error = $e;
        }
        return $this;
    }

    public function find(){
        return new Find($this);
    }

    protected function getOptions($options=array()){
        return array_merge(self::$options,$options);
    }

    public function findOne( $query = array(), $options = array())
    {
        return $this->instance( $this->dbc()->findOne( $query , $this->getOptions($options) ) );
    }

    public function findItem( $options = array() )
    {
        return $this->findOne( array( $this::$id => $this->id() ) , $this->getOptions($options) );
    }

    public function beforeCreate()
    {
        $this->setIdField();
    }

    public function afterCreate()
    {

    }

    public function beforeUpdate()
    {

    }

    public function afterUpdate()
    {

    }

    public function beforeDelete()
    {

    }

    public function afterDelete()
    {

    }

}