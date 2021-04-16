<?php
/**
 * UTM Framework / plugin de gestion de la gestion des acces a certains
 * controlleurs et certaines actions via une methode de callback
 *
 * @name protect
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
 * @author N.Namont Dizagn 2014
 *
 * @file
 * Permet de proteger certains éléments du site en fonction des controlleurs et
 * des actions appelés tel que defini dans la conf dans un format ctrl.act ou
 * chacun peut prendre son nom ou la valeur *
 *
 * -----------------------------------------------------------------------------
 * /!\ Le developpeur doit implémenter la methode checkAuthCallBack() afin de
 * définir la fonction de rappel pour valider l'authentification selon le modele
 * de son choix.
 * -----------------------------------------------------------------------------
 *
 **/
class protect extends corePlugin
{
    protected $m_aConfig;
    protected $m_aProtect;


    public function __construct(){
        $this->m_aConfig = core::$config['protect'];
    }

    /**
     * Au lancement du plugin, il recupere la liste des controlleurs a inclure
     * dans la verif et la liste des controlleurs a exclure
     */
    public function onPostRequest(){
        $l_a2Protect = $l_a2Exclude = array();
        // On recupere les couples ctrl.act ou mod.ctrl.act des url à protéger
        if(TRUE == isset($this->m_aConfig['url2protect']) && trim($this->m_aConfig['url2protect']) != ''){
            $l_a2UrlProtect = explode(';', $this->m_aConfig['url2protect']);
            $this->m_aProtect = $this->extractValue($l_a2UrlProtect) ;
        }
        
        //@todo : recuperer dans la config un eventuel callback != de celui ci user::isAuth

        // On récupere les infos (ctrl & act) de la requete en cours et on
        // valide si la page doit etre protégée et si la methode de verification
        // renvoi false
        if(TRUE == $this->check2protect(NULL, core::instance()->getRequest()->getController(), core::instance()->getRequest()->getAction()) &&
            FALSE == $this->checkAuthCallback()){
            if(FALSE == headers_sent()){
               header('Location: index.php?ctrl='.$this->m_aConfig['redirectCtrl'].'&act='.$this->m_aConfig['redirectAct'] ) ;
               exit;
            }
            else{
                throw new Exception ('Page protégée !!');
            }
        }
    }

    /**
     * Permet de transformer les entrées de la config au format string en
     * tableau multidim pour etre comparer plus tard.
     * @param array $p_aUrl tableau contenant les couples ctrl.Act
     * @return array
     *
     * @todo gérer les modules !
     */
    protected function extractValue($p_aUrl){
        // init
        $l_aValue = array();

        foreach($p_aUrl AS $l_sUrl){

            // On recupere un couple basé sur 3, 2 ou 1 entrées (ex: mod.ctrl.act)
            $l_aExtract = explode('.', trim($l_sUrl)) ;

            // gestion avec module, controlleur, action
            if(count($l_aExtract) == 3){
                //$l_aValue['mod'][$l_aExtract[0]][$l_aExtract[0]] = $l_aExtract[1] ;
            }
            // gestion avec controlleur, action
            elseif(count($l_aExtract) == 2){

                // CAS des controlleurs * : on cour-circuite tout et on sort :)
                if($l_aExtract[0] == '*'){
                    $l_aValue['ctrl'] = '*' ;
                    return $l_aValue ;
                }

                // CAS des ACTIONS
                // On gere le cas du joker étoile pour les actions
                if($l_aExtract[1] == '*'){
                    unset($l_aValue['ctrl'][$l_aExtract[0]]);
                    $l_aValue['ctrl'][$l_aExtract[0]][] = '*' ;
                }
                // Si on a une étoile on zappe les prochaines entrées
                else if(TRUE == isset($l_aValue['ctrl'][$l_aExtract[0]][0]) &&
                        $l_aValue['ctrl'][$l_aExtract[0]][0] == '*'){
                    continue;
                }
                // Sinon on enregistre chaque cas
                else{
                    $l_aValue['ctrl'][$l_aExtract[0]][] = $l_aExtract[1] ;
                }


            }
            else{
                throw new Exception('Le format de l\'entrée présente dans la config "protect" n\'est pas valide') ;
            }
        }
        return $l_aValue ;
    }

    /**
     *
     * @param type $p_sModule
     * @param type $p_sCtrl
     * @param type $p_sAct
     */
    protected function check2protect($p_sModule, $p_sCtrl, $p_sAct){
        $l_bProtect = FALSE ;

        // On vérifie si le controlleur est *
        if($this->m_aProtect['ctrl'] == '*'){
            $l_bProtect = TRUE ;
        }
        // On si toutes les actions de ce controlleur sont protégées
        else if(TRUE == array_key_exists($p_sCtrl, $this->m_aProtect['ctrl']) && $this->m_aProtect['ctrl'][$p_sCtrl][0] == '*'){
            $l_bProtect = TRUE ;
        }
        // Sinon on vérifie unitairement
        else if(TRUE == array_key_exists($p_sCtrl, $this->m_aProtect['ctrl']) && TRUE == in_array($p_sAct, $this->m_aProtect['ctrl'][$p_sCtrl])){
            $l_bProtect = TRUE ;
        }

        return $l_bProtect ;
    }

    /**
     * C'est la methode utilisée pour verifier si l'utilisateur est correctement
     * authentifié elle doit renvoyer FALSE en cas d'erreur
     *
     * @TODO a coder par le developpeur !!!! pour chaque projet
     * @return mixed FALSE en cas d'echec
     */
    protected function checkAuthCallback($p_sClass='user', $p_sMethod='isAuth'){
        
        $l_oClass = coreModel::factory($p_sClass) ;       
        
        // On vérifie que la classe et la methode existe
        if(TRUE == is_object($l_oClass) && 
           TRUE == method_exists($l_oClass, $p_sMethod)){
            return $l_oClass->$p_sMethod();
        }
        throw new Exception('La méthode de callBack du plugin "protect" : "'.$p_sMethod.'" n\'existe pas dans la classe modele "'.$p_sClass.'"' ) ;
    }

}
