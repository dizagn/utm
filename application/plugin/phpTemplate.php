<?php
/**
 * UTM Framework / plugin de rendu de page html/php
 * @name phpTemplate
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
 * @author N.Namont Dizagn 2012
 * @author : K.Queret
 * @version : $Id: phpTemplate.php 56946 2017-08-23 08:55:05Z n.namont@uniteam.fr $
 *
 * @todo : Gérer les conflits de variables entre différents stacks et le layout
 *
 * @file
 * Gestionnaire de rendu d'une vue basée directement sur php
 */
class phpTemplate extends corePlugin{

    protected $m_iStackId = 0;
    protected $m_aValue = array('globals' => array()) ; // tableau des valeurs des variables php
    protected $m_aStack ; // pile lors les appels a des inclusions de templates
    // Spécifique au layout
    protected $m_sContent; // Contenu a rendre dans le cas d'un layout
    protected $m_sLayout = FALSE; // permet de savoir si on est dans un layout
    protected $m_sMainKey; // Premet de définir la variable de remplacement du contenu principal dans un layout

    // Méta-datas
    protected $m_aMetas = array() ;
    protected $m_sTitle = '' ;

    /**
     * Chargement du template, ce fonctionnement différe légerement pour le layout
     * @param string $p_sTemplate nom du template à charger
     */
    public function load($p_sTemplate){
       if(TRUE == $this->m_sLayout){
          $this->m_aStack[$this->nextStack()]['template'] = realpath($this->getLayoutPath().$p_sTemplate) ;
       }
       else{
           $this->m_aStack[$this->nextStack()]['template'] = realpath($this->getPath().$p_sTemplate) ;
       }
    }

    /**
     * Renvoi le chemin des templates pour un composant ou un layout
     * @return type
     */
    private function getPath(){
        return core::$config['path']['template'].core::instance()->getRequest()->getController().'/';
    }
    private function getLayoutPath(){
        return core::$config['path']['template'].core::$config['phpTemplate']['layout_ctrl'].'/' ;
    }

    /**
     * Manipule les stacks de stockage des données de templating
     * @return type
     */
    private function currentStack(){
        return $this->m_iStackId ;
    }
    private function nextStack(){
        return $this->m_iStackId++ ;
    }
    private function previousStack(){
        return --$this->m_iStackId ;
    }

    /**
     * permet d'affecter une valeur a une variable du template selon le stack
     * en cours. Ces variables sont temporaires. Elles sont effacées des le rendu
     * effectué (output).
     * @param type $p_sVarName nom de la variable php
     * @param type $p_mValue  valeur à donner à cette variable
     */
    public function setVar($p_sVarName, $p_mValue, $p_bGlobal = FALSE){
        $this->m_aValue[$p_bGlobal ? 'globals' : $this->currentStack()][$p_sVarName] = $p_mValue ;
    }

    /**
     * Renvoi le contenu du layout avec le contenu du composant request demandé
     * dans la variable définie comme principale
     * @param type $p_sLayout
     * @return string Contenu du composant avec le layout a afficher
     */
    protected function layoutOutput($p_sLayout){

        $l_oRequest = new coreRequest(core::$config['request']) ;
        $l_oRequest->setFakeRequest(core::$config['phpTemplate']['layout_ctrl'],$p_sLayout) ;
        // On recupere les methodes à appeler
        $l_sControllerMethod = core::$config['core']['controller_method'];
        $l_sViewMethod = core::$config['core']['view_method'] ;
        // On execute le controlleur
        core::instance()->$l_sControllerMethod($l_oRequest);
        // Puis on retourne la vue
        return core::instance()->$l_sViewMethod($l_oRequest) ;
    }

    /**
     * Methode de rendu d'une vue et de son template
     * @param string $p_sLayout Precise le nom du layout dans lequelle il faut
     * rendre la vue courante
     * @param string $p_sMainKey Précise le nom de la variable php devant
     * accueillir le conteu de la vue en cours
     * @return string Contenu du template traité
     */
    public function output($p_sLayout=NULL, $p_sMainKey=NULL)
    {
        // On valide qu'il y a bien des variables de setter
        if(TRUE != empty($this->m_aValue[$this->currentStack()])){
            // Affectation des variables
            foreach($this->m_aValue[$this->currentStack()] AS $l_sVar => $l_mValue){
                $$l_sVar = $l_mValue;
            }
        }
        // On applique les variables globales
        if(TRUE != empty($this->m_aValue['globals'])){
            // Affectation des variables
            foreach($this->m_aValue['globals'] AS $l_sVar => $l_mValue){
                $$l_sVar = $l_mValue;
            }
        }
        // idem mais pour le remplacement du mot clé défini pour le remplacement
        // de la variable principale dans le template dans le cas d'un layout
        if(TRUE == $this->m_sLayout){
            $tmp = $this->m_sMainKey;
            $$tmp = $this->m_sContent;
        }

        // Lancement du buffer->récupération du contenu->clean/stop buffering
        ob_start();
        require_once($this->m_aStack[$this->previousStack()]['template']);
        $l_sContent = ob_get_contents();
        ob_end_clean();

        // Dans le cas du layout ou utilise le rendu d'un composant
        // (équivalent de includeComponent) pour récupérer le conteu d'une vue
        if(NULL != $p_sLayout){
            $this->m_sLayout = TRUE;
            $this->m_sMainKey = ($p_sMainKey == NULL) ? core::$config['phpTemplate']['main_key'] : $p_sMainKey;
            $this->m_sContent = $l_sContent;
            $l_sContent = $this->layoutOutput($p_sLayout,$p_sMainKey);
        }
        // On nettoye les valeur mise dans la stack courante pour éviter de créer
        // des conflits, et pour alléger la charge mémoire.
        unset($this->m_aValue[$this->currentStack()+1]);

        return $l_sContent;
    }

    /**
     * Définit le titre de la page
     * @param string $p_sTitle titre de la page
     */
    public function setTitle($p_sTitle){
        $this->m_sTitle = $p_sTitle ;
    }

    /**
     * Retourne le titre de la page
     * @return string titre de la page
     **/
    public function getTitle(){
        return $this->m_sTitle ;
    }

    /**
     * Ajouter une entête HTML
     * @param string $p_sTag type de tag (balise)
     * @param string $p_sName nom de l'entête (interne)
     * @param array $p_aParams attribus du tag
     **/
    public function addHtmlHead($p_sTag, $p_sName, array $p_aParams){
        $this->m_aMetas[$p_sTag][$p_sName] = $p_aParams;
    }

    /**
     * Retourne les paramètres d'une ou plusieurs entêtes HTML
     * @param string $p_sTag filtre sur un tag
     * @param string $p_sTagName résupère une entête spécifique
     * @return array Tableau de paramètres
     **/
    public function getHtmlHead($p_sTag = NULL, $p_sTagName = NULL){
        if (NULL == $p_sTag) {
            return $this->m_aMetas;
        }
        elseif (isset($this->m_aMetas[$p_sTag]) && NULL == $p_sTagName) {
            return $this->m_aMetas[$p_sTag];
        }
        elseif (isset($this->m_aMetas[$p_sTag][$p_sTagName])) {
            return $this->m_aMetas[$p_sTag][$p_sTagName];
        } else {
            return array();
        }
    }

    /**
     * Génère le HTML des entêtes
     * @param string $p_sTag filtre sur un tag
     * @return string HTML correspondant
     **/
    public function renderHtmlHead($p_sTag = NULL){
        if (NULL == $p_sTag) {
            $l_aTags = $this->m_aMetas;
        }
        elseif (isset($this->m_aMetas[$p_sTag])) {
            $l_aTags = array($this->m_aMetas[$p_sTag]);
        }
        else {
            return '' ;
        }
        $l_sReturn = '';
        foreach ($l_aTags as $l_sTagName => $l_aTag) {
            foreach ($l_aTag as $l_sName => $l_aParams) {
                $l_sReturn .= '<' . $l_sTagName ;
                foreach ($l_aParams as $l_sParam => $l_sValue) {
                    $l_sReturn .= ' ' . $l_sParam . '="' . htmlentities($l_sValue, ENT_COMPAT) . '"' ;
                }
                $l_sReturn .= "/>\n" ;
            }
        }
        return $l_sReturn ;
    }
}