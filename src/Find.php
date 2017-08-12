<?php

namespace Kosatyi\Mango;

use \IteratorIterator;
use \Iterator;

class Find implements Iterator
{

    private $model;
    private $cursor  = array();
    private $query   = array();
    private $params  = array();
    private $typeMap = array(
        'root' => 'array',
        'document' => 'array',
        'array' => 'array'
    );
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
    public function query($query = array(),$override=FALSE)
    {
        if(isset($query['page'])){
            $this->page($query['page']);
            unset($query['page']);
        }
        if( $override == FALSE ){
            $this->query = array_merge($this->query,$query);
        } else{
            $this->query = $query;
        }
        return $this;
    }
    public function options($params = array(),$override=FALSE)
    {
        if( $override == FALSE ){
            $this->params = array_merge($this->params,$params);
        } else{
            $this->params = $params;
        }
        return $this;
    }
    public function limit($limit)
    {
        $this->params['limit'] = $limit;
        return $this;
    }
    public function offset($offset)
    {
        $this->params['offset'] = $offset;
        return $this;
    }
    public function sort($sort = array())
    {
        $this->params['$orderby'] = $sort;
        return $this;
    }
    public function page($page = 1, $limit = 10)
    {
        $this->limit($limit);
        $this->offset($limit * (max(1, $page) - 1));
        return $this;
    }
    public function execute()
    {
        $this->cursor = $this->model->dbc()->find($this->query, $this->params);
        $this->cursor = new IteratorIterator($this->cursor);
        $this->cursor->setTypeMap($this->typeMap);
        return $this;
    }
    function rewind()
    {
        return $this->cursor->rewind();
    }
    function current()
    {
        $item = $this->cursor->current();
        return $this->model->instance($item);
    }
    function key()
    {
        return $this->cursor->key();
    }
    function next()
    {
        return $this->cursor->next();
    }
    function valid()
    {
        return $this->cursor->valid();
    }
    function count()
    {
        return $this->model->count($this->query);
    }
    function serialize()
    {
        return array(
            'count' => $this->count() ,
            'data'  => $this->toArray()
        );
    }
    function toArray()
    {
        $list = array();
        foreach ($this->cursor as $item) {
            array_push($list, $this->model->instance($item)->serialize());
        }
        return $list;
    }
}