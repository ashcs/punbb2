<?php
/**
 * 
 * @copyright (C) 2008-2018 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
namespace Punbb;

use Punbb\ForumFunction as F;

class Url implements \Transphporm\TSSFunction {

    public function run(array $args, \DomElement $element) {
        //F::forum_link($link)
        return  call_user_func_array(array('\Punbb\ForumFunction', 'forum_link'), $args);
    }
}
