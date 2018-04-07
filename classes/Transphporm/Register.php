<?php

/* @description     Transformation Style Sheets - Revolutionising PHP templating    *
 * @author          Tom Butler tom@r.je                                             *
 * @copyright       2017 Tom Butler <tom@r.je> | https://r.je/                      *
 * @license         http://www.opensource.org/licenses/bsd-license.php  BSD License *
 * @version         1.2                                                             */
namespace Punbb\Transphporm;

use \Punbb\Transphporm\TSSFunction\Translator;
//use \Punbb\Transphporm\Formatter\Translator;
use \Punbb\Transphporm\TSSFunction\Url;
use \Punbb\Transphporm\TSSFunction\ValueReader;

/** Assigns all the basic functions, data(), key(), iteration(), template(), etc    */
class Register implements \Transphporm\Module {

    private $params;

    /**
     * 
     * @var Translator
     */
    private $translator;
    
    public function __construct()
    {
        $this->translator = new Translator();
    }
    
    public function getTranslator() {
        return $this->translator;
    }
    
	public function load(\Transphporm\Config $config) {
	    
		$functionSet = $config->getFunctionSet();
		$functionSet->addFunction('forum_link', new Url());
		$functionSet->addFunction('translate',  $this->translator, $functionSet, 'translator');
		$functionSet->addFunction('getVal',  new \Punbb\Transphporm\TSSFunction\ValueReader());
		$functionSet->addFunction('csrf',  new \Punbb\Transphporm\TSSFunction\CsrfToken());
		$functionSet->addFunction('friendly',  new \Punbb\Transphporm\TSSFunction\Friendly());
		$functionSet->addFunction('debug',  new \Punbb\Transphporm\TSSFunction\Edebug());
		$config->registerFormatter(new \Punbb\Transphporm\Formatter\ForumTime());
		
	}
}
