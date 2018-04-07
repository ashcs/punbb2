<?php
/**
 * 
 * @copyright (C) 2008-2018 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
namespace Punbb\Transphporm\TSSFunction;

class Translator implements \Transphporm\TSSFunction
{
    
    private $lang = [];
    
    public function __construct()
    {
        if (func_num_args()) {
            $this->lang = call_user_func_array('array_merge', func_get_args());
        }
    }

    public function addLang() {
        
        $numargs = func_num_args();
        $arg_list = func_get_args();
        for ($i = 0; $i < $numargs; $i++) {
            $this->lang = array_merge($this->lang, $arg_list[$i]);
        }
        
        
    }
    
    public function run(array $args, \DomElement $element)
    {
        if (isset($this->lang[$element->textContent])) {
            if ($args[0]!= 'null') {
                return vsprintf($this->lang[$element->textContent], $args);
            }
            else {
                return $this->lang[$element->textContent];
            }
        }
        else {
            if ($args[0]!= 'null') {
                return vsprintf($element->textContent, $args);
                
            }
            return $element->textContent;
        }
    }
}
