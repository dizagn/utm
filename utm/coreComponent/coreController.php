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
 * 
 * @file
 * Cette classe contient la liste des methodes utilisables dans un controller
 **/
class coreController extends coreComponent
{
    /**
     * Forward l'action : modifie l'objet request et renvoi sur la methode 'execute'
     * d'une autre action, ainsi les entetes ne sont pas renvoyés.
     *
     * @param $p_sController string Nom du controller
     * @param $p_sAction string     Nom de l'action
     * @param $p_sModule string     Nom du module
     * @param $p_aGet array         Tableau de parametres GET supplémentaires
     * @param $p_aPost array        Tableau de parametres POST supplémentaires
     * @param $p_aCli array         Tableau de parametres CLI supplémentaires
     **/
    protected function forward($p_sController,$p_sAction,$p_sModule=NULL,$p_aGet=NULL,$p_aPost=NULL,$p_aCli=NULL)
    {
        $l_oCore = core::instance();
        // On re-définit la requete puis on relance l'execution avec le nouvel objet request
        $l_oCore->resetRequest($p_sController,$p_sAction,$p_sModule,$p_aGet,$p_aPost,$p_aCli) ;
        $l_oCore->execute($l_oCore->getRequest());
    }
    
    /**
     * Redirige vers une autre action (les entetes sont renvoyés)
     *
     * @param $p_sController string Nom du controller
     * @param $p_sAction string     Nom de l'action
     * @param $p_sModule string     Nom du module
     * @param $p_aGet array         Tableau de parametres GET supplémentaires
     **/
    protected function redirect($p_sController,$p_sAction,$p_sModule=NULL,$p_aGet=NULL)
    {
        if(PHP_SAPI == 'cli'){
            throw new Exception('Cette methode '.__METHOD__.' ne peut etre appelée en ligne de commande');
        }

        $l_sUrl = (string)'';
        $l_aReq = core::$config['request'];
        
        if($p_sModule != NULL){
            $l_sUrl = $l_aReq['module'].'='.$p_sModule.'&'; 
        }
        $l_sUrl .= $l_aReq['controller'].'='.$p_sController.'&';
        $l_sUrl .= $l_aReq['action'].'='.$p_sAction.'&';

        // On ajoute les parametres GET
        if($p_aGet != NULL){
            if(FALSE == is_array($p_aGet)){
                throw new Exception ('Les paramêtres fournis pour la redirection doivent etre un tableau') ;
            }
            foreach($p_aGet AS $key => $value){
                    $l_sUrl .= $key .'='.$value .'&' ;
            }
        }

        // On procède à la redirection si les entêtes n'ont pas été envoyés
        if (FALSE == headers_sent()){
            header('Location:index.php?'.rtrim($l_sUrl, '&')); exit;
        }
        else{
            throw new Exception('La redirection est impossible car les entêtes ont déjà été envoyés') ;
        }
    }
    
    /**
     * /!\ A securiser !!
     * @param type $p_sUrl
     */
    public function redirect2url($p_sUrl){
        if (FALSE == headers_sent()){
            header('Location:'.$p_sUrl) ;
        }
        else{
            throw new Exception('La redirection est impossible car les entêtes ont déjà été envoyés') ;
        }
    }
}
