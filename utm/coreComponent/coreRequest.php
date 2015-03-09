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
 * Classe gérant l'objet request. Cet objet est l'netité representatnt la
 * requete au sein du framework. Et ce qu'elle arrive en mode console ou http(
 * Get, Post, etc.)
 */
class coreRequest extends coreComponent{

    const HTTP = 1; /*!< Indique si on accede au framework par un navigateur*/
    const CLI  = 2; /*!< Indique si on accede au framework en ligne de commande*/

    protected $m_aReqElement = array() ;/*!< Elements constituants la requete*/
    protected $m_sRequestType;/*!< Indique si la requete est de type HTTP ou CLI*/
    protected $m_sModule;/*!< Elements module de la requete*/
    protected $m_sController;/*!< Elements controlleur de la requete*/
    protected $m_sAction;/*!< Elements action de la requete*/
    protected $m_aGet;/*!< Elements Get de la requete*/
    protected $m_aPost;/*!< Elements Post de la requete*/
    protected $m_aCli;/*!< Elements CLI de la requete*/

    public function __construct(array $p_aReqElement){
        $this->m_aReqElement = array_flip($p_aReqElement) ;
        $this->m_sRequestType = (PHP_SAPI!='cli') ? self::HTTP : self::CLI ;
        // On definit la valeur par defaut d'un controller et de l'action
        $this->m_sController = core::$config['request']['default'] ;
        $this->m_sAction = core::$config['request']['default'] ;
    }

    /**
     * Parse l'url pour créer l'objet request utilisable par le framework
     */
    public function httpParser(){
        parse_str( $_SERVER['QUERY_STRING'] , $l_aQuery ) ;
        return $l_aQuery ;
    }

    /**
     * Acces CLI (Ligne de commande) au framework
     * Parse la requete et renvoi un tableau contenant ses éléments
     * @todo Dans les futurs version s de PHP on pourra implémenter la meme
     * syntaxe qu'une commande PHP ex: --param value --param2 value etc.
     * @return array Tableau contenant les éléments de la requete
     **/
    protected function cliParser()
    {
        $l_aQuery = array();
        // On recupere chaque valeur fournie sous la forme key=value
        for($i=1 ; $i<$_SERVER['argc'] ; $i++){
            parse_str($_SERVER['argv'][$i], $l_aTemp) ;
            $l_aQuery = array_merge($l_aQuery, $l_aTemp) ;
        }
        return $l_aQuery ;
    }

    /**
     * On definit les membres de l'objet request(Type, elements, params, etc.)
     */
    public function setRequest()
    {
        if($this->m_sRequestType == self::HTTP){
            $l_aQuery = $this->httpParser();
            $this->m_aGet = $_GET ;
            $this->m_aPost = $_POST ;
        }
        else{
            $l_aQuery = $this->cliParser();
            $this->m_aCli = $_SERVER['argv'];
        }

        // On parcours le tableau afin d'y retrouver les clés definies en config
        foreach( $this->m_aReqElement AS $key => $value ){
            if(TRUE == array_key_exists($key, $l_aQuery) && TRUE == is_string($l_aQuery[$key])){
                if($value == 'module'){
                    $this->m_sModule = strip_tags($l_aQuery[$key]);
                }
                if($value == 'controller'){
                    $this->m_sController = strip_tags($l_aQuery[$key]);
                }
                if($value == 'action'){
                    $this->m_sAction = strip_tags($l_aQuery[$key]);
                }
            }
        }
    }

    /**
     * On remplit l'objet request en fonction d'une requete supplémentaire
     * @return array Tableau request
     */
    public function setFakeRequest($p_sController,$p_sAction,$p_sModule=NULL,$p_aGet=NULL,$p_aPost=NULL,$p_aCli=NULL)
    {
        $this->m_sModule = strip_tags($p_sModule);
        $this->m_sController = strip_tags($p_sController);
        $this->m_sAction = strip_tags($p_sAction);
        $this->m_aGet = ($p_aGet!=NULL && TRUE == is_array($p_aGet)) ? $p_aGet : NULL;
        $this->m_aPost = ($p_aPost!=NULL && TRUE == is_array($p_aPost)) ? $p_aPost : NULL;
        $this->m_aCli = ($p_aCli !=NULL && TRUE == is_array($p_aCli))? $p_aCli : NULL ;
    }

    /**
     * Accesseurs
     */
    public function getModule(){
        return $this->m_sModule ;
    }
    public function getController(){
        return $this->m_sController;
    }
    public function getAction(){
        return $this->m_sAction;
    }
    public function getMethod(){
        return $this->m_sRequestType;
    }

    /**
     *
     * @param <type> $p_sElement
     * @return array
     */
    public function getInput($p_sElement = 'get'){

        $l_aInputs = array('get' => 'm_aGet',
                           'post'=> 'm_aPost',
                           'cli' => 'm_aCli') ;

        if(TRUE == isset($this->$l_aInputs[strtolower($p_sElement)])){
            return $this->$l_aInputs[strtolower($p_sElement)];
        }
        return FALSE;
    }
}
