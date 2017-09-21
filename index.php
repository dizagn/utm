<?php
/**
 * Page de redirection au cas ou l'alias ne serait pas créer
 **/
if(TRUE == isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != ''){
    header('Location:www/index.php?'.$_SERVER['QUERY_STRING']) ;
}else{
    header('Location:www/index.php') ;
}
