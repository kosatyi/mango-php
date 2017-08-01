<?php

namespace Mango;

class Cursor implements \Iterator
{
    private $cursor = array();
    private $model  = array();
    private $query  = array();
    public function __construct(\MongoDB\Driver\Cursor $cursor, Model $model, $query = array())
    {
        $this->cursor = new \IteratorIterator($cursor);
        $this->query  = $query;
        $this->model  = $model;
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
    function sort($sort = array())
    {
        $this->cursor->sort($sort);
        return $this;
    }
    function valid()
    {
        return $this->cursor->valid();
    }
    function count()
    {
        return $this->model->count($this->query);
    }
    function limit($limit = 0)
    {
        $this->cursor->limit($limit);
        return $this;
    }
    function skip($skip = 0)
    {
        $this->cursor->skip($skip);
        return $this;
    }
    function page($page = 1, $limit = 10)
    {
        return $this->limit($limit)->skip($limit * (max(1, $page) - 1));
    }
    function serialize()
    {
        return array(
            'count' => $this->count(FALSE),
            'data' => $this->toArray()
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