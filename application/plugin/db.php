<?php
/**
 * Classe de gestion de base MySql avec MySqli
 *
 **/
class db extends corePlugin
{
    // Object connection a la base
    protected $m_oDb ;
    // Objet resultat
    protected $m_oResult ;
    // Configuration de la class DB
    protected $m_aConf ;
    // Temps cumulé d'execution des requetes
    protected $m_sSqlDuration ;
    // Liste des requetes executées
    protected $m_sQueries ;

    // constructeur
    public function __construct()
    {
        $this->m_aConf = core::$config['db'] ;
        $l_iTmp = microtime(TRUE);
        $this->m_oDb = new mysqli( $this->m_aConf['host'],
                                   $this->m_aConf['username'],
                                   $this->m_aConf['passwd'],
                                   $this->m_aConf['dbname'],
                                   $this->m_aConf['port']);

        //definition du charset de la connexion
        if(!$this->m_oDb->set_charset($this->m_aConf['charset'])){
            throw new Exception('Le jeu de caractère \''.$this->m_aConf['charset']
                                .'\' n\'a pas pu etre defini pour la connexion à
                                la base (Valeur courante :utf8, latin1, etc.)') ;
        }
        // Ajoute le temps d'execution de ces requetes
        $this->addToTime(microtime(TRUE)-$l_iTmp);
    }

    // destructeur
    public function __destruct()
    {
        $this->m_oDb->close();
    }

    /**
     * Tiens le compteur de temps d'execution des requetes
     */
    protected function addToTime($p_iTime){
        $this->m_sSqlDuration += $p_iTime;
    }
    
    /**
     * Liste de requetes executées
     */
    protected function addToQueries($p_sSql){
        $this->m_sQueries .= $p_sSql.'<br>--------------<br/>';
    }

    /**
     * Lance une requete
     * @param string $p_rSql Requete
     * @return object Objet result ou FALSE
     **/
    public function query($p_rSql)
    {
        $l_iTmp = microtime(TRUE);
        $this->m_oResult = $this->m_oDb->query($p_rSql) ;
        // Ajoute le temps d'execution de ces requetes
        $this->addToTime(microtime(TRUE)-$l_iTmp);
        $this->addToQueries($p_rSql);
        
        if(FALSE != $this->m_oResult){
            return $this->m_oResult;
        }
        else{
            throw new Exception('ERREUR DB : '.mysqli_error($this->m_oDb).' dans la requete "'.$p_rSql.'"');
        }
    }

    /**
     * Lance une requete
     * @param string $p_rSql Requete
     * @param bool Indique si on retourn le nombre d'enregistrement affecté ou
     * le dernier ID inseré
     * @return int Id autoincrement
     **/
    public function exec($p_rSql)
    {
        $l_iTmp = microtime(TRUE);
        $this->m_oResult = $this->m_oDb->query($p_rSql) ;
        // Ajoute le temps d'execution de ces requetes
        $this->addToTime(microtime(TRUE)-$l_iTmp);
        $this->addToQueries($p_rSql);
        
        if(FALSE != $this->m_oResult){
            return $this->lastId();
        }
        else{
            throw new Exception('ERREUR DB : '.mysqli_error($this->m_oDb).' dans la requete "'.$p_rSql.'"');
        }
    }

    /**
     * Lance une requete et recupere la premiere ligne
     * @param string $p_rSql Requete
     * @return array Tableau indexé de la premiere ligne ou FALSE
     **/
    public function queryRow($p_rSql)
    {
        $l_iTmp = microtime(TRUE);
        $this->m_oResult = $this->m_oDb->query($p_rSql) ;
        // Ajoute le temps d'execution de ces requetes
        $this->addToTime(microtime(TRUE)-$l_iTmp);
        $this->addToQueries($p_rSql);
        
        if(FALSE != $this->m_oResult){
            return $this->m_oResult->fetch_object() ;
        }
        else{
            throw new Exception('ERREUR DB : '.mysqli_error($this->m_oDb).' dans la requete "'.$p_rSql.'"');
        }
    }

    /**
     * Lance une requete et recupere le premier champ de la
     * premiere ligne.
     * @param string $p_rSql Requete
     * @return array Tableau indexé de la premiere ligne ou FALSE
     **/
    public function queryOne($p_rSql)
    {
        $l_iTmp = microtime(TRUE);
        $this->m_oResult = $this->m_oDb->query($p_rSql) ;
        // Ajoute le temps d'execution de ces requetes
        $this->addToTime(microtime(TRUE)-$l_iTmp);
        $this->addToQueries($p_rSql);
        
        if(FALSE != $this->m_oResult){
        $row = $this->m_oResult->fetch_row() ;
        return $row[0] ;
        }
        else{
            throw new Exception('ERREUR DB : '.mysqli_error($this->m_oDb).' dans la requete "'.$p_rSql.'"');
        }
    }

    /**
     * Renvoi le dernier id inséré en base
     **/
    public function lastId()
    {
        return $this->m_oDb->insert_id ;
    }

    /**
     * Renvoi le nombre d'enregistrement dans le resultat de la requete
     **/
    public function rowCount()
    {
        return $this->m_oResult->num_rows ;
    }

    /**
     * Echappe une chaine de caractère
     * @param string $p_sString Chaine à échapper
     *
     * @return string Chaine echappée
     **/
    public function escape($p_sString, $p_bEncode = FALSE)
    {
        if(FALSE == is_array($p_sString)){
            if(TRUE == $p_bEncode){
                $p_sString = htmlentities($p_sString, ENT_NOQUOTES, $this->m_aConf['charset']);
            }
            return $this->m_oDb->real_escape_string($p_sString) ;
        }
        else{
            return FALSE ;
        }
    }

    /**
     *
     */
    public function onFinish(){
        if(TRUE == $this->isLoaded('debug')){
            $this->debug->addToDebug('Temps d\'éxécution SQL', round($this->m_sSqlDuration,5).' sec','time');
            if(TRUE == isset(core::$config['db']['dumpToDebug']) && TRUE == core::$config['db']['dumpToDebug']){
                $this->debug->addToDebug('Requetes SQL', $this->m_sQueries,'query');
            }
        }
    }
    
     /**
     * Lance une requete et recupere la premiere ligne
     * @param string $p_rSql Requete
     * @return array Tableau indexé de la premiere ligne ou FALSE
     **/
    public function getConnect()
    {
        return $this->m_oDb;
    }
}
