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
 * @version: $Id: shell.php 56946 2017-08-23 08:55:05Z n.namont@uniteam.fr $
 *
 * @file
 * Shell en version beta, permettant de simplifier certaines taches telles que :
 * - créer des composants, installer des plugins, etc.
 **/

/**
 * Inclusion des composants/librairies du framework grâce à l'autoload.
 * Les controlleurs et les vues ne sont pas gérés par cette methode
 **/
// pour empecher l'appel en HTTP
if(PHP_SAPI!='cli'){
    die();
};

class utmShell {

    const UTM_VERSION = '1.1' ;
    const CONFIG_FILE = '../application/config/utm.ini' ;

    protected $choice = null ;

    /**
     *
     **/
    public function __construct(){
        $this->config = parse_ini_file(self::CONFIG_FILE, true) ;
    }

    /**
    * Récupération des entrées au clavier
    * @return string Text saisi
    */
    protected function getInputCLI()
    {
        $fp = fopen('php://stdin', 'r');
        $input = fgets($fp, 255);
        fclose($fp);
        $input = trim($input);
        return $input;
    }

    /**
     *
     **/
    public function run(){

        // Message d'accueil
        if ($this->choice == NULL){
            $this->getMessage(0) ;
        }

        $this->choice = strtolower($this->getInputCli()) ;
        // On traite le choix de l'utilisateur
        switch ($this->choice){
            default: // erreur de saisie
                $this->getMessage(2) ;
            break;
            case 1 :
                $this->makeComponent();
                $this->choice = NULL ;
                $this->run();
            break;
            case 2 :

            break;
            case 'r' : // retour
                $this->choice = NULL ;
                $this->run();
                break;
            case 'x' : // exit
                $this->getMessage(1) ;
                exit;
            break;

        }


        if($this->choice != 'x'){
            $this->run();
        }
    }

    /**
     *
     **/
    protected function makeComponent(){
        $this->getMessage(3); //
        $l_sController = $this->getInputCli() ;
        $l_sController = ($l_sController != '') ? $l_sController : $this->config['core']['default'];

        $this->getMessage(4); //
        $l_sAction = $this->getInputCli() ;
        $l_sAction = ($l_sAction != '') ? $l_sAction : $this->config['core']['default'];

        $this->getMessage(5); //
        $l_sModule = $this->getInputCli() ;

        $l_sCtrlName = $l_sController."_".$l_sAction.$this->config['core']['controller_name'] ;
        $l_sViewName = $l_sController."_".$l_sAction.$this->config['core']['view_name'] ;
        $l_sCtrlPath = realpath($this->config['path']['controller']).DIRECTORY_SEPARATOR.$l_sController;
        $l_sViewPath = realpath($this->config['path']['view']).DIRECTORY_SEPARATOR.$l_sController;

        // Ajout du module
        if($l_sModule != ''){
            $l_sCtrlName = $l_sModule.'_'.$l_sCtrlName ;
            $l_sViewName = $l_sModule.'_'.$l_sViewName ;
        }

        // verification d'existence
        if(true == file_exists($l_sViewPath.DIRECTORY_SEPARATOR.$l_sAction.'View.php') ||
           true == file_exists($l_sCtrlPath.DIRECTORY_SEPARATOR.$l_sAction.'Controller.php')){
            $this->getMessage(8) ;
            return FALSE ;
        }

        // creation du fichier controller ( controller_actionController extends core)
        $l_sCtrl = "<?php
/**
 * Classe autogénéré par le shell utmShell version ".self::UTM_VERSION."
 * @date ".date('Y-m-d H:i:s')."
 * @author
 */

class ".$l_sCtrlName." extends coreController {

    public function ".$this->config['core']['controller_method']."(){

    }
}";

        // creation du fichier controller ( controller_actionController extends core)
        $l_sView = "<?php
/**
 * Classe autogénéré par le shell utmShell version ".self::UTM_VERSION."
 * @date ".date('Y-m-d H:i:s')."
 * @author
 */

class ".$l_sViewName." extends coreView {

    public function ".$this->config['core']['view_method']."(){

    }
}";

        // création des répertoires si ils n'existent pas
        $this->makeDir($l_sCtrlPath); // controlleur
        $this->makeDir($l_sViewPath); // vue

        if(file_put_contents($l_sCtrlPath.DIRECTORY_SEPARATOR.$l_sAction.'Controller.php', $l_sCtrl) &&
           file_put_contents($l_sViewPath.DIRECTORY_SEPARATOR.$l_sAction.'View.php', $l_sView)){
            $this->getMessage(6);
        }

    }

    /**
     * Verifie si un dossier existe sinon il le crée
     **/
    protected function makeDir($p_sPath){
        if(FALSE == is_dir($p_sPath)){
            if(!mkdir($p_sPath)){
                $this->getMessage(7); // erreur
            }
        }
    }



    /**************************************
     * Message
     **/
    protected function getMessage($p_sMessage){

        switch ($p_sMessage){
            case 0 : // Message d'accueil
                $l_sString = "
UTM SHell Version ".self::UTM_VERSION."

  [1]     Création d'un composant (composant : controller et vue)
  [2]     (Beta)Création d'un model
  [3]     (Beta)installation d'un plugin

  [x]     Quitter l'application

Votre choix : " ;
            break;
            case 1 : // exit
                $l_sString = "\nAu revoir !\n\n";
            break;
            case 2 : // erreur de saisie
                $l_sString = "! Choix impossible !
Nouveau choix : " ;
            break;
            case 3 : // makeComponent : controlleur
                $l_sString = "=> Création d'un composant
Nom du controller [".$this->config['core']['default']."]: ";
            break;
            case 4 : // makeComponent : action
                $l_sString = "Nom du l'action [".$this->config['core']['default']."]: ";
            break;
            case 5 : // makeComponent : module
                $l_sString = "Nom du module (Laisser vide si aucun) : ";
            break;
            case 6 : // makeComponent
                $l_sString = "\n=> Composant créé avec succès\n" ;
            break;
            case 7 : // makeComponent: Erreur
                $l_sString = "Impossible de créer le répertoire du composant, veuillez vérifier que vous avez les droits suffisants\n" ;
            break;
            case 8: // makeComponent:
                $l_sString = "\n=> Ce composant existe deja\n";
            break;
        }
        echo $l_sString ;
    }

}

$l_oShell = new utmShell ;
$l_oShell->run();
