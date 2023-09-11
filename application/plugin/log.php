<?php
/**
 * UTM Framework :: Plugin log
 *
 * LICENSE
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.dizagn.com/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@dizagn.com so we can send you a copy immediately.
 *
 * @license http://framework.dizagn.com/license New BSD License
 * @copyright  Copyright (c) 2002-2023 Dizagn. (http://www.dizagn.com)
 * @link http://framework.dizagn.com
 * @author J.M. Ulmet 2023
 * @author J.Kuijer 2023
 *
 *
 * @log
 * Gestionnaire de logs
 */
class log extends corePlugin {

/**
 * Log that
 * @param string label, indique l'action réalisé
 * @param integer level, sévérité (notice, warning, critical)
 * @param string ps_request, requête effectuée
 * @param string ps_method, class et fonction où s'éxecute le log
 * @param integer pi_line, ligne dans la méthode où s'éxecute le log
 * @return boolean
 */
  public function logThat($ps_label, $pi_level = 1, $ps_request = "", $ps_method = "", $pi_line = ""){
    $ls_sql = "INSERT INTO `log` "
            . "SET `type`=".intval($pi_level).", "
                . "`request`='".$this->escape($ps_request)."', "
                . "`method`='".$this->escape($ps_method)."', "
                . "`line`=".intval($pi_line).", "
                . "`message`='".$this->escape($ps_label)."', "
                . "`date`=NOW()";

    return $this->exec($ls_sql);
  }
}
