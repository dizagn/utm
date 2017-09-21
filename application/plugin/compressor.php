<?php
/**
 * UTM Framework :: Plugin compressor
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
 * @author K.Queret, N.Namont
 * @version: $Id: compressor.php 56946 2017-08-23 08:55:05Z n.namont@uniteam.fr $
 *
 * @file
 * Compresseur de fichiers CSS & JS
 */
class compressor extends corePlugin
{
    protected $m_aCache = array();
    protected $m_bConfig = FALSE ;
    protected $m_bUpdateCache = false;

    public function onStart(){

        $this->m_bConfig = core::$config['compressor'];

        if(TRUE != $this->m_bConfig['compress']){
            $this->debug->addToDebug('Compressor', 'Chargé mais inactif', 'compressor');
        }

        if(TRUE == file_exists(core::$config['path']['cache'] . 'compressor.json') && TRUE == $this->m_bConfig['compress']){
            $this->m_aCache = json_decode(file_get_contents(core::$config['path']['cache'] . 'compressor.json'), true);
            if(TRUE == $this->isLoaded('debug')){
                $this->debug->addToDebug('Compressor', 'Chargement du cache', 'compressor');
            }
        }
    }

    public function onFinish(){
        if(TRUE == $this->m_bUpdateCache){
            if(FALSE == file_put_contents(core::$config['path']['cache'] . 'compressor.json', json_encode($this->m_aCache))){
                if(TRUE == $this->isLoaded('debug')){
                    $this->debug->addToDebug('Compressor', 'Impossible d\'ecrire le fichier de cache JSON');
                }
            }
        }
    }

    public function onEcho(){

        if(TRUE != $this->m_bConfig['compress'] || TRUE != $this->m_bConfig['html']){
            return;
        }

        $l_oCore = core::instance();
        $l_sViewContent = (string)$l_oCore->getViewContent();
        $l_sViewContent = $this->minifyHtml($l_sViewContent);
        $l_oCore->resetViewContent($l_sViewContent);
    }
    
    
    /**
     * Compression d'un fichier JavaScript
     */
    public function loadJs( $p_sJSFileName, $p_sSuffix = '.min' ){

        if(TRUE != $this->m_bConfig['compress'] || TRUE != $this->m_bConfig['js']){
            return $p_sJSFileName;
        }

        // Si le compressor est désactivé ou Si le fichier n'existe pas on log
        if( !is_file($p_sJSFileName)){
            if(TRUE == $this->isLoaded('debug')){
                $this->debug->addToDebug('Compressor JS', 'Le fichier '.$p_sJSFileName.' n\'a pu être chargé', 'compressor');
            }
            return $p_sJSFileName;
        }

        // Sinon on prépare le nom du fichier minifié
        $l_sMinFileName = str_replace('.js', $p_sSuffix.'.js', $p_sJSFileName);

        // On crée le fichier minifié
        if(!is_file($l_sMinFileName) ||
            !isset($this->m_aCache[$p_sJSFileName]) ||
            filemtime($p_sJSFileName) > $this->m_aCache[$p_sJSFileName]){

            include 'JSMinPlus.php';
            if(FALSE  != file_put_contents($l_sMinFileName, JSMinPlus::minify(file_get_contents($p_sJSFileName)))){
                $this->m_aCache[$p_sJSFileName] = filemtime($p_sJSFileName);
                $this->m_bUpdateCache = true;
                $l_sMessage = 'Ecriture du fichier ' . $l_sMinFileName ;
            }
            else{
                $l_sMessage = 'Impossible de generer le fichier (probleme d\'écriture?) : ' . $l_sMinFileName ;
            }
        }

        /* Utilisation du plugin debug pour logguer*/
        if(TRUE == $this->isLoaded('debug') && TRUE == isset($l_sMessage)){
            $this->debug->addToDebug('Compressor JS', $l_sMessage, 'compressor');
        }
        return $l_sMinFileName;
    }

    /**
     * Compression d'un fichier CSS
     **/
    public function loadCss( $p_sCSSFileName, $p_sSuffix = '.min' ){

        if(TRUE != $this->m_bConfig['compress'] || TRUE != $this->m_bConfig['css']){
            return $p_sCSSFileName;
        }

        // Si le compressor est désactivé ou Si le fichier n'existe pas on log
        if(!is_file($p_sCSSFileName)){
            if(TRUE == $this->isLoaded('debug')){
                $this->debug->addToDebug('Compressor CSS', 'Le fichier '.$p_sCSSFileName.' n\'a pu être chargé', 'compressor');
            }
            return $p_sCSSFileName;
        }

        // On prépare le nom du fichier minifié
        $l_sMinFileName = str_replace('.css', $p_sSuffix . '.css', $p_sCSSFileName);

        // On créé le fichier minifié
        if(!is_file($l_sMinFileName) ||
            !isset($this->m_aCache[$p_sCSSFileName]) ||
            filemtime($p_sCSSFileName) > $this->m_aCache[$p_sCSSFileName]){

            include_once 'minifycss.php';
            if(FALSE != file_put_contents($l_sMinFileName, Minify_CSS_Compressor::process(file_get_contents($p_sCSSFileName)))){
                $this->m_aCache[$p_sCSSFileName] = filemtime($p_sCSSFileName);
                $this->m_bUpdateCache = true;
                $l_sMessage = 'Ecriture du fichier ' . $l_sMinFileName ;
            }
            else{
                $l_sMessage = 'Impossible de generer le fichier (probleme d\'écriture?) : ' . $l_sMinFileName ;
            }

            if(TRUE == $this->isLoaded('debug')){
                $this->debug->addToDebug('Compressor CSS', $l_sMessage, 'compressor');
            }
        }
        return $l_sMinFileName;
    }

    // HTML Minifier
    private function minifyHtml($input) {
        if(trim($input) === "") return $input;
        // Remove extra white-space(s) between HTML attribute(s)
        $input = preg_replace_callback('#<([^\/\s<>!]+)(?:\s+([^<>]*?)\s*|\s*)(\/?)>#s', function($matches) {
            return '<' . $matches[1] . preg_replace('#([^\s=]+)(\=([\'"]?)(.*?)\3)?(\s+|$)#s', ' $1$2', $matches[2]) . $matches[3] . '>';
        }, str_replace("\r", "", $input));
        return preg_replace(
            array(
                // t = text
                // o = tag open
                // c = tag close
                // Keep important white-space(s) after self-closing HTML tag(s)
                '#<(img|input)(>| .*?>)#s',
                // Remove a line break and two or more white-space(s) between tag(s)
                '#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|\s*$#s',
                '#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)#s', // t+c || o+t
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s', // o+o || c+c
                '#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?<!\>)\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s', // c+t || t+o || o+t -- separated by long white-space(s)
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<\/.*?>)#s', // empty tag
                '#<(img|input)(>| .*?>)<\/\1>#s', // reset previous fix
                '#(&nbsp;)&nbsp;(?![<\s])#', // clean up ...
                '#(?<=\>)(&nbsp;)(?=\<)#', // --ibid
                // Remove HTML comment(s) except IE comment(s)
                '#\s*<!--(?!\[if\s).*?-->\s*|(?<!\>)\n+(?=\<[^!])#s'
            ),
            array(
                '<$1$2</$1>',
                '$1$2$3',
                '$1$2$3',
                '$1$2$3$4$5',
                '$1$2$3$4$5$6$7',
                '$1$2$3',
                '<$1$2',
                '$1 ',
                '$1',
                ""
            ),
        $input);
    }
}