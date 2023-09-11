<?php
/**
 * Plugin de gestion du multi langue dans utm.
 *
 * @version 1.1.0
 *
 * Liste des changements :
 * - 1.1.0 : on récupère les langues autorisées depuis la configuration.
 * - 1.2.0 : on ajoute un paramètre "debugLang" pour préfixer les traductions 
 *           et identifier les textes écrits en dur.
 */
class lang extends corePlugin
{
    protected $m_sLang ;
    protected $m_aLangAvailable = array() ;
    protected $m_aItem = array() ;
    protected $m_bHasCustomTemplate = false;
    protected $m_sDefaut = 'en' ;

    // au lancement du plugin on initialise la langue
    public function onStart(){

        /**** 
         * INIT
         ******/
        // On init les langues dispos et celle par defaut
        if (isset(core::$config['lang']['languages_availables'])) {
            $this->m_aLangAvailable = explode('|', core::$config['lang']['languages_availables']);       
            $this->m_sDefaut = $this->m_aLangAvailable[0];       
        }
                
        /**** 
         * DETECTION DE LA LANGUE
         ******/
        // On détermine la langue en fonction du domaine.
        if(TRUE == isset(core::$config['lang']['domain']) && TRUE == isset($_SERVER[core::$config['lang']['domain']])){
            $language = $this->getLanguageFromHost($_SERVER[core::$config['lang']['domain']]);
            $this->setLanguage($language);
        } 
        // Sinon on récupère la langue depuis une variable $_SERVER.
        else if(TRUE == isset(core::$config['lang']['server']) && TRUE == isset($_SERVER[core::$config['lang']['server']])){
            $this->setLanguage($_SERVER[core::$config['lang']['server']]);
        }
        // Sinon on teste si elle est présente à la racine de l'URI (ex : en/contact-us)
        else if(TRUE == $this->getLangFromUri()){
            $this->setCookieLang() ;
        }
        // sinon on vérifie si on l'a recu par un param uri,
        else if(TRUE == isset($_GET['lang']) && TRUE == in_array($_GET['lang'], $this->m_aLangAvailable )  ){
            $this->setLanguage($_GET['lang']) ;
            $this->setCookieLang() ;
        }
        // On regarde si elle existe deja dans un cookie
        else if(TRUE == isset($_COOKIE[core::$config['lang']['cookie_name']]) && 
                TRUE == in_array($_COOKIE[core::$config['lang']['cookie_name']], $this->m_aLangAvailable)){
            $this->setLanguage($_COOKIE[core::$config['lang']['cookie_name']]) ;
        }
        // Enfin sinon on se base sur le navigateur pour déterminer la langue
        else{ 
            // extract and setLanguage
            $l_sLang = $this->extractBrowserLanguage();
            if (FALSE != $l_sLang){
                $this->setLanguage($l_sLang) ;
                $this->setCookieLang() ;
            }        
        }

        /**** 
         * chargement des traductions
         ******/
        // On charge le fichier correspondant à la langue
        if(TRUE == file_exists(core::$config['lang']['path'].$this->m_sLang.'.php')){
            include(core::$config['lang']['path'].$this->m_sLang.'.php');
            $this->m_aItem[$this->m_sLang] = $l_aItem ;
        }   
        // Sinon on cherche une langue dispo  
        else{
            foreach($this->m_aLangAvailable AS $l_sValue){
                if(TRUE == file_exists(core::$config['lang']['path'].$l_sValue.'.php')){
                    include(core::$config['lang']['path'].$l_sValue.'.php');
                    $this->m_aItem[$l_sValue] = $l_aItem ;
                    $this->setLanguage($l_sValue) ;
                    $this->setCookieLang() ;
                    break ;
                }else{
                    throw new Exception( 'Lang file "'.$l_sValue.'.php"'.'declared but not found !') ;
                }
            }
        }
    }

    // accesseur 
    public function setLanguage($p_sLang){
        $this->m_sLang = $p_sLang;
    }

    protected function getLanguageFromHost($host) 
    {
        foreach ($this->m_aLangAvailable as $lang) {
            if (isset(core::$config['host']['domain_'.$lang]) 
                && core::$config['host']['domain_'.$lang] == $host) {
                return $lang;
            }
        }
        
        return $this->m_sLang;
    }
    
    // Extrait le code langue depuis le navigateur
    protected function extractBrowserLanguage(){
        
        if(TRUE == isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && FALSE == empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
            $l_sLang = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2));
            return $l_sLang;
        }
        return FALSE ;
    }

    // place un cookie contenant la langue choisie par l'internaute
    protected function setCookieLang(){
        return setcookie(core::$config['lang']['cookie_name'], $this->m_sLang, time()+60*60*24*30, '/') ;
    }

    // retourne le code langue utilisé par l'internaute
    public function getLanguageCode() {
        return (TRUE == isset($this->m_sLang)) ? $this->m_sLang : FALSE ;
    }

    // retourne le code langue utilisé par l'internaute
    public function getLanguageAvailable() {
        return (TRUE == isset($this->m_aLangAvailable)) ? $this->m_aLangAvailable : FALSE ;
    }

    /**
     * 
     */
    protected function getLangFromUri(){
        $l_sPattern = '&^/([a-zA-Z]{2,3})\/&';
        if(preg_match($l_sPattern, $_SERVER['REQUEST_URI'],$l_sLang)){
            
            if(TRUE == in_array($l_sLang[1], $this->m_aLangAvailable)){
                $this->setLanguage($l_sLang[1]) ;
                return TRUE ;
            }
        }
        return FALSE;
    }
    
    /**
     * Retourne la traduction correspondante à l'id donné.
     * 
     * @param string $p_sItem id du texte à traduire.
     * @param array $p_aPlaceholders (optionnel) une paire nom/valeur.
     * 
     * @return string
     */
    public function t($p_sItem, array $p_aPlaceholders = []): string
    {
        $l_sItem = '#UNDEFINED['.$p_sItem.']#';
        
        if (isset($this->m_aItem[$this->m_sLang][$p_sItem])) {
            $l_sItem = isset($_GET['debugLang']) ? '['.$this->m_sLang.'] - ' : '';
            $l_sItem.= $this->m_aItem[$this->m_sLang][$p_sItem];
        }
        
        // Remplace un ou plusieurs textes à l'intérieur d'une chaîne à traduire.
        // Permet de dynamiser une chaine de texte traduite (ex. prénom, nom etc.).
        // On peut aussi l'utiliser pour mettre des liens (ex. ['url.site' => $this->rewrite('...')] )).
        if (!empty($p_aPlaceholders)) {
            $l_sItem = str_replace(
                array_map(function($word) { return '[['.$word.']]'; }, array_keys($p_aPlaceholders)), 
                array_values($p_aPlaceholders), 
                $l_sItem
            );
        }
        
        return $l_sItem;
    }
    
    
    /**
     * @todo : a revoir ... 
     */
    public function getTranslationId(string $p_sItem) : string
    {
        return array_search($p_sItem, $this->m_aItem[$this->m_sLang]);
    }
    
    public function loadTranslations(string $p_sLang): void
    {
        include_once core::$config['lang']['path'].$p_sLang.'.php';
        
        if (isset($l_aItem)) {
            $this->m_aItem[$p_sLang] = $l_aItem;
        }
        
    }
    
}
