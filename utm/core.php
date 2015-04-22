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
 * @license http://framework.dizagn.com/license New BSD License
 * @copyright  Copyright (c) 2002-2010 Dizagn. (http://www.dizagn.com)
 * @link http://framework.dizagn.com
 * @author N.Namont Dizagn 2008
 * @version: $Id$
 *
 * @file
 * Fichier coeur du framework
 * Cette classe contient les principales methodes du framework et sert également
 * de façade pour certaines méthodes des plugins.
 * Ce framework repose sur une architecture UTM (User Tool Model) en reference
 * à l'inventeur du motif de conception MVC (Model View Controller).
 * Par defaut vous trouverez tous les éléments de configuration du framework
 * dans le fichier ../application/config/utm.ini.
 **/

/**
 * Inclusion des composants/librairies du framework grâce à l'autoload.
 * Les controlleurs et les vues ne sont pas gérés par cette methode
 **/
function __autoload($p_sClassName) {
    include_once $p_sClassName . '.php';
}

/**
 * Classe principale du framework.
 */
class core {

    const INI_PATH        = '../application/config/'; /*!< Chemin des fichiers de configuration de l'application */

    private static $m_oInstance; /*!< Contient l'instance unique du framework  */
    public static $config =array(); /*!< Contient la configuration du framework */
    private $m_oRequest; /*!< Objet requete */
    private $m_sViewContent ;/*!< Contenu de la vue avant affichage final */

    /**
     * Methode definie comme privée afin de forcer l'utilisation de la méthode
     * statique instance
     **/
    private function __construct(){}

    /**
     * Créé une instance unique du framework (Motif singleton)
     * @return object Instance unique du framework
     **/
    public static function instance()
    {
        // On retourne l'instance existante sinon on la créé
        if (TRUE == isset(self::$m_oInstance)){
            return self::$m_oInstance ;
        }

        // Chargement des fichiers de config
        self::loadIniFile();

        // Configuration des chemins d'acces aux fichiers pour l'autoload, les
        // plugins sont déclarés avant les lib pour éviter un conflit avec une
        // lib qui porterait le meme nom qu'un plugin.
        set_include_path(get_include_path().PATH_SEPARATOR.core::$config['path']['core']);

        $l_aPluginPath = explode(PATH_SEPARATOR, core::$config['path']['plugin']) ;
        $l_aLibPath = explode(PATH_SEPARATOR, core::$config['path']['lib']) ;
        // Ainsi on peut déclarer plusieurs chemins pour les libs et les plugins
        foreach(array_merge($l_aPluginPath, $l_aLibPath) AS $l_sPath){
            set_include_path(get_include_path().PATH_SEPARATOR.$l_sPath);
        }

        return self::$m_oInstance = new core() ;
    }

    /**
     * Assemble tous les fichiers .ini trouvés dans le dossier de config
     */
    protected static function loadIniFile()
    {
        // Récuperation des fichiers .ini a parser
        $l_aFile = glob(self::INI_PATH.'*.ini');
        if(count($l_aFile)==0){
            die('Aucun fichier de configuration valide dans le dossier : '.self::INI_PATH);
        }
        foreach($l_aFile AS $l_sFile){
            self::$config = array_merge(self::$config, parse_ini_file($l_sFile,TRUE)) ;
        }
    }

    /**
     * Lance l'execution du framework
     **/
    public function run()
    {
        // gestionnaire d'erreur du framework
        set_error_handler('coreError::error_handler') ;

        try{
            // On instancie l'objet request et on definit ses paramètres
            $this->m_oRequest = new coreRequest(core::$config['request']);

            // On recupere les methodes publiques de chaque plugin
            corePlugin::initPlugin() ;
            // Emission du premier evenement
            corePlugin::emit('onStart') ;

            // Initialisation de l'objet request
            $this->m_oRequest->setRequest();
            corePlugin::emit('onPostRequest') ;

            // On execute la requete
            $this->execute($this->m_oRequest);

            // On rend la vue dynamique ou statique
            $this->m_sViewContent = $this->render($this->m_oRequest);
            if($this->m_sViewContent != FALSE){
                corePlugin::emit('onEcho') ;
                echo $this->m_sViewContent ;
            }

            // Emission de l'evenement onFinish
            corePlugin::emit('onFinish');
            // Emission de l'evenement onPluginFinish
            corePlugin::emit('onUltimateFinish');
            
        }
        catch( Exception $e){
            trigger_error($e,E_USER_ERROR);
        }
    }

    /**
     * Execute le controleur demandé par l'objet request
     * @param object $p_oRequest Objet contenant les élements de la requete
     **/
    public function execute(coreRequest $p_oRequest)
    {
        corePlugin::emit('onExecute') ;

        // Recuperation du controller aupres du finder
        $l_aCtrl = $this->find($p_oRequest, self::$config['core']['controller_name']) ;

        // On vérifie si la methode existe dans la classe
        require_once($l_aCtrl['path']);
        if(TRUE == method_exists($l_aCtrl['class'], $l_aCtrl['method'])){
            // On instancie le controlleur
            $l_oInstance = new $l_aCtrl['class']() ;

            // On verifie qu'il s'agit bien d'une classe qui etend coreController
            if(TRUE == ($l_oInstance instanceof coreController)){
                $l_oInstance->$l_aCtrl['method']() ;
            }
            else{
                throw New Exception('La classe : '.$l_aCtrl['class'].' ('.$l_aCtrl['path'].') doit étendre la classe coreController') ;
            }
        }
        else{
            throw New Exception('La classe : '.$l_aCtrl['class'].' ('.$l_aCtrl['path'].') doit contenir une methode '.$l_aCtrl['method']) ;
        }
    }

    /**
     * Retourne le contenu de la vue
     * @param object $p_oRequest Objet contenant les élements de la requete
     * @return string/false Le contenu ou false si il n'y a pas de vue
     **/
    public function render(coreRequest $p_oRequest)
    {
        // Récupération de la vue aupres du finder
        $l_aView = $this->find($p_oRequest, self::$config['core']['view_name']) ;

        // Si la vue n'a pas été trouvée c'est qu'elle n'existe pas.
        if($l_aView === FALSE){
            return FALSE;
        }

        // Traitement d'une vue statique
        if(TRUE == isset($l_aView['static'])){
            return file_get_contents($l_aView['path']) ;
        }
        // Traitement d'une vue a instancier
        else{
            require_once($l_aView['path']) ;
            if(TRUE == class_exists($l_aView['class'])){
                $l_oView = new $l_aView['class']() ;
                if(TRUE == method_exists($l_aView['class'], $l_aView['method'])){
                    if(TRUE == ($l_oView instanceof coreView)){
                        return $l_oView->$l_aView['method']() ;
                    }
                    else{
                        throw New Exception('La vue : '.$l_aView['class'].' ('.$l_aView['path'].') doit étendre la classe coreView') ;
                    }
                }else{
                    throw New Exception('La vue '.$l_aView['class'].' doit contenir la methode '.$l_aView['method']);
                }
            }else{
                throw New Exception('La classe : '.$l_aView['class'].' n\'est pas définie dans le fichier '.$l_aView['path']);
            }
        }
    }

    /**
     * Détermine la classe à instancier et le chemin pour y acceder
     * @param object $p_oRequest Objet contenant les élements de la requete
     * @param $p_sType string Type d'objet a rechercher Controller, View     *
     * @return array Tableau contenant le fichier à inclure, le nom de la
     * classe à instancier, la methode à appeler, ou le type static
     **/
    public function find(coreRequest $p_oRequest, $p_sType = NULL)
    {
        // Initialisation et raccourcis
        $l_aReturn     = FALSE;
        $l_sController = $p_oRequest->getController() ; // controller
        $l_sAction     = $p_oRequest->getAction(); // action
        $l_sModule     = $p_oRequest->getModule(); // module
        $l_sBase       = $l_sController.'/'.$l_sAction ; // controller/action
        $cn = self::$config['core']['controller_name'];
        $cm = self::$config['core']['controller_method'];
        $vn = self::$config['core']['view_name'];
        $vm = self::$config['core']['view_method'];
        $ms = self::$config['core']['module_separator'];
        
        // On traite les controlleurs
        if($p_sType == $cn || $p_sType === NULL){
            $l_aReturn['method']= $cm;
            // Gestion sans module
            if($l_sModule == NULL){
                // Cas 1 : ./components/controller/action[Controller.php]
                if(TRUE == file_exists(self::$config['path']['controller'].$l_sBase.$cn.'.php')){
                    $l_aReturn['path']  = self::$config['path']['controller'].$l_sBase.$cn.'.php';
                    $l_aReturn['class'] = $l_sController.'_'.$l_sAction.$cn;
                }
                // Cas 2 SuperController : ./components/controller[Controller.php]
                else if(TRUE == file_exists(self::$config['path']['controller'].$l_sController.$cn.'.php')){
                    $l_aReturn['path']  = self::$config['path']['controller'].$l_sController.$cn.'.php' ;
                    $l_aReturn['class'] = $l_sController.$cn ;
                    $l_aReturn['method']= $l_sAction;
                }
                else{
                    throw new Exception("Le controlleur demandé n'existe pas, format accepté :
".self::$config['path']['controller'].$l_sBase.$cn.".php
".self::$config['path']['controller'].$l_sController.$cn.'.php');
                }
            }
            // Gestion du module si il existe
            else{
                // cas 3 : ./components/module_controller/action[Controller.php]
                if(TRUE == file_exists(self::$config['path']['controller'].$l_sModule.$ms.$l_sBase.$cn.'.php')){
                    $l_aReturn['path']  = self::$config['path']['controller'].$l_sModule.$ms.$l_sBase.$cn.'.php';
                    $l_aReturn['class'] = $l_sModule.'_'.$l_sController.'_'.$l_sAction.$cn;
                }
                // cas 4 SuperController : ./components/module_controller[Controller.php]
                else if(TRUE == file_exists(self::$config['path']['controller'].$l_sModule.$ms.$l_sController.$cn.'.php')){
                    $l_aReturn['path']  = self::$config['path']['controller'].$l_sModule.$ms.$l_sController.$cn.'.php';
                    $l_aReturn['class'] = $l_sModule.'_'.$l_sController.$cn ;
                    $l_aReturn['method']= $l_sAction;
                }
                else{
                    throw new Exception("Le controlleur demandé n'existe pas, format accepté avec 'module' :
".self::$config['path']['controller'].$l_sModule.$ms.$l_sBase.$cn.".php
".self::$config['path']['controller'].$l_sModule.$ms.$l_sController.$cn.'.php');
                }
            }
        }
        // On traite la vue si elle existe
        else if($p_sType == $vn){
            if($l_sModule == NULL){
                // cas 1 : ./components/controller/action[View.php]
                if(TRUE == file_exists(self::$config['path']['view'].$l_sBase.$vn.'.php')){
                    $l_aReturn['path']  = self::$config['path']['view'].$l_sBase.$vn.'.php';
                    $l_aReturn['class'] = $l_sController.'_'.$l_sAction.$vn;
                    $l_aReturn['method']= $vm;
                }
                // cas 2 page statique : ./components/controller/action.html
                else if(TRUE == file_exists(self::$config['path']['view'].$l_sBase.'.html')){
                    $l_aReturn['path']  = self::$config['path']['view'].$l_sBase.'.html';
                    $l_aReturn['static']= TRUE;
                }
            }
            // Gestion du module si il existe
            else{
                // cas 3 : ./components/module[/_]controller/action[View].php
                if(TRUE == file_exists(self::$config['path']['view'].$l_sModule.$ms.$l_sBase.$vn.'.php')){
                    $l_aReturn['path']  = self::$config['path']['view'].$l_sModule.$ms.$l_sBase.$vn.'.php';
                    $l_aReturn['class'] = $l_sModule.'_'.$l_sController.'_'.$l_sAction.$vn;
                    $l_aReturn['method']= $vm;
                }
                // cas 4 page statique : ./components/module[/_]controller/action.html
                else if(TRUE == file_exists(self::$config['path']['view'].$l_sModule.$ms.$l_sBase.'.html')){
                    $l_aReturn['path']  = self::$config['path']['view'].$l_sModule.$ms.$l_sBase.'.html';
                    $l_aReturn['static'] = TRUE;
                }
            }
            return $l_aReturn ;
        }
        else{
            throw new Exception('Le "type" (2ème parametre) demandé dans la methode find n\'existe pas.
Les types possibles sont : "'.$cn.'" et "'.$vn.'"') ;
        }
        // Tableau du resultat de la recherche
        return $l_aReturn ;
    }

    /**
     * Renvoi l'objet request
     * @return <object> Objet request
     */
    public function getRequest(){
        return TRUE == isset($this->m_oRequest) ? $this->m_oRequest : FALSE ;
    }

    /**
     * Permet de modifier à la volée les données de l'objet request.
     * @param string $p_sCtrl
     * @param string $p_sAction
     * @param string $p_sModule
     * @param string $p_aGet
     * @param string $p_aPost
     * @param string $p_aCli
     *
     */
    public function resetRequest($p_sCtrl, $p_sAction, $p_sModule=null, $p_aGet = null, $p_aPost = null, $p_aCli = null){
        $this->m_oRequest->setFakeRequest($p_sCtrl, $p_sAction, $p_sModule, $p_aGet, $p_aPost, $p_aCli);
    }

    /**
     * Renvoi le contenu de la vue
     * @return <string> >Vue
     */
    public function getViewContent(){
        return TRUE == isset($this->m_sViewContent) ? $this->m_sViewContent : FALSE ;
    }

    /**
     * On modifie le contenu finale de la vue
     * @param string $p_sViewContent Nouveau contenu de la vue
     */
    public function resetViewContent($p_sViewContent){
        $this->m_sViewContent = (string)$p_sViewContent;
    }

    /**
     * Methode façade:enregistre les plugins.
     * @param $p_sPlugin string Variable contenant le nom du plugin a utilisé
     * @return void
     **/
    public function registerPlugin(){
        // on place le plugin dans le tableau des plugins
        corePlugin::register(func_get_args());
    }
}
