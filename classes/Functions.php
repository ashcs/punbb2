<?php
/* @description     Transformation Style Sheets - Revolutionising PHP templating    *
 * @author          Tom Butler tom@r.je                                             *
 * @copyright       2017 Tom Butler <tom@r.je> | https://r.je/                      *
 * @license         http://www.opensource.org/licenses/bsd-license.php  BSD License *
 * @version         1.2                                                             */
namespace Punbb;

/** Assigns all the basic functions, data(), key(), iteration(), template(), etc    */
class Functions implements \Transphporm\Module {

	public function load(\Transphporm\Config $config) {
		$functionSet = $config->getFunctionSet();

		$functionSet->addFunction('forum_link', new \Punbb\Url());
		
	}
}
