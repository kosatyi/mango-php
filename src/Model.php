<?php

namespace Kosatyi\Mango;

use MongoDB\Client;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\Regex;
use MongoDB\GridFS\Bucket;

/**
 * Class Model
 * @package Kosatyi\Mango
 */
class Model implements \JsonSerializable, \Serializable
{

    /**
     *
     */
    const SEPARATOR = '.';

    /**
     * @var string
     */
    protected static $id = '_id';

    /**
     * @var string
     */
    protected static $connection = '/tmp/mongodb-27017.sock';
    /**
     * @var
     */
    protected static $mongo;

    /**
     * @var
     */
    protected static $db;

    /**
     * @var
     */
    protected static $table;

    /**
     * @var array
     */
    protected static $indexes = [];

    /**
     * @var array
     */
    protected static $options = [
        'typeMap' => [
            'root' => 'array',
            'document' => 'array',
            'array' => 'array'
        ]
    ];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var null
     */
    protected $error = null;

    /**
     * Model constructor.
     */
    public function __construct()
    {
        if (!array_key_exists($this::$id, $this->data)) {
            $this->data[$this::$id] = null;
        }
    }

    /**
     * @param $name
     */
    public function setDatabase($name)
    {
        self::$db = $name;
    }
    /**
     * @param $connection
     */
    public function setConnection($connection)
    {
        $this::$connection = $connection;
    }
    /**
     * @return string
     */
    public function getConnection()
    {
        return $this::$connection;
    }

    /**
     * @return string
     */
    public function getConnectionURI()
    {
        return sprintf('mongodb://%s', rawurlencode($this->getConnection()));
    }

    /**
     * @param null $value
     * @return ObjectID|null
     */
    public function objectId($value = null)
    {
        if ($value instanceof ObjectID) return $value;
        if (is_string($value)) $value = new ObjectID($value);
        else $value = new ObjectID();
        return $value;
    }


    /**
     * @param $value
     * @param string $flags
     * @return Regex
     */
    public function regex($value, $flags = '')
    {
        if ($value instanceof Regex) return $value;
        if (is_string($value)) $value = new Regex($value, $flags);
        return $value;
    }

    /**
     * @param null $value
     * @return UTCDateTime|null
     */
    public function utcDateTime($value = null)
    {
        if ($value instanceof UTCDateTime) return $value;
        if (is_numeric($value)) $value = new UTCDateTime($value);
        if (is_string($value)) $value = new UTCDateTime(strtotime($value));
        return $value;
    }

    /**
     *
     */
    public function install()
    {
        foreach ($this::$indexes as $index) {
            $this->index($index['name'], $index['type']);
        }
    }

    /**
     * @param $name
     * @param $type
     */
    public function index($name, $type)
    {
        $this->dbc()->createIndex($name, $type);
    }

    /**
     * @return Client
     */
    protected function connect()
    {
        if (!isset(self::$mongo)) {
            try {
                self::$mongo = new Client($this->getConnectionURI());
            } catch (\Exception $e) {
                die('db connection error');
            }
        }
        return self::$mongo;
    }

    /**
     * @param null $collection
     * @return Client|\MongoDB\Collection|\MongoDB\Database
     */
    public function db($collection = NULL)
    {
        $db = $this->connect();
        $db = $db->selectDatabase($this::$db);
        if (isset($collection))
            $db = $db->selectCollection($collection);
        return $db;
    }

    /**
     * @return Client|\MongoDB\Collection|\MongoDB\Database
     */
    public function dbc()
    {
        return $this->db($this::$table);
    }

    /**
     * @param null $value
     * @return $this|string
     */
    public function id($value = NULL)
    {
        if (is_null($value)) {
            return (string)$this->prop($this::$id);
        } else {
            $this->prop($this::$id, $this->objectId($value));
        }
        return $this;
    }

    /**
     * @return Bucket
     */
    public function fs()
    {
        return new Bucket($this->dbc()->getManager(), $this::$db);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function instance($data = array())
    {
        $model = new $this;
        $model->attrs($data);
        return $model;
    }

    /**
     * @param $path
     * @return array|mixed|strlen
     */
    protected function getKeyParts($path)
    {
        if (is_array($path)) return $path;
        $parts = array_filter(explode(static::SEPARATOR, (string)$path), 'strlen');
        $parts = array_reduce($parts, function ($a, $v) {
            $a[] = ctype_digit($v) ? intval($v) : $v;
            return $a;
        }, []);
        return $parts;
    }

    /**
     * @param $type
     * @param $keys
     * @return string
     */
    public function getMethodName($type, $keys = [])
    {
        $keys = array_map(function ($value) {
            return implode('', array_map('ucwords', explode('_', $value)));
        }, $keys);
        $keys = implode('', $keys);
        return $type . $keys;
    }

    /**
     * @param $attr
     * @param null $value
     * @return Model|mixed|null
     */
    public function attr($attr, $value = null)
    {
        $keys = $this->getKeyParts($attr);
        $method = $this->getMethodName(is_null($value) ? 'get' : 'set', $keys);
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], [$value]);
        }
        return $this->prop($keys, $value);
    }

    /**
     * @param $keys
     * @param null $value
     * @return $this|mixed|null
     */
    public function prop($keys, $value = null)
    {
        $keys = $this->getKeyParts($keys);
        $prop = array_shift($keys);
        $get = is_null($value);
        if (array_key_exists($prop, $this->data)) {
            if ($get) {
                $copy = $this->data[$prop];
            } else {
                $copy =& $this->data[$prop];
            }
        } else {
            return null;
        }
        while (count($keys)) {
            if ($copy instanceof $this) {
                return $copy->attr($keys, $value);
            }
            if (is_callable($copy)) {
                return $copy($keys, $value);
            }
            $key = array_shift($keys);
            if (is_object($copy)) {
                $copy =& $copy->{$key};
            } else {
                $copy =& $copy[$key];
            }
        }
        if ($get) {
            return $copy;
        }
        if (is_callable($copy)) {
            $copy($value);
        } else {
            $copy = $value;
        }
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function attrs($data = array())
    {
        $data = (array)$data;
        foreach ($data as $key => $value)
            $this->attr($key, $value);
        return $this;
    }

    /**
     * @param $attr
     * @param $default
     * @return Model|mixed|null
     */
    public function alt($attr, $default)
    {
        $attr = $this->attr($attr);
        return empty($attr) ? $default : $attr;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasMethod($name)
    {
        return is_string($name) ? method_exists($this, $name) : FALSE;
    }

    /**
     * @param $method
     * @param array $params
     * @return bool|mixed
     */
    public function call($method, $params = array())
    {
        return $this->hasMethod($method) ? call_user_func_array(array($this, $method), $params) : FALSE;
    }

    /**
     * @return int
     */
    public function currentTimestamp()
    {
        return (time());
    }


    public function setIdField()
    {
        if (empty($this->id())) {
            $this->id($this->objectId());
        }
    }

    /**
     * @param $value
     * @return UTCDateTime|null
     */
    public function getMongoDate($value)
    {
        return $this->utcDateTime($value);
    }

    /**
     * @param $key
     * @param $value
     */
    public function setMongoDate($key, $value)
    {
        $this->attr($key, $this->getMongoDate($value));
    }

    /**
     * @param $value
     * @param bool $unique
     * @return array
     */
    public function stringToArray($value, $unique = TRUE)
    {
        if (is_string($value)) $value = explode(',', $value);
        if ($unique == TRUE) $value = array_unique((array)$value);
        return $value;
    }

    /**
     * @return null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return array
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * @return $this
     */
    public function create()
    {
        try {
            $this->setIdField();
            $this->beforeCreate();
            $this->dbc()->insertOne($this->data());
            $this->afterCreate();
        } catch (\Exception $e) {
            $this->error = $e;
        }
        return $this;
    }

    /**
     * @return array
     */
    private function queryId()
    {
        return [$this::$id => $this->id()];
    }

    /**
     * @return $this
     */
    public function update()
    {
        try {
            $this->beforeUpdate();
            $this->dbc()->updateOne($this->queryId(), ['$set' => $this->data()]);
            $this->afterUpdate();
        } catch (\Exception $e) {
            $this->error = $e;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function delete()
    {
        try {
            $this->beforeDelete();
            $this->dbc()->deleteOne($this->queryId());
            $this->afterDelete();
        } catch (\Exception $e) {
            $this->error = $e;
        }
        return $this;
    }

    /**
     * @return Find
     */
    public function find()
    {
        return new Find($this);
    }

    /**
     * @param array $query
     * @param array $options
     * @return int
     */
    public function count($query = array(), $options = array())
    {
        return $this->dbc()->count($query, $this->getOptions($options));
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getOptions($options = array())
    {
        return array_merge(self::$options, $options);
    }

    /**
     * @param array $query
     * @param array $options
     * @return mixed
     */
    public function findOne($query = array(), $options = array())
    {
        return $this->instance($this->dbc()->findOne($query, $this->getOptions($options)));
    }

    /**
     * @param array $options
     * @return mixed
     */
    public function findItem($options = array())
    {
        return $this->findOne(array($this::$id => $this->id()), $this->getOptions($options));
    }

    /**
     *
     */
    public function beforeCreate()
    {

    }

    /**
     *
     */
    public function afterCreate()
    {

    }

    /**
     *
     */
    public function beforeUpdate()
    {

    }

    /**
     *
     */
    public function afterUpdate()
    {

    }

    /**
     *
     */
    public function beforeDelete()
    {

    }

    /**
     *
     */
    public function afterDelete()
    {

    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->data);
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        $this->data = unserialize($data);
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

}