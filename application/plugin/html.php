<?php
/**
 * Plugin d'extansion de la gestion des erreurs du framework UTM
 * selon la config
 * - affiche l'erreur
 * - envoi un mail
 * - redirige sur une page spéciale
 *
 **/
class html extends corePlugin
{
    protected $m_aMetas = array() ;
    protected $m_sTitle = '' ;

    /**
     *
     **/
    public function setTitle($p_sTitle){
        $this->m_sTitle = $p_sTitle ;
    }

    /**
     *
     **/
    public function getTitle(){
        return $this->m_sTitle ;
    }

    /**
     *
     **/
    public function addHtmlHead($p_sName, $p_sTag, array $p_aParams){
        $this->m_aMetas[$p_sTag][$p_sName] = $p_aParams;
    }

    /**
     *
     **/
    public function getHtmlHead($p_sTag = NULL, $p_sTagName = NULL){
        if (NULL == $p_sTag) {
            return $this->m_aMetas;
        }
        elseif (NULL == $p_sTagName) {
            return $this->m_aMetas[$p_sTag];
        }
        elseif (!empty($this->m_aMetas[$p_sTag][$p_sTagName])) {
            return $this->m_aMetas[$p_sTag][$p_sTagName];
        } else {
            return array();
        }
    }

    /**
     *
     **/
    public function renderHtmlHead($p_sTag = NULL){
        if(NULL != $p_sTag) {
            if (empty($this->m_aMetas[$p_sTag])) {
                return FALSE;
            }
            $l_aTags = array($this->m_aMetas[$p_sTag]);
        }
        else {
            $l_aTags = $this->m_aMetas;
        }
        $l_sReturn = '';
        foreach ($l_aTags as $l_sTagName => $l_aTag) {
          foreach ($l_aTag as $l_sName => $l_aParams) {
            $l_sReturn .= '<' . $l_sTagName;
            foreach ($l_aParams as $l_sParam => $l_sValue) {
              $l_sReturn .= ' ' . $l_sParam . '="' . htmlentities($l_sValue, ENT_COMPAT) . '"';
            }
            $l_sReturn .= "/>\n";
          }
        }
        return $l_sReturn ;
    }
}
