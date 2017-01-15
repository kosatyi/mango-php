<?

class Mango_Model
{

    protected static $key;
    protected static $table;
    protected static $mongo;
    protected static $db;
    protected static $fields = array();

    protected $data = array();
    protected $error = array();

    public function __construct()
    {

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
                self::$mongo = new MongoClient('mongodb:///tmp/mongodb-27017.sock');
            } catch (Exception $e) {
                die('db connection error');
            }
        }
        return self::$mongo;
    }

    public function setDatabase($name)
    {
        self::$db = $name;
    }

    public function db($collection = NULL)
    {
        $db = $this->connect();
        $db = $db->selectDB($this::$db);
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
            return $this->attr('id');
        } else {
            $this->data['id'] = new MongoId( $value );
        }
        return $this;
    }

    public function instance( $data = array() )
    {
        $model = new $this;
        $model->attrs( $data );
        return $model;
    }

    public function attrs($data=array()){
        $data = (array) $data;
        foreach ($data as $key => $value)
            $this->attr( $key , $value);
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
        return empty($attr = $this->attr($attr)) ? $default : $attr;
    }

    public function model( $name )
    {
        $name = implode( '_' , array_map( 'ucfirst' , explode('_',$name) ) );
        return new $name;
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

    public function beforeCreate()
    {

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

    public function setIdField(){
        $this->id(new MongoId());
    }

    public function getMongoDate($value)
    {
        if (is_numeric($value))
            return new MongoDate($value);
        if (is_string($value))
            return new MongoDate(strtotime($value));
        if ($value instanceof MongoDate)
            return $value;
    }

    public function setMongoDate($key, $value)
    {
        $this->attr($key, $this->getMongoDate($value));
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
        if($unique==TRUE) $value = array_unique($value);
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
            $this->dbc()->insert($this->serialize());
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
            $this->dbc()->update(array('id' =>$this->id()),$this->serialize());
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
            $this->dbc()->remove(array('id' => $this->id()));
            $this->afterDelete();
        } catch (Exception $e) {
            $this->error = $e;
        }
        return $this;
    }

    public function findAll($query = array())
    {
        $query = array_merge(array(
            'page'   => 1,
            'limit'  => 10 ,
            'sort'   => array( 'id' => 1 ) ,
            'fields' => array( '_id' => 0 )
        ), array_filter($query) );
        $page   = $query['page'];
        $limit  = $query['limit'];
        $sort   = $query['sort'];
        $fields = $query['fields'];
        $offset = $limit * (max(1, $page) - 1);
        unset($query['fields']);
        unset($query['sort']);
        unset($query['limit']);
        unset($query['page']);
        $data = $this->dbc()->find($query,$fields);
        $data->limit($limit);
        $data->skip($offset);
        $data->sort($sort);
        return (new Mango_List($data,$this));
    }

    public function findOne( $where = array(), $fields = array())
    {
        return $this->instance( $this->dbc()->findOne( $where , $fields ) );
    }

    public function findItem($fields = array())
    {
        return $this->findOne( array( 'id' => $this->id() ) ,$fields );
    }

}