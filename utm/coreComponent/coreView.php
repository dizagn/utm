<?php
/**
 * UTM Framework
 *
 * LICENSE
 *
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
 * Cette classe sert d'interface pour les classes vues étendues pour garantir
 * l'implémentation d'une methode render()
 **/
class coreView extends coreComponent
{
    /**
     * Permet d'inclure un sous composant
     * @param $p_sController string Nom du controller
     * @param $p_sAction string     Nom de l'action
     * @param $p_sModule string     Nom du module
     * @param $p_aGet array         Tableau de parametres GET supplémentaires
     * @param $p_aPost array        Tableau de parametres POST supplémentaires
     * @param $p_aCli array         Tableau de parametres CLI supplémentaires
     */
    public function includeComponent($p_sController,$p_sAction, $p_sModule=NULL, $p_aGet = NULL, $p_aPost = NULL, $p_aCli = NULL)
    {
        $l_oRequest = new coreRequest(core::$config['request']) ;
        $l_oRequest->setFakeRequest($p_sController,$p_sAction,$p_sModule, $p_aGet, $p_aPost, $p_aCli) ;
        // On recupere les methodes à appeler
        $l_sControllerMethod = core::$config['core']['controller_method'];
        $l_sViewMethod = core::$config['core']['view_method'] ;
        // On execute le controlleur
        core::instance()->$l_sControllerMethod($l_oRequest);
        // Puis on retourne la vue
        return core::instance()->$l_sViewMethod($l_oRequest) ;
    }
}
