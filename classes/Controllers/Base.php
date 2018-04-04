<?php
namespace Punbb\Controllers;

class Base {
    /**
     * @var \Pimple\Container
     */
    protected $c;
    
    protected $errors = [];
    
    protected $lang = [];
    
    public function __construct($class, $c)
    {
        $name = substr(strrchr($class, '\\'), 1);
        if (file_exists(FORUM_ROOT.'lang/'.$c['user']['language'].'/'.lcfirst($name).'.php')) {
            //$this->lang = require FORUM_ROOT.'lang/'.$c['user']['language'].'/'.lcfirst($name).'.php';
            $c['lang_'.lcfirst($name)] = $this->lang = require FORUM_ROOT.'lang/'.$c['user']['language'].'/'.lcfirst($name).'.php';
        }
        $this->gateway = $c[$name.'Gateway'];
        $this->c = $c;
    }
    
    public function getLang()
    {
        return $this->lang;    
    }
}