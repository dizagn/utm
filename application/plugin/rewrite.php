<?php
/**
 * UTM Framework :: Plugin rewrite
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
 * @author K.Queret
 *
 *
 * @file
 * Gestionnaire de réécriture d'url
 */
class rewrite extends corePlugin
{
    protected $m_aRoute = array();
    protected $m_aRouteByLang = array();
    protected $m_sDelimitor = '#';
    protected $m_bEnabled = FALSE;
    protected $m_sLang = FALSE;
    protected $m_aLangAvailable = array();
    protected $m_bRouteFinded = FALSE;

    protected $m_sQueryString = NULL;
    protected $m_sBase = "/";

    public function __construct() {

        // On détermine si le plugin lang est chargé et sa valeur en cours
        if(TRUE == $this->isLoaded('lang')){
            $this->m_sLang = $this->getLanguageCode();
            $this->m_aLangAvailable = $this->getLanguageAvailable();   
            if(FALSE != $this->m_sLang){
                $this->m_aRouteByLang = $this->getRouteByLang($this->m_sLang);
            }         
        }

        $this->m_aRoute = $this->mergeRoute(); 
        $this->m_aRedirections = core::$config['rewrite_redirections'];
        $this->m_sDelimitor = '#';
        $this->m_bEnabled = FALSE;
        if(PHP_SAPI != 'cli'){
            $this->m_bEnabled = core::$config['rewrite']['enabled'];
            // patch windows
            $l_sBase = str_replace("\\","/",dirname($_SERVER['SCRIPT_NAME'])) ;
            $this->m_sBase = rtrim($l_sBase, '/') . '/' ;
            $this->m_sUrl = substr(parse_url($_SERVER['REQUEST_URI'],  PHP_URL_PATH), strlen($this->m_sBase)) ;
            $this->m_sQueryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        }       

        
        $this->m_bRouteFinded = FALSE;
    }

    /**
     * On Start :
     * Transforme l'uri en objet GET pour le framework
     * Ex : contact.html -> crtl=contact&act=show
     */
    public function onStart(){

        // Si la réécriture est désactivée ou que la request est égale à "/index.php" ou "/"
        if (FALSE == $this->m_bEnabled || FALSE === $this->m_sUrl || parse_url($_SERVER['REQUEST_URI'],  PHP_URL_PATH) == $_SERVER['SCRIPT_NAME']) {
            return FALSE;
        }

        // Rewriting
        foreach ($this->m_aRoute as $l_sRuleName => $l_aRule) {
            
            foreach($l_aRule AS $l_sPattern => $l_sRequest){
                
                $l_sPattern = $this->m_sDelimitor . str_replace($this->m_sDelimitor, '\\' . $this->m_sDelimitor, $l_sPattern) . $this->m_sDelimitor;
                
                if (1 == preg_match($l_sPattern, $this->m_sUrl)) {
                    // Remplacement des éléments de l'url réécrite en tant que paramètres de la query string 
                    // pour qu'ils s'insert dans le fonctionnement normal du framework (coreRequest)
                    $l_sString = preg_replace($l_sPattern, $l_sRequest, $this->m_sUrl);
                    // Combinaison des paramètres déjà présents dans l'url réécrite
                    $l_sString .= ($this->m_sQueryString ? '&' . $this->m_sQueryString : NULL);
                    // Mise à jour de l'objet _GET
                    parse_str($l_sString, $_GET);
                    // et de la variable serveur QUERY_STRING
                    $_SERVER['QUERY_STRING'] = $l_sString;
                    $this->m_bRouteFinded = $l_sRuleName;                    
                }
            }
        }
        
        // Redirections
        foreach ($this->m_aRedirections as $l_sRuleName => $l_aRule) {
            $l_sPattern = key($l_aRule);
            $l_sRequest = current($l_aRule);
            // On construit le pattern complet
            $l_sPattern = $this->m_sDelimitor . str_replace($this->m_sDelimitor, '\\' . $this->m_sDelimitor, $l_sPattern) . $this->m_sDelimitor;
            if (1 == preg_match($l_sPattern, $this->m_sUrl)) {
                // Remplacement des éléments de l'url réécrite en tant que paramètres de la query string 
                // pour qu'ils s'insert dans le fonctionnement normal du framework (coreRequest)
                $l_sString = preg_replace($l_sPattern, $l_sRequest, $this->m_sUrl);
                header("Location: " . $this->m_sBase . $l_sString, true, 301);
                exit;
            }
        }

        // 404
        if (FALSE === $this->m_bRouteFinded) {
            throw new Exception('Plugin rewrite : unknown URL "'.$this->m_sUrl.'"');
        }
    }

    /**
     * Transforme une regle de rewrite en URI en tenant compte de la langue en cours
     * Rule : "contact' -> fr/contact.html
     */
    public function rewrite($p_sRuleName, array $p_aParams = NULL, string $p_sLang = NULL){

        if (empty($this->m_aRoute[$p_sRuleName])) {
            throw new Exception('Plugin rewrite : unknown route "'.$p_sRuleName.'"');
        }

        // On verifie si il y a une version traduite de l'uri dans la config sinon on prend la valeur par defaut
        if(TRUE == isset($this->m_aRouteByLang[$p_sRuleName])){
            $l_sPattern = key($this->m_aRouteByLang[$p_sRuleName]);
            $l_sRequest = current($this->m_aRouteByLang[$p_sRuleName]);
        }else{
            $l_sPattern = key($this->m_aRoute[$p_sRuleName]);
            $l_sRequest = current($this->m_aRoute[$p_sRuleName]);
        }

        // Si le rewrite est désactivé
        if (FALSE == $this->m_bEnabled) {            
            return $this->getBase() . "?" . preg_replace_callback(
                '#\$([0-9]+)#', 
                function($match) use ($p_aParams) {
                    return isset($p_aParams[$match[1] - 1]) ? $p_aParams[$match[1] - 1] : NULL;
                },
                $l_sRequest
            );
        } 
        else {
            $n = 0;
            $l_sRegEx = '#\(([^\)]+)\)#' ;
            
            // ON (re)vérifie dans le cas du changement de langue si le param est
            // bien passé quand on en a besoin
            if( NULL == $p_aParams && 1 == preg_match($l_sRegEx, $l_sPattern) ){
                return $this->getBase() . '?lang='.$p_sLang;
            }

            $l_sUri = trim(preg_replace_callback(
                $l_sRegEx,                 
                function($match) use ($p_aParams, $p_sRuleName, &$n) {
                    if (!isset($p_aParams[$n])) {
                        return FALSE;
                        //throw new Exception('Plugin rewrite : empty value for pattern "'.$match[1].'" in rule "'.$p_sRuleName.'"');
                    }
                    elseif (!preg_match('#^' . $match[1] . '$#', $p_aParams[$n])) {
                        return FALSE;
                        //throw new Exception('Plugin rewrite : value "'.$p_aParams[$n].'" invalid for pattern "'.$match[1].'" in rule "'.$p_sRuleName.'"');
                    }
                    return $p_aParams[$n++];
                },
                $l_sPattern
            ), '^$');

            return $this->getBase() . $l_sUri;
        }
    }

    public function rewriteCurrentIn($p_sLang){
        // Si on est dans la langue en cours on renvoi direct l'uri
        if($p_sLang == $this->m_sLang){
            return $this->getCurentUrl();
        }
        // Sinon on cherche son equivalent
        // On stocke la route courrante en temp
        $l_aRouteTmp = $this->m_aRouteByLang;
        // O recupere la liste des routes de la langue
        $this->m_aRouteByLang = $this->getRouteByLang($p_sLang);
        $l_sUri = $this->rewrite($this->m_bRouteFinded,NULL, $p_sLang );
        
        // Cas particulier ou la route existe pas dans la traduction
        if(FALSE == array_key_exists($this->m_bRouteFinded, $this->m_aRouteByLang )){
            return $this->getBase() . '?lang='.$p_sLang;
        }

        $this->m_aRouteByLang = $l_aRouteTmp ;
        return $l_sUri ;
    }

    public function getCurentUrl($p_bAbsolute = TRUE){
        return $this->getBase($p_bAbsolute) . $this->m_sUrl . ($this->m_sQueryString ? '?' . $this->m_sQueryString : NULL);
    }

    public function getBase($p_bAbsolute = TRUE, $p_sLang=NULL){
        if (!empty(core::$config['rewrite']['host'])) {
            $l_sUrl = core::$config['rewrite']['host'];
        }
        else {
            $l_sUrl = (TRUE == $p_bAbsolute ? $this->getHost() : '') . $this->m_sBase ;
        }
        if($p_sLang!= NULL){
            $l_sUrl .= $p_sLang.'/';
        }
        return $l_sUrl ;
    }

    public function getHost(){

        $scheme = 'http' ;
        
        // Get Protocol 
        if( (TRUE == isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) ||
            (TRUE == isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] =='on') ||
            FALSE == empty($_SERVER['HTTPS'])){
            $scheme = $scheme.'s';
        }

        $host = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST']);
        //$port = (isset($_SERVER['HTTP_X_FORWARDED_PORT']) ? $_SERVER['HTTP_X_FORWARDED_PORT'] : $_SERVER['SERVER_PORT']);
        return $scheme . '://' . $host; //. (($port != 80) || ($port != 443) ? ':'.$port : ''); 
    }


    protected function mergeRoute(){

        $l_aRoute = array();
        $l_sDefaultName = 'rewrite_rules' ;
        // On init le tableau si il y en a un par defaut
        if(TRUE == isset(core::$config[$l_sDefaultName])){                
            $l_aRoute = core::$config[$l_sDefaultName];
        }
        // Ensuite on y ajoute chaque langue
        //var_dump($l_aRoute,$this->m_aLangAvailable) ;
        foreach($this->m_aLangAvailable AS $l_sValue ){
            if(TRUE == isset(core::$config[$l_sDefaultName.'_'.$l_sValue])){                
                $l_aRoute = array_merge_recursive($l_aRoute, core::$config[$l_sDefaultName.'_'.$l_sValue]);
            }
        }
        //var_dump($l_aRoute) ; exit;
        return $l_aRoute ; 
    }

    /**
     * Recupere la liste des route pour une langue
     * @param $p_sLang string : Code langue
     * @return array : liste des routes
     */
    protected function getRouteByLang($p_sLang){

        $l_sDefaultName = 'rewrite_rules' ;

        if(TRUE == in_array($p_sLang, $this->m_aLangAvailable) && 
        TRUE == isset(core::$config[$l_sDefaultName.'_'.$p_sLang])){
            return core::$config[$l_sDefaultName.'_'.$p_sLang] ;
        }
    }
}