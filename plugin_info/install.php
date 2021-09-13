<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

// Fonction exécutée automatiquement après l'installation du plugin
  function instantt_install()
  {
      // Create the table where the triggers are stored
      $sql = 'CREATE TABLE IF NOT EXISTS `instantt_state` ('
          . '`added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
          . '`instantt_id` INT(11) NOT NULL,'
          . '`instantt_state_eqlogic` VARCHAR(50) DEFAULT NULL,'
          . '`instantt_state_id` VARCHAR(50) NOT NULL,'
          . '`instantt_state_value` VARCHAR(50) NOT NULL'
          . ') ENGINE=InnoDB DEFAULT CHARSET=utf8;';
      DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);


      // Create the table where the triggers are stored
      $sql = 'CREATE TABLE IF NOT EXISTS `instantt_cmds` ('
          . '`instantt_state_id` INT(11) NOT NULL,'
          . '`instantt_cmd_id` INT(11) NOT NULL,'
          . '`instantt_cmd_name` VARCHAR(50) NOT NULL,'
          . 'UNIQUE(instantt_state_id, instantt_cmd_id)'
          . ') ENGINE=InnoDB DEFAULT CHARSET=utf8;';
      DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
  }

// Fonction exécutée automatiquement après la mise à jour du plugin
  function instantt_update()
  {

  }

// Fonction exécutée automatiquement après la suppression du plugin
  function instantt_remove()
  {
      DB::Prepare('DROP TABLE IF EXISTS `instantt_cmds`;', array(), DB::FETCH_TYPE_ROW);
      DB::Prepare('DROP TABLE IF EXISTS `instantt_state`;', array(), DB::FETCH_TYPE_ROW);
  }

?>
