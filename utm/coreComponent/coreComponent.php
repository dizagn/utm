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
 * Cette classe est celle dont herite tous les composants du framework
 * Elle contient les methodes magique __call, et __get utilisées pour gérer les
 * appels aux plugins.
 **/
class coreComponent
{
    /**
     * utilisation de la methode __call pour recuperer les appels de methodes
     * non définies dans les classes filles, pour l'utiliser dans un plugin
     * /!\ ****
     * La methode peut etre présente dans plusieurs plugins, mais elle ne
     * sera volontairement executée que dans le premier plugin enregistré,
     * contrairement a ce qui est fait pour les appels aux evenements.
     * /!\ ****
     * @param $p_sMethod string Methode appelée
     * @param $p_aArguments array Tableau d'arguments passés à la méthode
     * @return mixed Le resultat de l'appel à la methode du plugin
     **/
    public function __call($p_sMethod, $p_aArguments){
        // On parcours les plugins à la recherche de la methode demandée
        if(TRUE == isset(corePlugin::$m_aPlugin[$p_sMethod])){
            // On instancie le plugin si l'objet n'existe pas deja dans le
            // registre.
            if(FALSE == coreRegistry::exists(corePlugin::$m_aPlugin[$p_sMethod][0], core::$config['registry']['plugin'])){
                coreRegistry::set(corePlugin::$m_aPlugin[$p_sMethod][0], new corePlugin::$m_aPlugin[$p_sMethod][0](), core::$config['registry']['plugin']);
            }
            // On renvoi le resultat de la méthode du plugin appelée dynamiquement
            return call_user_func_array(array(coreRegistry::get(corePlugin::$m_aPlugin[$p_sMethod][0], core::$config['registry']['plugin']), $p_sMethod),
                                        $p_aArguments) ;
        }
        throw new Exception('la methode "'.$p_sMethod.'" n\'existe pas parmi les plugins chargés.');
    }

    /**
     * Gere les alias défini dans le framework
     * Ex: Renvoi le nom du module, du controller, ou de l'action utilisé selon le
     * nom générique (module, controller, action).
     * @param $p_sElement string Nom d'un élément de la requete
     * @return string Valeur de l'élément appelé
     */
    public function __get($p_sElement){
        // Appel a une methode d'un plugin si elle existe. Permet ainsi la syntaxe alternative
        // $this->method() ou $this->pluginName->method()
        if(TRUE == in_array($p_sElement, corePlugin::$m_aRegistredPlugin)){
            if(FALSE == coreRegistry::exists($p_sElement, core::$config['registry']['plugin'])){
                coreRegistry::set($p_sElement, new $p_sElement(), core::$config['registry']['plugin']) ;
            }
            return coreRegistry::get($p_sElement, core::$config['registry']['plugin']) ;
        }
        throw new Exception('La variable "'.$p_sElement.'" n\'existe pas' ) ;
    }
}