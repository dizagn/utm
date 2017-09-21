<?php
/**
 * Layout par defaut
 *
 **/
class index_indexView extends coreView
{
    // Methode appelée automagiquement pour rendre la vue
    public function render()
    {
        // Load template
        $this->load('index.phtml');
       
        // SEO ...
        $this->setVar('pageTitle',  'My first page with UTM') ;
        $this->addHtmlHead('meta', 'desc', array('name' => 'description', 'content' => 'this is my incredible meta desc')) ;
        
        return $this->output('basic');
    }
}
