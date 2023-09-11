<?php
/**
 * Layout par defaut
 *
 **/
class index_exampleView extends coreView
{
    // Methode appelée automagiquement pour rendre la vue
    public function render()
    {
        // Load template
        $this->load('example.phtml');
       
        // SEO ...
        $this->setVar('pageTitle',  'My first example page with UTM') ;
        $this->addHtmlHead('meta', 'desc', array('name' => 'description', 'content' => 'this is my incredible meta desc')) ;
        
        return $this->output('basic');
    }
}
