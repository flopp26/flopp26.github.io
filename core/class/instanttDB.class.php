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

class instanttDB
{
    public function ahhh($idEqLogic)
    {
        $sql = 'SELECT c.id, c.logicalId, its.instantt_state_value 
                FROM instantt_state its
                    INNER JOIN cmd c ON c.id = its.instantt_state_id
                WHERE its.`instantt_state_eqlogic` = :instantt_state_eqlogic';

        $result = DB::Prepare($sql, ['instantt_state_eqlogic' => $idEqLogic], DB::FETCH_TYPE_ALL);

        $cmds = [];
        //reorganisation du tableau pour faciliter l'ordre
        foreach ($result as $r) {
            $cmds[$r['logicalId']] = $r;
        }

        return $cmds;
    }

    public function getCmdsFromStateId($stateId)
    {
        $sql = 'SELECT * FROM `instantt_cmds` WHERE `instantt_state_id` = :instantt_state_id';

        return DB::Prepare($sql, ['instantt_state_id' => $stateId], DB::FETCH_TYPE_ALL);
    }

    public function getCmdsFromStateEqLogicId($instantId, $idEqLogic)
    {
        $parameters = array(
            'instantt_id' => $instantId,
            'instantt_state_eqlogic' => $idEqLogic
        );

        $sql = 'SELECT * FROM `instantt_state` WHERE `instantt_id` = :instantt_id AND `instantt_state_eqlogic` = :instantt_state_eqlogic';

        return DB::Prepare($sql, $parameters, DB::FETCH_TYPE_ALL);
    }

    public function insertCommand($stateId, $actions)
    {
        foreach ($actions as $action) {

            $parameters = array(
                'instantt_state_id' => $stateId,
                'instantt_cmd_id' => $action['id'],
                'instantt_cmd_name' => $action['name']
            );

            $sql = 'INSERT INTO `instantt_cmds` SET
                    `instantt_state_id` = :instantt_state_id, `instantt_cmd_id` = :instantt_cmd_id, `instantt_cmd_name` = :instantt_cmd_name';

            try {
                DB::Prepare($sql, $parameters, DB::FETCH_TYPE_ROW);
            } catch (Exception $e) {
                //Log::add('instantt', 'error', $e);
            }
        }
    }

    public function removeInstantT($instantId)
    {
        $sql = 'DELETE FROM `instantt_state` WHERE `instantt_id` = :instantt_id';

        return DB::Prepare($sql, ['instantt_id' => $instantId], DB::FETCH_TYPE_ROW);
    }

    public function insertInstantT($instantId, $idEqLogicState, $idCmdState, $state)
    {
        $parameters = array(
            'instantt_id' => $instantId,
            'instantt_state_eqlogic' => $idEqLogicState,
            'instantt_state_id' => $idCmdState,
            'instantt_state_value' => $state
        );

        $sql = 'INSERT INTO `instantt_state` SET
                `instantt_id` = :instantt_id, `instantt_state_eqlogic` = :instantt_state_eqlogic, `instantt_state_id` = :instantt_state_id, `instantt_state_value` = :instantt_state_value';

        try {
            return DB::Prepare($sql, $parameters, DB::FETCH_TYPE_ROW);
        } catch (Exception $e) {
            dump($e);
        }

        return false;
    }

    public function getStateFromInstantId($instantId)
    {
        $sql = 'SELECT * FROM `instantt_state` WHERE `instantt_id` = :instantt_id GROUP BY `instantt_state_eqlogic`';

        $result = DB::Prepare($sql, ['instantt_id' => $instantId], DB::FETCH_TYPE_ALL);

        return $result;
    }

    public static function deleteAllCmds()
    {
        return DB::Prepare('DELETE FROM `instantt_cmds`', [], DB::FETCH_TYPE_ROW);
    }
}