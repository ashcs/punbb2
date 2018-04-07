<?php
/**
 * 
 * @copyright (C) 2008-2018 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
namespace Punbb\Transphporm\Formatter;

class ForumTime
{
    
 
    public function forum_time($time)
    {
        return \Punbb\ForumFunction::format_time($time);
    }
}
