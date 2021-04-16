<?php
/**
 * Plugin d'extension de la gestion des erreurs du framework UTM
 * selon la config
 * - affiche l'erreur
 * - envoi un mail
 * - redirige sur une page spéciale
 *
 **/
class utmerror extends corePlugin
{
    const MAX_ATTEMPT = 10 ;

    public function onError(){
        // Send Email on error
        if(TRUE == isset(core::$config['error']['email'])){
            $this->emailOnError();
        }

        // Log on Error
        if(TRUE == isset(core::$config['error']['logFile'])){
            $this->logOnError();
        }

        // Redirect to error page maybe the 404 one ?
        if(TRUE == isset(core::$config['error']['url404'])){
            $this->redirectOnError();
        }
    }

    /**
     *
     **/
    private function redirectOnError(){
        header('Location:'.core::$config['error']['url404']);
        exit;
    }

    /**
     * Permet d'envoyer un mail en cas d'erreur
     **/
    private function emailOnError(){
        $l_sPattern = '&^[a-z0-9_\-]+(\.[_a-z0-9\-]+)*@([_a-z0-9\-]+\.)+([a-z]{2}|aero|arpa|biz|com|coop|edu|gov|info|int|jobs|mil|museum|name|nato|net|org|pro|travel)$&';
        $l_sSubject = 'Une erreur s\'est produite sur l\'environnement '.core::$config['env'] ;
        $l_sNbMailSend = (TRUE == coreRegistry::exists('mailSend') ? coreRegistry::get('mailSend') : 0 ) ;
        $l_iMaxMailAttempt = (TRUE == isset(core::$config['error']['maxMailAttempt'])) ? core::$config['error']['maxMailAttempt'] : self::MAX_ATTEMPT;

        if(preg_match($l_sPattern, core::$config['error']['email']) && $l_sNbMailSend < $l_iMaxMailAttempt){
            @mail(core::$config['error']['email'], $l_sSubject, coreError::getError('txt').$this->getDebugInfo() );
            coreRegistry::set('mailSend', $l_sNbMailSend+1 ) ;
        }
    }

    /**
     * Permet d'ecriure un fichier de log applicatif contenant les erreurs
     * d'execution du framework
     **/
    private function logOnError(){
        if(TRUE == isset(core::$config['path']['log']) && true == is_writable(core::$config['path']['log'])){
            $l_sMessage = "\nError ".date('Y-m-d H:i:s')." **********************
            \n".coreError::getError('txt').$this->getDebugInfo() ;
            file_put_contents(realpath(core::$config['path']['log']).DIRECTORY_SEPARATOR.core::$config['error']['logFile'], $l_sMessage , FILE_APPEND) ;
        }
    }

    /**
     * Send more infos
     **/
    private function getDebugInfo(){
        $l_sMess = '';
        // On récupere les tableau superglobaux et on les parse
        if(FALSE == isset(core::$config['error']['logLevel'])){
            return ' /!\ LogLevel is not defined /!\ ';
        }
        $l_aSuper = explode('|',core::$config['error']['logLevel']) ;
        $l_aAuthSuper = array('SERVER' => TRUE == isset($_SERVER) ? $_SERVER : '',
                              'REQUEST' => TRUE == isset($_REQUEST) ? $_REQUEST : '',
                              'GET' => TRUE == isset($_GET) ? $_GET : '',
                              'POST' => TRUE == isset($_POST) ? $_POST : '',
                              'COOKIE' => TRUE == isset($_COOKIE) ? $_COOKIE : '',
                              'SESSION' => TRUE == isset($_SESSION) ? $_SESSION : '',
                              'ENV' => TRUE == isset($_ENV) ? $_ENV : '',
                              'FILES' => TRUE == isset($_FILES) ? $_FILES : '') ;

        // on vérifie si la varibale existe et si elle fait partie des
        // elements autorisés
        foreach($l_aSuper AS $l_sSuper){
            if(TRUE == isset($l_aAuthSuper[$l_sSuper]) &&
               TRUE == is_array($l_aAuthSuper[$l_sSuper]) &&
               FALSE == empty($l_aAuthSuper[$l_sSuper])){
                $l_sMess .= "\n------".$l_sSuper."-------- \n" ;
                foreach($l_aAuthSuper[$l_sSuper] AS $l_sKey => $l_sValue){
                    $l_sMess .= $l_sKey.'='.var_export($l_sValue, TRUE)."\n" ;
                }
            }
        }

        return $l_sMess ;
    }
}
