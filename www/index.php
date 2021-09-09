<?php
/**
 * UTM Framework
 *
 * LICENSE
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.dizagn.com/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@dizagn.com so we can send you a copy immediately.
 *
 * @license http://framework.dizagn.com/license  New BSD License
 * @copyright  Copyright (c) 2002-2021 Dizagn. (http://www.dizagn.com)
 * @link http://framework.dizagn.com
 * @author N.Namont Dizagn 2008
 */

/**
 * On inclus le fichier coeur du framework
 * Puis on créé l'unique instance du framework en appelant la methode static
 * "instance". Puis on enregistre un ou plusieurs plugins et on lance le
 * framework avec la methode "run".
 **/
require_once('../utm/core.php') ;
if(TRUE == file_exists('../vendor/autoload.php')){
    require_once('../vendor/autoload.php') ;
}else{
    die("You should probably run \"composer install\" first ;) !") ;
}

// On crée l'instance
$mvc = core::instance() ;

// On enregistre les plugins de base
$mvc->registerPlugin('debug','utmerror');

// On enregistre les plugins complémentaires en fonction du besoin
$mvc->registerPlugin('env',/*'session',*/'phpTemplate','compressor',/*'userManager','db','form',*/'rewrite');

// On lance l'execution du framework
$mvc->run() ;

// test
