<?php
/**
 * 
 * @copyright (C) 2008-2018 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
namespace Punbb\Transphporm\TSSFunction;

class ValueReader implements \Transphporm\TSSFunction
{
    
    public function run(array $args, \DomElement $element)
    {
        return $element->textContent;
    }
}
