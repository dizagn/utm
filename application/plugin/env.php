<?php
/**
 * UTM Framework / plugin de gestion d'environnement
 * @name env
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
 * @version : $Id: env.php 56946 2017-08-23 08:55:05Z n.namont@uniteam.fr $
 * 
 * @file
 * Permet de surcharger facilement une config en fonction des environements de 
 * travail (local, dev, recette, preprod, prod, etc.) sans avoir à modifier le 
 * fichier de config. 
 * Ceci peut etre fait soit par une variable serveur soit dans un fichier
 * de config au format ".ini"
 * 
 * -> Exemple de variable serveur avec un fichier .htaccess :
 *     SetEnv env preprod
 * 
 * -> Exemple dand un fichier .ini
 *     env = preprod
 *     
 * -> Exemple d'application dans un fichier config.ini 
 * [db]
 *  host = mysql.dizagn.com
 *  login = web_user
 *  passwd = VerySecretPassword
 *  port = 3306
 * 
 * [db.preprod]
 *  login = web_user_pp
 *  passwd = AnotherSecretPassword
 **/
class env extends corePlugin
{
    protected $config = array(); // On récupere le tableau de config
    protected $env ; // Contient la variable de l'environnement
    
    const env = 'env' ; // Clé utilisée dans les fichiers de configuration

    public function __construct(){
        $this->config = core::$config;
        // On cherche la valeur de env dans une variable serveur
        if(TRUE == isset($_SERVER[self::env])){
            $this->setEnv($_SERVER[self::env]);
        }
        else if(PHP_SAPI=='cli' && FALSE != $this->findEnvInCLi()){
            $this->setEnv($this->findEnvInCLi());
        }
        // Sinon on regarde dans les fichiers .ini de config
        else if(TRUE == isset(core::$config[self::env])){
            $this->setEnv(core::$config[self::env]);
        }
        else{
            $this->setEnv(FALSE);
        }
    }

    /**
     * Cherche dans le CLI si la variable env est passée pour l'enregistrer 
     */
    protected function findEnvInCLi(){
        parse_str($_SERVER['argv'][1], $l_aTemp) ;
        if(TRUE == isset($l_aTemp['env'])){
            return $l_aTemp['env'] ;
        }
        return FALSE;
    }
    
    /**
     * Accesseur de type set
     * @param type $p_sEnv 
     */
    protected function setEnv($p_sEnv){
        // On set dans l'objet
        $this->env = (string)$p_sEnv;
        // Puis on ecrase la valeur dans la config pour l'instance 
        core::$config['env'] = $p_sEnv;
    }
    
    /**
     * Au lancement du plugin parcours chaque valeur et surcharge la config
     * dont l'entrée est présente sous la forme [clé.env]
     */
    public function onStart(){
        if(FALSE != $this->env){
            foreach ($this->config AS $key => $value){
                $this->overloadEnvConfig($key);
            }
        }
        else{
            throw new Exception('La variable "'.self::env.'" n\'est pas définie : soit dans une variable serveur (vhost,.htaccess,etc) soit dans la configuration (config.ini, env.ini, etc.)');
        }
    }
    
    /**
     * Permet de surcharger les valeurs de la config par defaut d'une entrée 
     * deja présente dans le tableau de config.
     */
    protected function overloadEnvConfig($p_sConfigKey){
        if(TRUE == isset($this->config[$p_sConfigKey.'.'.$this->env])){
            foreach($this->config[$p_sConfigKey.'.'.$this->env] AS $subkey => $value){
                core::$config[$p_sConfigKey][$subkey] = core::$config[$p_sConfigKey.'.'.$this->env][$subkey];
            }
        }
    }
}