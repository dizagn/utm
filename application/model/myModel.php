<?php
/**
 * Classe modele de test fournie par defaut avec le framework
 *
 * Toutes les classes modeles doivent étendre coreModel pour profiter des
 * methodes natives du framework
 **/
class myModel extends coreModel
{
    /**
     * Methode de démo d'utilisation d'un model
     **/
    public function getDefaultContent(){
        return 'UTM default content :)' ;
    }
}
