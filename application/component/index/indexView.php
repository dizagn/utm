<?php
/**
 * Vue par defaut
 *
 * Toutes les vues doivent étendre coreView pour profiter des methodes natives
 * du framework
 **/
class index_indexView extends coreView
{
    // Methode appelée automagiquement pour rendre la vue
    public function render()
    {
        $content = coreModel::factory('myModel')->getDefaultContent() ;
        return $content;
    }
}
