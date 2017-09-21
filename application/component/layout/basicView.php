<?php
/**
 * Layout par defaut
 *
 **/
class layout_basicView extends coreView
{
    // Methode appelée automagiquement pour rendre la vue
    public function render()
    {
        $this->load('basic.phtml');

        // Plugin compressor 
        $this->setVar('script', $this->loadJs('js/script.js')) ;
        $this->setVar('style',  $this->loadCss('css/style.css')) ;
        
        // SEO ...
        //$this->setVar('pageTitle',  'UTM framework') ;        
        
        return $this->output();
    }
}
