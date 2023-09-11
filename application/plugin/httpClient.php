<?php
use League\CommonMark\CommonMarkConverter; // Helper to convert markdown to html

/**
 * UTM Framework :: Plugin http Client
 *
 * @author N.Namont 2021
 *
 *
 * @file
 * Client http basé sur curl
 */
class httpClient extends corePlugin
{
    private $m_oCurl ;

    public function __construct(){
        // initialisation de la session
        $this->m_oCurl = curl_init();
    }

    public function __destruct(){
        curl_close($this->m_oCurl) ;
    }

    /**
     * 
     */
    public function getData($p_sUrl, $p_sToken){

        $l_aOpt = array(
            CURLOPT_URL             => $p_sUrl,
            CURLOPT_HEADER          => 0,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_HTTPHEADER      => array(
                'accept: application/json',
                'Authorization: Bearer '.$p_sToken),
        );

        // configuration des options
        curl_setopt_array($this->m_oCurl, $l_aOpt);
        
        // exécution de la session
        $l_oResult = curl_exec($this->m_oCurl);
        if(FALSE == $l_oResult){
            throw new Exception('Strapi connection failed') ;
        }
        return $l_oResult;
    }

    /**
     * Convertit le MD common en HTML
     * https://packagist.org/packages/league/commonmark
     */
    public function convertMd2Html($p_sMdContent){
        
        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        
        return  $converter->convertToHtml($p_sMdContent);
    }
}