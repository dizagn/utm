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
 * @copyright  Copyright (c) 2002-2010 Dizagn. (http://www.dizagn.com)
 * @link http://framework.dizagn.com
 * @author K.Queret
 * @version: $Id: rewrite.php 56946 2017-08-23 08:55:05Z n.namont@uniteam.fr $
 *
 *
 * @file
 * Gestionnaire de réécriture d'url
 */
class rewrite extends corePlugin
{
    protected $m_aRoute = array();
    protected $m_sDelimitor = '#';
    protected $m_bEnabled = FALSE;

    protected $m_sQueryString = NULL;
    protected $m_sBase = "/";

    public function __construct() {
        $this->m_aRoute = core::$config['rewrite_rules'];
        $this->m_aRedirections = core::$config['rewrite_redirections'];
        $this->m_sDelimitor = core::$config['rewrite']['delimitor'];
        $this->m_bEnabled = FALSE;
        if(PHP_SAPI != 'cli'){
            $this->m_bEnabled = core::$config['rewrite']['enabled'];
            $this->m_sBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/' ;
            $this->m_sUrl = substr(parse_url($_SERVER['REQUEST_URI'],  PHP_URL_PATH), strlen($this->m_sBase)) ;
            $this->m_sQueryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        }       
        $this->m_bRouteFinded = FALSE;
    }
    
    public function onStart(){

        // Si la réécriture est désactivée ou que la request est égale à "/index.php" ou "/"
        if (FALSE == $this->m_bEnabled || FALSE === $this->m_sUrl || parse_url($_SERVER['REQUEST_URI'],  PHP_URL_PATH) == $_SERVER['SCRIPT_NAME']) {
            return FALSE;
        }

        // Rewriting
        foreach ($this->m_aRoute as $l_sRuleName => $l_aRule) {
            $l_sPattern = key($l_aRule);
            $l_sRequest = current($l_aRule);
            // On construit le pattern complet
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
                $this->m_bRouteFinded = TRUE;
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

    public function rewrite($p_sRuleName, array $p_aParams = NULL){

        if (empty($this->m_aRoute[$p_sRuleName])) {
            throw new Exception('Plugin rewrite : unknown route "'.$p_sRuleName.'"');
        }
        
        if (FALSE == $this->m_bEnabled) {
            $l_sRequest = current($this->m_aRoute[$p_sRuleName]);
            return $this->getBase() . "?" . preg_replace_callback(
                '#\$([0-9]+)#', 
                function($match) use ($p_aParams) {
                    return isset($p_aParams[$match[1] - 1]) ? $p_aParams[$match[1] - 1] : NULL;
                },
                $l_sRequest
            );
        } else {
            $l_sPattern = key($this->m_aRoute[$p_sRuleName]);
            // 
            $n = 0;
            return $this->getBase() . trim(preg_replace_callback(
                '#\(([^\)]+)\)#', 
                function($match) use ($p_aParams, $p_sRuleName, &$n) {
                    if (!isset($p_aParams[$n])) {
                        throw new Exception('Plugin rewrite : empty value for pattern "'.$match[1].'" in rule "'.$p_sRuleName.'"');
                    }
                    elseif (!preg_match('#^' . $match[1] . '$#', $p_aParams[$n])) {
                        throw new Exception('Plugin rewrite : value "'.$p_aParams[$n].'" invalid for pattern "'.$match[1].'" in rule "'.$p_sRuleName.'"');
                    }
                    return $p_aParams[$n++];
                },
                $l_sPattern
            ), '^$');
        }
    }

    public function getCurentUrl($p_bAbsolute = TRUE){
        return $this->getBase($p_bAbsolute) . $this->m_sUrl . ($this->m_sQueryString ? '?' . $this->m_sQueryString : NULL);
    }

    public function getBase($p_bAbsolute = TRUE){
        if (!empty(core::$config['rewrite']['host'])) {
            return core::$config['rewrite']['host'];
        }
        else {
            return (TRUE == $p_bAbsolute ? $this->getHost() : '') . $this->m_sBase ;
        }
    }

    public function getHost(){
        $scheme = 'http' . (!empty($_SERVER['HTTPS']) ? 's' : '');
        $host = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST']);
        return $scheme . '://' . $host . ($scheme == 'http' && $_SERVER['SERVER_PORT'] != 80 || $scheme == 'https' && $_SERVER['SERVER_PORT'] != 443 ? ':' . $_SERVER['SERVER_PORT'] : '');
    }
}