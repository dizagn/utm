<?php
/**
 * Plugin de debug permettant d'afficher des informations pour les developpeurs
 **/
class debug extends corePlugin
{
    protected $m_aDebug = array(); // variable renvoyée pour l'affichage du tableau de debug
    protected $m_sMemory; // memoire utilisée
    protected $m_sPeakMemory; // pic de mémoire utilisée
    protected $m_sInitTime; // pic de mémoire utilisée
    protected $color = '#00E500';
    protected $m_sStopDebug = FALSE; // indique si un autre plugin a demandé l'arret du debug

    public function onStart(){
        $this->m_sInitTime = microtime(TRUE);
        $this->m_sMemory = (TRUE == extension_loaded('xdebug'))? xdebug_memory_usage() : memory_get_usage(FALSE) ;
        $this->m_sPeakMemory = TRUE == extension_loaded('xdebug')? xdebug_peak_memory_usage() : memory_get_peak_usage(FALSE) ;
        if(TRUE == isset(core::$config['debug']['color'])){
            $this->color = core::$config['debug']['color'] ;
        }
    }

    /**
     * Format l'affichage en fonction du type d'appel : HTTP ou CLI
     * @param $p_sString string Chaine de texte a afficher
     * @return string Chaine de texte formatté avec pre ou \n
     */
    protected function format(){
        if(PHP_SAPI != 'cli'){
            return $this->makeDebugTable();
        }
        else{
            return '';
        }
    }

    /**
     * Vérifie si le debug doit etre activé
     */
    public function isActive(){
        // Debug activé
        if (FALSE == core::$config['debug']['display'] ){
            return FALSE;
        }
        // Appel ajax
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'xmlhttprequest'){
            return FALSE;
        }
        // On vérifie si un autre plugin n'aurait pas demandé l'arret de l'execution
        if($this->m_sStopDebug != FALSE){
            return FALSE;
        }

        return TRUE;
    }

    public function stop(){
        $this->m_sStopDebug = TRUE ;
    }

    /**
     * Affiche les infos de debug : memoire utilisée et temps d'execution avec
     * differentes methodes selon que l'on utilise xdebug ou pas
     * Cette methode se déclenche sur l'évenement onFinish
     */
    public function onUltimateFinish(){
        if(FALSE == $this->isActive()){
            return FALSE;
        }

        if(TRUE == extension_loaded('xdebug')){
            $this->addToDebug('Mémoire système', ($this->m_sMemory/1000).' Ko ('.($this->m_sPeakMemory/1000).' Ko)', 'memory');
            $this->addToDebug('Mémoire Applicative', ((xdebug_memory_usage()-$this->m_sMemory)/1000).' Ko ('.((xdebug_peak_memory_usage()-$this->m_sPeakMemory)/1000).' Ko)', 'memory');
            $this->addToDebug('Temps d\'éxécution',round(xdebug_time_index(),3).' sec.' , 'time');
        }else{
            $this->addToDebug('Temps d\'éxécution',round(microtime(true)-$this->m_sInitTime,3).' sec.' , 'time');
            $this->addToDebug('Mémoire système', (memory_get_usage(FALSE)/1000).' Ko ('.(memory_get_peak_usage(FALSE)/1000).' Ko)', 'memory');
            $this->addToDebug('Mémoire Applicative', ((memory_get_usage(FALSE)-$this->m_sMemory)/1000).' Ko ('.((memory_get_peak_usage(FALSE)-$this->m_sPeakMemory)/1000).' Ko)', 'memory');
        }
        echo $this->format() ;
    }

    public function addToDebug($p_sKey, $p_sValue, $p_sCategory = 'all'){
        $this->m_aDebug[$p_sCategory][$p_sKey] = $p_sValue;
    }

    protected function makeDebugTable(){
        $l_sString ='<pre><style>
                    #debugOutput {font-size:12px;}
                    #debugOutput {min-width:500px;border:2px solid '.$this->color.';margin:10px}
                    #debugOutput th{height:16px;width:180px;background-color:'.$this->color.'; color:#000;font-weight:bold;text-align:right;padding:0px 8px;}
                    #debugOutput td{height:16px;background-color:#000; color:'.$this->color.';font-weight:normal;padding:0px 6px;}
                </style>
                <table id="debugOutput">';
        // On ajoute les lignes de debug au tableau
        ksort($this->m_aDebug);
        foreach($this->m_aDebug AS $l_sCategory => $l_aValues){
            foreach($l_aValues AS $l_sLabel => $l_sValue){
                $l_sString .= '<tr><th>'.$l_sLabel.'</th><td>'.$l_sValue.'</td></tr>';
            }
        }
        return $l_sString.'</table></pre>';
    }

    /**
     * Var_dump amélioré
     * @param $p_mElement Elements a dumper
     * @param $stop boolean Indique si on doit s'arreter apres le le dump ou pas
     */
    public function dbg($p_mElement, $stop = FALSE ){

        echo '<pre>'.var_dump($p_mElement).'</pre>' ;

        if($stop != FALSE){
            exit;
        }
    }
}
