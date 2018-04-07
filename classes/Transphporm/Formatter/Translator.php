<?php
/**
 * 
 * @copyright (C) 2008-2018 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
namespace Punbb\Transphporm\Formatter;

class Translator
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
    
    public function translate()
    {
        
        $numargs = func_num_args();
        $arg_list = func_get_args();
        
        if (!isset($this->lang[$arg_list[0]])) {
            if ($numargs == 1) {
                return $arg_list[0];
            }
            else {
                return vsprintf($arg_list[0], array_slice($arg_list, 1));
            }
        }
        
        if ($numargs == 1) {
            return $this->lang[$arg_list[0]];
        }
        else { 
            return vsprintf($this->lang[$arg_list[0]], array_slice($arg_list, 1));
        }
        
    }
}
