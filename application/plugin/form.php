<?php
/**
 * UTM Framework / plugin phpTemplate
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
 * @copyright  Copyright (c) 2002-2021 Dizagn. (http://www.dizagn.com)
 * @link http://framework.dizagn.com
 * @author N.Namont Dizagn 2012
 * @author K.Queret 2016
 * @version : $Id: form.php 50058 2016-09-26 09:32:07Z n.namont@uniteam.fr $
 *
 * @file
 * Gestionnaire de formulaire
 */
class form extends corePlugin {

    protected $m_aError = NULL ;
    protected $m_aData = array() ;

    /**
     * Initialisation à partir du tableau passé en param
     * @param array Tableau des données a analyser
     */
    public function init(array $p_aData){
        $this->m_aData = $p_aData;
        $this->m_aError = NULL;
    }

    /**
     * Initialisation de la valeur d'un champs si celui ci n'est pas encore définit
     * @param string $p_sName Nom
     * @param string $p_sValue Valeur
     */
    public function setDefault($p_sName, $p_sValue){
        if (empty($this->m_aData) || !isset($this->m_aData[$p_sName])) {
            $this->m_aData[$p_sName] = $p_sValue;
        }
    }

    /**
     * Ajoute arbitrairement un message.
     * @param type $p_sVar
     * @param type $p_sMessage
     */
    public function addMessage($p_sVar, $p_sMessage){
        $this->m_aError[$p_sVar][] = $p_sMessage ;
    }

    /**
     * Renvoi false ou le tableau d'erreur
     * @param type $p_sField
     * @return mixed FALSE / Array of error
     */
    public function getError($p_sField = NULL,$p_bList=1){
        if(TRUE == is_null($p_sField)){
            return ($this->m_aError == NULL) ? FALSE : $this->m_aError ;
        }
        else{
            if(FALSE == isset($this->m_aError[$p_sField])){
                return FALSE ;
            }
            return (FALSE == $p_bList) ? $this->m_aError[$p_sField] : $this->m_aError[$p_sField][0] ; 
        }
    }

    /**
     * Renvoi NULL ou la valeur du champ
     * @param type $p_sField
     * @return string or NULL
     */
    public function __get($p_sField){
        return $this->getValue($p_sField);
    }

    /**
     * Renvoi NULL ou la valeur du champ
     * @param type $p_sField
     * @return string or NULL
     */
    public function getValue($p_sField){
        if(FALSE == isset($this->m_aData[$p_sField])){
            return NULL;
        }
        return TRUE == is_string($this->m_aData[$p_sField]) ? $this->antiXss($this->m_aData[$p_sField]) : $this->m_aData[$p_sField] ;
    }

    /**
     * Renvoi une chaine avec les entités HTML encodées
     * @param type $p_sString
     * @return string
     */
    public function antiXss($p_sString){
        return htmlentities($p_sString, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Renvoi le HTML nécéssaire si l'élément est coché
     * @param type $p_sField
     * @param type $p_sValue
     * @return string or NULL
     */
    public function htmlChecked($p_sField, $p_sValue){
        return $this->getValue($p_sField) == $this->antiXss($p_sValue) ? ' checked="checked"' : NULL;
    }

    /**
     * Renvoi le HTML nécéssaire si l'élément est sélectionné
     * @param type $p_sField
     * @param type $p_sValue
     * @return string or NULL
     */
    public function htmlSelected($p_sField, $p_sValue){
        return $this->getValue($p_sField) == $this->antiXss($p_sValue) ? ' selected="selected"' : NULL;
    }

    /**
     * RULES
     */

    /**
     * Verifie si une variable est vide ou pas
     * @param type $p_sVar
     * @return type
     */
    public function notEmpty($p_sVar, $p_sMessage=NULL, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(FALSE == isset($this->m_aData[$p_sVar]) || (TRUE == is_string($this->m_aData[$p_sVar]) && $this->m_aData[$p_sVar] == '') || (TRUE == is_array($this->m_aData[$p_sVar]) && empty($this->m_aData[$p_sVar])) ){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this ;
    }

    /**
     *
     */
    public function isEmail($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        $l_sPattern = '&^[a-zA-Z0-9]+([a-zA-Z0-9\-\._]+)*@([a-zA-Z0-9\-_]+)+(\.[a-zA-Z]{2,})*$&';
        $l_bReturn = preg_match($l_sPattern, $this->m_aData[$p_sVar]);
        
        if(FALSE == $l_bReturn){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }

    public function isSBEmail($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        $l_sPattern = '&^[a-z0-9]+([a-z0-9\-\._]+)*@([a-z0-9\-_]+)+(\.[a-z]{2,})*$&';
        $l_bReturn = preg_match($l_sPattern, $this->m_aData[$p_sVar]);
        
        if(FALSE == $l_bReturn){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }

    /**
     * verifie un code postal
     * @param type $p_sVar
     * @param type $p_sMessage
     * @return object $this
     */
    public function isZipCode($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        $l_sPattern = '&^[0-9]{5}$&' ;
        if(FALSE == preg_match($l_sPattern, $this->m_aData[$p_sVar])){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this ;
    }

    /**
     * Verifie le min et max d'une chaine
     */
    public function isMinMax($p_sVar, $p_sMessage, $p_iMin=0, $p_iMax=255, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(true == isset($this->m_aData[$p_sVar]) && true == is_string($this->m_aData[$p_sVar]) && $this->m_aData[$p_sVar] != ''){
            $length = strlen($this->m_aData[$p_sVar]);
            if($length<$p_iMin || $length>$p_iMax){
                $this->m_aError[$p_sVar][] = $p_sMessage ;
            }
        }
        return $this;
    }

    /**
     * Verifie si la chiane ne contient que des chiffres
     */
    public function isNumeric($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(FALSE == preg_match( '&^[0-9]+$&', $this->m_aData[$p_sVar])){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }


    /**
     * Verifie que le numeric est strictement entre les 2 valeurs
     */
    public function isBetween($p_sVar, $p_sMessage, $p_iMin=0, $p_iMax=255, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if($this->m_aData[$p_sVar]<$p_iMin || $this->m_aData[$p_sVar]>$p_iMax){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }

    /**
     * Verifie si une valeur est dans la liste
     */
    public function isInto($p_sVar, $p_sMessage, $p_aList, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(FALSE == isset($this->m_aData[$p_sVar]) || FALSE == in_array($this->m_aData[$p_sVar], $p_aList)){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }

    /**
     * Verifie si une valeur est dans la liste
     */
    public function isChecked($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(FALSE == isset($this->m_aData[$p_sVar]) || 'on' != $this->m_aData[$p_sVar]){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }

    /**
     * Vérifie si le fichier uploadé est bien du type demandé
     */
    public function isFileType($p_sVar, $p_sMessage, $p_aList, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(FALSE == isset($_FILES[$p_sVar]['type']) || FALSE == in_array($_FILES[$p_sVar]['type'],$p_aList)){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
    }

    public function isUrl($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(FALSE == preg_match( '&^(https|http):\/\/[a-z0-9.\/-]+$&', $this->m_aData[$p_sVar])){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }

    /**
     * Verifie aussi la coherence des dates (année bissexctile, ...)
     **/
    public function isFrenchDate($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(FALSE == preg_match( '&^(((0[1-9]|[12]\d|3[01])\/(0[13578]|1[02])\/((19|[2-9]\d)\d{2}))|((0[1-9]|[12]\d|30)\/(0[13456789]|1[012])\/((19|[2-9]\d)\d{2}))|((0[1-9]|1\d|2[0-8])\/02\/((19|[2-9]\d)\d{2}))|(29\/02\/((1[6-9]|[2-9]\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))))$&', $this->m_aData[$p_sVar])){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }

    /**
     * Verifie aussi la coherence des dates (année bissexctile, ...)
     **/
    public function isEnglishDate($p_sVar, $p_sMessage, $p_sSeparator, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(FALSE == preg_match( '&^(19|20)\d\d['. $p_sSeparator .'](0[1-9]|1[012])['. $p_sSeparator .'](0[1-9]|[12][0-9]|3[01])$&', $this->m_aData[$p_sVar])){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }

    /**
     * Vérifie pour un nom ou un prenom qu'il n'y ait que des lettres ou un espace
     **/
    public function isAlphaName($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(FALSE == preg_match( '&^[a-zA-ZáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ\ \-\']+$&', $this->m_aData[$p_sVar])){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }
    
    /**
     * Vérifie pour un champs qu'il n'y ait que des lettres sans espace ni ponctuation
     **/
    public function isAlphaWS($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(FALSE == preg_match( '&^[a-zA-ZáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ]+$&', $this->m_aData[$p_sVar])){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }

    /**
     * Vérifie pour un alpha-numérique
     **/
    public function isAlphaNum($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(FALSE == preg_match( '&^[0-9a-zA-ZáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ\ \-\']+$&', $this->m_aData[$p_sVar])){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }

    /**
     * Vérifie un numéro de téléphone
     **/
    public function isPhoneNumber($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }

        //$l_sPattern = '&^0[1-9][0-9]{8}$&' ; // 10 digits
        $l_sPattern = '&^[\+]*[0-9]{10,14}$&' ; // @todo : check regex : international phone numbers
        if(FALSE == preg_match($l_sPattern, str_replace(' ', '', $this->m_aData[$p_sVar]))){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }

    /**
     * Vérifie un numéro de téléphone français au format xx.xx.xx.xx.xx
     **/
    public function isFrenchPhone($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }

        $l_sPattern = '&^([0-9]{2})((\.[0-9]{2}){4})$&' ;
        if(FALSE == preg_match($l_sPattern, str_replace(' ', '', $this->m_aData[$p_sVar]))){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }

    /**
     * Vérifie si deux valeurs sont identiques
     **/
    public function isEqualTo($p_sVar, $p_sVarTo, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar]) && !empty($this->m_aError[$p_sVarTo])){
            return $this;
        }
        if( $this->m_aData[$p_sVar] != $this->m_aData[$p_sVarTo] ){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }

    /**
     * Vérifie si la chaine contient un caractère de chaque type (1 Maj., 1 Min., 1 chiffre, 1 spécial)
     **/
    public function isAllType($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }

        $lb_validate = TRUE;
        if(FALSE == preg_match( '&[a-z]+&', $this->m_aData[$p_sVar])) $lb_validate = FALSE;
        if(FALSE == preg_match( '&[A-Z]+&', $this->m_aData[$p_sVar])) $lb_validate = FALSE;
        if(FALSE == preg_match( '&[0-9]+&', $this->m_aData[$p_sVar])) $lb_validate = FALSE;
        if(FALSE == preg_match( '&[\&~"#\'{([\-|`_\\^@)°\]+=}£$€¤%µ*?,.;\/:§!<>]+&', $this->m_aData[$p_sVar])) $lb_validate = FALSE;

        if(FALSE == $lb_validate){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }
    
    /**
     * Vérifie le format d'un timestamp
     * 1488309835
     * @todo verifie on doit pouvoir faire mieux avec un objet date
     **/
    public function isTimestamp($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(strlen($this->m_aData[$p_sVar]) < 1 || strlen($this->m_aData[$p_sVar]) > 10 || FALSE == is_numeric($this->m_aData[$p_sVar])){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this ;
    }
    
    /**
     * Vérifie si la chaine recu correspond au format MD5
     **/
    public function isMd5($p_sVar, $p_sMessage, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(strlen($this->m_aData[$p_sVar]) != 32){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this ;
    }

    /**
     * Test la valeur de $_POST['g-recaptcha-response'] en utilisant le server side check 
     */
    public function isGoogleRecaptchaValid($p_sVar, $p_sMessage,$p_sPrivateKey, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        // On test 
        $l_oResult = $this->callGoogleCaptcha($p_sVar, $p_sPrivateKey ) ;
        
        if(FALSE == $l_oResult->success ){
            $code = 'error-codes'; // a cause du tiret on passe par une variable car $l_oResult->error-codes passe pas
            $this->m_aError[$p_sVar][] = $p_sMessage. ' ('.$l_oResult->$code[0].')' ;
        }
        return $this ;
    }

    /**
     * Server side check
     * https://developers.google.com/recaptcha/docs/verify
     */
    protected function callGoogleCaptcha($p_sVar, $p_sPrivateKey){

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query(
                    array(
                        'secret' => $p_sPrivateKey ,
                        'response' => $this->m_aData[$p_sVar] )
                )
            )
        );

        $context  = stream_context_create($opts);        
        $l_oResult = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context));
        
        return $l_oResult ;
    }


    /**
     * 
     **/
    public function checkCallback($p_sVar, $p_sMessage, $p_mCallback, $p_sChained=TRUE){
        if (TRUE === $p_sChained && !empty($this->m_aError[$p_sVar])){
            return $this;
        }
        if(is_callable($p_mCallback) && call_user_func($p_mCallback, (isset($this->m_aData[$p_sVar]) ? $this->m_aData[$p_sVar] : NULL)) === FALSE){
            $this->m_aError[$p_sVar][] = $p_sMessage ;
        }
        return $this;
    }
}
