<?php

namespace Kosatyi\Mango;

use \Iterator;

class Find implements Iterator
{

    private $model;

    private $position = 0;

    private $cursor = [

    ];

    private $query  = [

    ];

    private $params = [
        'limit' => 1000
    ];

    private $typeMap = [
        'root' => 'array',
        'document' => 'array',
        'array' => 'array'
    ];

    public function __construct(Model $model)
    {
        $this->position = 0;
        $this->model = $model;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->model->instance($this->cursor[$this->position]);
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->cursor[$this->position]);
    }

    public function count()
    {
        return $this->model->count($this->query);
    }

    public function query($query = [], $override = FALSE)
    {
        if ($override == FALSE) {
            $this->query = array_merge($this->query, $query);
        } else {
            $this->query = $query;
        }
        return $this;
    }

    public function options($params = [], $override = FALSE)
    {
        if ($override == FALSE) {
            $this->params = array_merge($this->params, $params);
        } else {
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

    public function sort($sort = [])
    {
        $this->params['sort'] = $sort;
        return $this;
    }

    public function page($page = 1, $limit = 10)
    {
        $this->params['page'] = $page;
        $this->limit( $limit );
        $this->offset($limit * (max(1, $page ) - 1));
        return $this;
    }

    public function execute()
    {
        $this->cursor = $this->model->dbc()->find($this->query, $this->params);
        $this->cursor->setTypeMap($this->typeMap);
        $this->cursor = $this->cursor->toArray();
        return $this;
    }

    public function data()
    {
        return [
            'page'  => [
                'total'   =>  $this->count(),
                'current' => $this->params['page'],
                'limit'   =>  $this->params['limit'],
            ],
            'query' => $this->query ,
            'list'  => $this->toArray()
        ];
    }

    public function toArray()
    {
        $list   = [];
        foreach ($this->cursor as $item){
            array_push($list, $this->model->instance($item) );
        }
        return $list;
    }

}