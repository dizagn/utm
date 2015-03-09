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
 * @version : $Id$
 *
 * @file
 * Cette classe gère les appels aux plugins. Soit par un appel a un evenement
 * soit par une extension des fonctionnalités du framework
 *
 **/
class corePlugin extends coreComponent
{
    static public $m_aPlugin = array(); /*!< Tableau contenant la liste des methodes et des classes associées */
    static public $m_aRegistredPlugin = array() ; /*<! Tableau contenant la liste des plugins chargés */

    /**
     * Enregistre les plugins dans la liste des plugins
     * @param $p_aPlugin array Tableau des plugins a enregistrer
     */
    public static function register($p_aPlugin){
        foreach($p_aPlugin AS $l_sValue){
            if(TRUE == is_string($l_sValue))
            self::$m_aRegistredPlugin[] = $l_sValue;
        }
    }

    /**
     * Recupere toutes les méthodes publiques des plugins : evenements/extension
     * Affecte la liste des methodes(clés) et des classes associés(valeurs)
     **/
    public static function initPlugin(){
        // On recupere pour chaque plugin enregistré ses methodes publiques
        foreach(self::$m_aRegistredPlugin AS $plugin){
            $l_aMethod = get_class_methods($plugin) ;
            if(FALSE == is_array($l_aMethod)){
                throw new Exception ('Le plugin '.$plugin.' n\'existe pas, ou ne contient aucune methode publique');
            }
            foreach($l_aMethod AS $l_sMethod){
                self::$m_aPlugin[$l_sMethod][] = $plugin ;
            }
        }
    }

    /**
     * Emet un evenement, et execute toutes les methodes associées
     * @todo Doit on permettre de passer des parametres a un evenement ?
     * @param $p_sEvent string Evenement déclenché
     **/
    public static function emit($p_sEvent){
        // Si il existe une methode dans les plugins chargés correspondants à
        // l'événement déclenché, on l'execute.
        if(TRUE == isset(self::$m_aPlugin[$p_sEvent])){
            // On la recherche dans tous les plugins
            foreach (self::$m_aPlugin[$p_sEvent] AS $l_sClass){
                // On verifie si l'objet n'existe pas deja dans le registre,
                // sinon on l'instancie et on le stock
                if(TRUE == coreRegistry::exists($l_sClass, core::$config['registry']['plugin'])){
                    $l_oPlugin = coreRegistry::get($l_sClass, core::$config['registry']['plugin']) ;
                }
                else{
                    $l_oPlugin = new $l_sClass() ;
                    coreRegistry::set($l_sClass, $l_oPlugin, core::$config['registry']['plugin']);
                }
                // On execute la methode du plugin
                $l_oPlugin->$p_sEvent() ;
            }
        }
    }

    /**
     * Indique si le plugin demandé est chargé, utile pour la dépendance entre
     * plugin
     * @param string $p_sPlugin Nom du plugin a vérifier
     * @return boolean TRUE si le plugin est chargé, FALSE si il ne l'est pas
     */
    public function isLoaded($p_sPlugin){
        return in_array($p_sPlugin, self::$m_aRegistredPlugin) ;
    }
}
