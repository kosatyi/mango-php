<?php

require_once 'vendor/autoload.php';

class User extends Kosatyi\Mango\Model
{
    protected static $db = 'project';
    protected static $table = 'users';
    protected static $indexes = [
        [
            'name' => ['email' => 1],
            'type' => ['unique' => 1]
        ],
        [
            'name' => ['date_add' => -1],
            'type' => []
        ],
        [
            'name' => ['date_mod' => -1],
            'type' => []
        ]
    ];

    protected $data = [
        'name' => '',
        'email' => '',
        'date_add' => '',
        'date_mod' => ''
    ];

    protected $filters = [

    ];

    public function beforeCreate()
    {
        $this->setMongoDate('date_add', $this->currentTimestamp());
        $this->setMongoDate('date_mod', $this->currentTimestamp());
    }

    public function beforeUpdate()
    {
        $this->setMongoDate('date_mod', $this->currentTimestamp());
    }

    public function getList($query = [], $page = 1, $limit = 10)
    {
        $cursor = $this->find();
        $cursor->query($query);
        $cursor->page($page, $limit);
        $cursor->sort(['date_add' => -1]);
        return $cursor->execute();
    }

    public function getDateMod()
    {
        return (string)$this->prop('date_mod');
    }

    public function getDateAdd()
    {
        return (string)$this->prop('date_add');
    }

}

$user = new User();

$user->install();
$user->attr('name', 'Test User');
$user->attr('email', 'test@example.com');
$user->create();

$list = $user->getList([], 1, 2);

foreach ($list as $user) {
    echo '<pre>';
    print_r($user->data());
    echo '<pre>';
}