<?php
/**
 * UTM Framework :: Plugin session
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
 * @author N.Namont Dizagn 2008
 *
 *
 * @file
 * Gestionnaire de session en fichier
 */
class session extends corePlugin
{
    /**
     * Valeur par defaut de la session
     */
    // Nom de l'identifiant de session
    const DEFAULT_NAME      = 'SESSION_ID' ;
    // Durée de vie en secondes du cookie
    const DEFAULT_LIFETIME  = 3600 ;
    // Chemin dans le domaine pour lequel le cookie sera accessible (ex.: /www )
    const DEFAULT_PATH      = '/' ;
    // Domaine pour lequel le cookie est actif (ex.: www.dizagn.com ou .dizagn.com)
    const DEFAULT_DOMAIN    = '' ;
    // Le cookie doit etre utilisé uniquement en mode HTTPS : 0-false, 1-true
    const DEFAULT_SECURE    = 0 ;
    // Le cookie est accessible uniquement par le protocole HTTP : 0-false, 1-true
    const DEFAULT_HTTP      = 0 ;
    // Type de hashage utilisé : 4:[0-9,a-f] , 5:[0-9,a-v], 6:[0-9,a-z,A-Z,"-",","]
    const DEFAULT_HASH      = 4 ;
    // Demmarage automatique de la session sur l'évenement onStart
    const DEFAULT_AUTO_START= 0 ;
    
    /**
     * A cette evenement on lance la session
     */
    public function onStart(){
        if(TRUE == (TRUE == isset(core::$config['session']['auto_start']))?core::$config['session']['auto_start']:self::DEFAULT_AUTO_START){
            $this->session_start() ;
        }
    }

    /**
     * Demarre une session en mode manuel
     */
    public function session_start(){
        //On lance la session
        $this->sessionStart(isset(core::$config['session']['name'])?core::$config['session']['name']:self::DEFAULT_NAME,
                            isset(core::$config['session']['lifetime'])?core::$config['session']['lifetime']:self::DEFAULT_LIFETIME,
                            isset(core::$config['session']['path'])?core::$config['session']['path']:self::DEFAULT_PATH,
                            isset(core::$config['session']['domain'])?core::$config['session']['domain']:self::DEFAULT_DOMAIN,
                            isset(core::$config['session']['secure'])?core::$config['session']['secure']:self::DEFAULT_SECURE,
                            isset(core::$config['session']['http_only'])?core::$config['session']['http_only']:self::DEFAULT_HTTP,
                            isset(core::$config['session']['hash_mode'])?core::$config['session']['hash_mode']:self::DEFAULT_HASH);
    }

    /**
     * On re-génere l'id de la session. Et on détruit l'ancien fichier
     **/
    public function regenerate(){
        session_regenerate_id(TRUE);
    }

    /**
     * Permet de détruire complétement une session
     **/
    public function sessionStop(){
        $l_aCookie = session_get_cookie_params();
        if(FALSE == headers_sent()){
            setcookie(session_name(),
                      '',
                      time()-3600,
                      $l_aCookie['path'],
                      $l_aCookie['domain'],
                      $l_aCookie['secure'],
                      $l_aCookie['httponly']);
        }
        else{
            throw new Exception('La session n\'a pas pu etre détruite car les entêtes ont deja été renvoyés');
        }
    }

    /**
     * Configure et créé le cookie de session.
     **/
    private function sessionStart($p_sName, $p_sLifetime,$p_sPath,$p_sDomain,$p_bSecure,$p_bHttp,$p_iHash){
        // On définit les paramètres de création du cookie
        session_set_cookie_params($p_sLifetime, $p_sPath, $p_sDomain, $p_bSecure, $p_bHttp);

        // Nom de l'id de session a utilisé
        session_name($p_sName);
        
        // Modèle de hashage a utilisé
        ini_set('session.hash_bits_per_character', $p_iHash) ;

        // On redéfinit le chemin d'acces aux fichiers de session
        $this->setPath(isset(core::$config['session']['directory'])?core::$config['session']['directory']:NULL);

        // Démarrage de la session
        if(FALSE == headers_sent()){
            session_start() ;
        }
        else{
            throw new Exception('La session ne peut démarrer car les entêtes ont déja été envoyés') ;
        }
    }

    /**
     * On définit un chemin différent pour l'emplacement des fichiers de sessions
     * @param string $p_sPath nouveau chemin
     **/
    private function setPath($p_sPath=NULL){
        if(FALSE == empty($p_sPath)){
            $l_sPath = realpath($p_sPath) ;
            if(TRUE == is_dir($l_sPath) &&
               TRUE == is_readable($l_sPath) &&
               TRUE == is_writable($l_sPath)){
                session_save_path($l_sPath) ;
            }
            else{
                throw new Exception('Le dossier alternatif que vous avez définit pour la gestion des sessions doit etre un dossier qui possede les droits en lecture et en ecriture') ;
            }
        }
    }
}