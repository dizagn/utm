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
 * @copyright  Copyright (c) 2002-2010 Dizagn. (http://www.dizagn.com)
 * @link http://framework.dizagn.com
 * @author N.Namont Dizagn 2008
 * @version : $Id: coreModel.php 56946 2017-08-23 08:55:05Z n.namont@uniteam.fr $
 *
 * @file
 * Cette classe contient la liste des methodes utilisables dans une classe
 * modèle.
 **/
class coreModel extends coreComponent
{
    /**
     * Fabrique d'objet : utilisé pour générer l'instance d'une classe modèle
     * @param $p_sClass string Nom de la classe à instancier
     * @param $p_aParam array Tableau de Parametres a passer au constructeur
     * @param $p_bNew boolean Indique si on souhaite récupérer une nouvelle instance
     * ou celle eventuellement présente dans le registre
     * @return object Objet Model instancié
     **/
    public static function factory($p_sClass,$p_aParam=NULL, $p_bNew = FALSE)
    {
        // Utilisation d'un singleton basé sur le registre pour mettre en cache
        // l'objet model instancié, sauf si le parametre $p_bNew est faux
        if(TRUE == coreRegistry::exists($p_sClass, core::$config['registry']['model'])&&
           $p_bNew == FALSE){
            return coreRegistry::get($p_sClass, core::$config['registry']['model']);
        }

        if(TRUE == file_exists(core::$config['path']['model'].$p_sClass.'.php')){
            require_once core::$config['path']['model'].$p_sClass.'.php';
        }else{
            throw new Exception('La classe modèle :'. core::$config['path']['model'].$p_sClass.'.php est introuvable');
        }
        
        if($p_aParam!=NULL){
            $l_oClass = new $p_sClass(implode(',',$p_aParam));
        }
        else{
            $l_oClass = new $p_sClass();
        }
        coreRegistry::set($p_sClass, $l_oClass, core::$config['registry']['model']) ;
        return $l_oClass ;
    }
}
