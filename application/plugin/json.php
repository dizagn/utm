<?php
/**
 * UTM Framework :: Plugin ajax
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
 * @license http://framework.dizagn.com/license New BSD License
 * @copyright  Copyright (c) 2002-2021 Dizagn. (http://www.dizagn.com)
 * @link http://framework.dizagn.com
 * @author K.Queret 2016
 *
 *
 * @file
 * Gestionnaire de transferts ajax
 */
class json extends corePlugin
{
    protected $m_bHeader = false;
    
    public function onEcho() {
        if (true == $this->m_bHeader){
            // On envoi le contenu JSON si c'est bien un tableau
            if(TRUE == headers_sent()){
                throw new Exception('Erreur interne');
            }
            
            // Arret du debug envoi des headers et du contenu correctement encodé
            $this->debug->stop();	
            
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-type: application/json');
        }
    }
    
    /*
    * Renvoi le tableau au format json 
    */
    public function jsonize($l_aResult) {
        // On vérifie qu'il s'agit bien d'un tableau
        if(FALSE == is_array($l_aResult)){
            $l_aResult = array('status'=> 0 , 'code' => '500') ;
        }

        $this->m_bHeader = true;
        return json_encode($l_aResult);				
    }

    public function isAjax(){
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && preg_match('#xmlhttprequest#i', $_SERVER['HTTP_X_REQUESTED_WITH']);
    }
}