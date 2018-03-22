<?php
namespace Punbb\Controllers;

class Base {
    /**
     * @var \Pimple\Container
     */
    protected $c;
    
    protected $errors = [];
    
    public function __construct($class, $c) {
        $name = substr(strrchr($class, '\\'), 1);
        $c['lang_'.lcfirst($name)] = require FORUM_ROOT.'lang/'.$c['user']['language'].'/'.lcfirst($name).'.php';
        $this->gateway = $c[$name.'Gateway'];
        $this->c = $c;
    }
}