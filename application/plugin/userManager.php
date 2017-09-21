<?php
/**
 * UTM Framework / plugin de gestion des droits et roles des utilisateurs de 
 * l'application
 *
 * @name userManager
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
 * @copyright  Copyright (c) 2016 Dizagn. (http://www.dizagn.com)
 * @link http://framework.dizagn.com
 * @author N.Namont Dizagn 2016
 * @version : $Id: userManager.php 56946 2017-08-23 08:55:05Z n.namont@uniteam.fr $
 *
 * @file
 * 
 * ------
 *
 **/
class userManager extends corePlugin
{
    CONST DB_PREFIX = 'cb_user' ;
    protected $m_iUserId = 0;
    protected $m_sUserLogin = '';
    protected $m_aField = array();
    protected $m_aPermission = array();
    
    public $m_bUpdate = 0;
    public $m_bUpdatePasswd = 0;


    public function __construct(){
        if(FALSE == $this->isLoaded('db') || FALSE == $this->isLoaded('session')){
            throw new Exception('PLUGIN USER : Les dépendances envers les plugins "DB" et "SESSION"'
                    . ' ne sont pas satisfaites.');
        }
        
        $this->restoreSession();
    }
    
    public function onFinish(){
        if(TRUE == $this->isLoaded('debug') && TRUE == isset(core::$config['userManager']['dumpToDebug']) && TRUE == core::$config['userManager']['dumpToDebug']){
            $this->addToDebug('Infos Utilisateur', '<b>Login ('.$this->getUid().')</b> : '.$this->getLogin(), 'user');
            $this->addToDebug('Champs Utilisateur',$this->dumpFieldIntoString() , 'user');
        }
    }
    
    protected function dumpFieldIntoString(){
        $l_sField = '';
        $l_aField = $this->getField($this->getUId()) ;
        if($l_aField != NULL){
            foreach ($l_aField AS $name => $value){
                $l_sField .= '<b>'.$name .'</b> : '.$value.'<br/>' ;
            }
        }
        return $l_sField;
    }
    
    public function getUId() {
        if(TRUE == isset($this->m_iUserId)){
            return $this->m_iUserId;
        }
    }    
    public function getLogin() {
        if(TRUE == isset($this->m_sUserLogin)){
            return $this->m_sUserLogin;
        }
    }
    public function field($p_sElement) {       
        if(TRUE == isset($this->m_aField[$p_sElement])){
            return $this->m_aField[$p_sElement];
        }
    }
    
    /**
     * Ajoute un user 
     * 
     */
    public function createUser($p_sLogin, $p_sPasswd, $p_sRoleId=2){
        $l_qSql = "INSERT INTO `".self::DB_PREFIX."` (`login`, `passwd`, `roleId`, `createDate`, `status`) "
                . "VALUES ('".$this->db->escape($p_sLogin)."'," 
                . "'".$this->makePwd($this->db->escape($p_sPasswd))."',"
                . "'".$p_sRoleId."',"
                . "NOW(),"
                . "'1')" ;
        return $this->exec($l_qSql);
    }

    /**
     * Ajoute un champs user 
     * 
     */
    public function addField($p_iId, $p_sLabel, $p_sValue){
        // On l'ajoute dans le tableau courant
        $this->m_aField[$p_sLabel] = $p_sValue;
        
        // Puis on le persiste en base
        $l_qSql = "INSERT INTO `".self::DB_PREFIX."_field` ( `userId`, `label`, `value`) "
                . "VALUES ('".$this->escape($p_iId)."',"
                . "'".$this->escape($p_sLabel)."',"
                . "'".$this->escape($p_sValue)."')" ;
        return $this->exec($l_qSql);
    }

    
    /**
     * Authentifie un user
     */
    public function authenticate($p_sLogin, $p_sPasswd){
        
        $_SESSION['user']['id'] = $this->m_iUserId = $this->checkCredential($p_sLogin, $p_sPasswd);        
        
        if(FALSE == $this->m_iUserId){
            return FALSE ;
        }
        $_SESSION['user']['login'] = $this->m_sUserLogin = $p_sLogin;
        // regenere l'id de session
        $this->session->regenerate();
        // stocke toutes les permissions et les fields extras en session
        $this->storeSession();
        
        return TRUE ;
    }
    
    /*
     * Recupere certains membres de l'objet et les persiste en session
     */
    protected function storeSession($p_sType = 'all'){
        if($p_sType == 'all' || $p_sType == 'permission')
            $_SESSION['user']['permission'] = $this->getPermission($this->m_iUserId);
        
        if($p_sType == 'all' || $p_sType == 'field'){
            $_SESSION['user']['field']      = $this->getField($this->m_iUserId);
        }
    }
    
    /*
     * Recupere certaines valeur en session et les restaure dans l'objet
     */
    protected function restoreSession(){
        // on recupere les valeurs en session et on les replace dans l'objet
        if('' != session_id() && TRUE == isset($_SESSION['user']['id'])){
            $this->m_aPermission    = $_SESSION['user']['permission'] ;
            $this->m_aField         = $_SESSION['user']['field'] ;
            $this->m_iUserId        = $_SESSION['user']['id'] ;
            $this->m_sUserLogin     = $_SESSION['user']['login'] ;
        }
    }
    
    /**
     * Verifie si l'utilisateur possède ce droit dans son profil.
     */
    public function checkPermission($p_sPermission){
        return TRUE == in_array($p_sPermission, $this->m_aPermission);
    } 
    
    /**
     * Ajoute une permission temporaire qui ne sera pas stockée en session
     * Ex : pour un utilisateur qui veut modifer son propre enregistrement
     */
    public function addPermission($p_sPermission){
        $this->m_aPermission[] = $p_sPermission ;
    }
    
    /**
     * Verifie le couple user et mot de passe
     * @param 
     * @param
     * @return FALSE ou le userId en cas de succes
     */
    protected function checkCredential($p_sLogin, $p_sPasswd){
        
        $l_qSql = "SELECT U.`id` "
                . "FROM ".self::DB_PREFIX." AS U "
                . "WHERE login='".$this->escape($p_sLogin)."' "
                . "AND passwd='".$this->makePwd($p_sPasswd)."' "
                . "AND status=1";
        return $this->queryOne($l_qSql);
    }
    
    /**
     * 
     */
    protected function makePwd($p_sPasswd){
        return sha1(core::$config['userManager']['salt'].md5($this->escape(trim($p_sPasswd))));
    }


    /**
     * 
     */
    public function getPermission($p_iUserId){
        $l_qSql = "SELECT P.id, P.label "
                . "FROM `".self::DB_PREFIX."` AS U "
                . "INNER JOIN `".self::DB_PREFIX."_role` AS R ON R.id = U.roleId "
                . "INNER JOIN `".self::DB_PREFIX."_permission_role` AS PR ON PR.roleId = R.id "
                . "INNER JOIN `".self::DB_PREFIX."_permission` AS P ON P.id = PR.permissionId   "
                . "WHERE U.id =".intval($p_iUserId) ;

        $l_oResult = $this->db->query($l_qSql);
        if($l_oResult != FALSE){
            while($row = $l_oResult->fetch_array()){
                $this->m_aPermission[$row['id']] = $row['label'];
            }
            return $this->m_aPermission;
        }
    }
    
    /**
     * 
     */
    public function getField($p_iUserId){
        if(TRUE == isset($p_iUserId) && $p_iUserId != 0){
            $l_qSql = "SELECT label, value FROM ".self::DB_PREFIX."_field WHERE userId = ".intval($p_iUserId);
            $l_oResult = $this->query($l_qSql);
            if($l_oResult != FALSE){
                while($row = $l_oResult->fetch_array()){
                    $this->m_aField[$row['label']] = $row['value'];
                }
                return $this->m_aField;
            }
        }
    }
    
    public function updateField($p_iId, $p_aField){
        
        $l_qSql = "INSERT INTO `".self::DB_PREFIX."_field` (`userId`, `label`,`value`) VALUES ";
        foreach ($p_aField as $key => $value) {
            $l_qSql .= "('".intval($p_iId)."', '".$this->escape($key)."', '".$this->escape($value)."'),";
            $this->deleteField($p_iId, $key);
        }
        $this->exec(rtrim($l_qSql, ','));
        $this->getField($p_iId);
        $this->storeSession('field');
        $this->m_bUpdate = 1;
        
    }
    
    protected function deleteField($p_iId, $p_sLabel){
        $l_qSql = "DELETE FROM `".self::DB_PREFIX."_field` "
                . "WHERE `userId` = ".intval($p_iId)." "
                . "AND `label`='".$this->escape($p_sLabel)."'";
        return $this->exec($l_qSql);
    }
    
    public function updatePasswd($p_sPasswd){
        $l_qSql = "UPDATE `".self::DB_PREFIX."` SET `passwd` = '".$this->makePwd($p_sPasswd)."' WHERE `id` = ".$this->m_iUserId ;
        $this->exec($l_qSql);
        $this->m_bUpdatePasswd = 1;
    }
}

