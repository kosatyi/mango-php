<?

class Mango_List implements Iterator
{
    private $cursor = array();
    private $model  = array();
    public function __construct(MongoCursor $cursor, Mango_Model $model)
    {
        $this->cursor = $cursor;
        $this->model = $model;
    }
    function rewind()
    {
        return $this->cursor->rewind();
    }
    function info()
    {
        return $this->cursor->info();
    }
    function explain()
    {
        return $this->cursor->explain();
    }
    function current()
    {
        $item = $this->cursor->current();
        return $this->model->instance( $item );
    }
    function key()
    {
        return $this->cursor->key();
    }
    function next()
    {
        return $this->cursor->next();
    }
    function sort($sort = array())
    {
        $this->cursor->sort($sort);
        return $this;
    }
    function valid()
    {
        return $this->cursor->valid();
    }
    function count($type = TRUE)
    {
        return $this->cursor->count($type);
    }
    function serialize(){
        return array(
            'count' => $this->count(FALSE) ,
            'data'  => $this->toArray()
        );
    }
    function toArray()
    {
        $list = array();
        foreach($this->cursor as $item){
            array_push($list,$this->model->instance($item)->serialize());
        }
        return $list;
    }
}