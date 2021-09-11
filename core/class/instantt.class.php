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

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/instanttHelper.class.php';
require_once __DIR__  . '/instanttActions.class.php';
require_once __DIR__  . '/instanttFunction.class.php';
require_once __DIR__  . '/instanttDB.class.php';

function dump($debug)
{
    echo '<pre>' . print_r($debug, true) . '</pre>';
}

class instantt extends eqLogic
{
    /*     * *********************Méthodes d'instance************************* */

    // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert()
    {

    }

    // Fonction exécutée automatiquement après la création de l'équipement
    public function postInsert()
    {

    }

    // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate()
    {
        $this->checkEquipement();


        //DB::Prepare('DROP TABLE IF EXISTS `instanttDB`;', array(), DB::FETCH_TYPE_ROW);
        //DB::Prepare('DROP TABLE IF EXISTS `instantt_cmds`;', array(), DB::FETCH_TYPE_ROW);
    }

    // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate()
    {
        $this->createEquipements();
    }

    // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
    public function preSave()
    {

    }

    // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
    public function postSave()
    {
        $this->createCommandsEquipements();
    }

    // Fonction exécutée automatiquement avant la suppression de l'équipement
    public function preRemove()
    {

    }

    // Fonction exécutée automatiquement après la suppression de l'équipement
    public function postRemove()
    {

    }

    /*     * **********************Getteur Setteur*************************** */

    public function createEquipements()
    {
        $instantHelper = new instanttHelper();
        $instanttDb = new instanttDB();

        log::add('instantt', 'debug', $this->getHumanName() . ' Entering postUpdate');

        $triggers = $this->getConfiguration('triggers');
        foreach ($triggers as $trigger) {

            if($triggerHumanReadable = jeedom::toHumanReadable($trigger)){
                if(isset($triggerHumanReadable['cmd'])){
                    $triggerHumanReadable = $triggerHumanReadable['cmd'];
                }
            }

            log::add('instantt', 'debug', sprintf('%s %s - Création des commandes', $this->getHumanName(), $triggerHumanReadable));

            $cmds = array();
            $cmdName = str_replace('#', '', $trigger['cmd']);

            // gestion des équipement complet
            if (substr($cmdName, 0, 7) == 'eqLogic') {
                $idEqLogic = str_replace('eqLogic', '', $cmdName);
                $cmds = $instantHelper->getCommandsInfoFromEqLogic($idEqLogic);
            } else {
                $cmd = cmd::byId(str_replace('#', '', $trigger['cmd']));
                if (!is_object($cmd)) {
                    log::add('instantt', 'debug', $this->getHumanName() . ' $cmd is not object');
                    continue;
                }

                $cmds[] = $cmd;
            }

            log::add('instantt', 'debug', sprintf('%s %s - %s commande(s) info et autorisée(s) trouvée(s)', $this->getHumanName(), $triggerHumanReadable, count($cmds)));

            foreach ($cmds as $cmd) {
                $cmdId = $cmd->getId();
                if($cmd->getEqType() == 'groupe'){
                    $cmdStateConfig = $cmd->getConfiguration();
                    if(is_array($cmdStateConfig)){
                        $cmdId = str_replace('#', '', $cmdStateConfig['state']);
                        $cmd = cmd::byId($cmdId);
                    }
                }

                $actions = $instantHelper->getCommandsActions($cmd);

                log::add('instantt', (count($actions) == 0) ? 'error' : 'debug', sprintf('%s %s - %s action(s) trouvée(s) pour la commande %s%s', $this->getHumanName(), $triggerHumanReadable, count($actions), $cmd->getHumanName(), (count($actions) > 0) ? null : ' - [logicalId] = ' . $cmd->getLogicalId()));

                if (is_array($actions) && count($actions) > 0) {
                    $instanttDb->insertCommand($cmdId, $actions);
                }
            }
        }
    }

    public function createCommandsEquipements()
    {
        $cmd = $this->getCmd(null, 'make_instantt');
        if (!is_object($cmd)) {
            $cmd = new instanttCmd();
            $cmd->setLogicalId('make_instantt');
            $cmd->setIsVisible(1);
            $cmd->setType('action');
            $cmd->setName(__('Faire un instantT', __FILE__));
        }

        $cmd->setEqLogic_id($this->getId());
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->save();

        $cmd = $this->getCmd(null, 'load_instantt');
        if (!is_object($cmd)) {
            $cmd = new instanttCmd();
            $cmd->setLogicalId('load_instantt');
            $cmd->setIsVisible(1);
            $cmd->setType('action');
            $cmd->setName(__('Charger un instantT', __FILE__));
        }

        $cmd->setEqLogic_id($this->getId());
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->save();
    }

    public function makeInstantT()
    {
        $instanttDb = new instanttDB();
        $instantHelper = new instanttHelper();
        $instanttDb->removeInstantT($this->getId());

        $triggers = $this->getConfiguration('triggers');
        foreach ($triggers as $trigger) {

            if($triggerHumanReadable = jeedom::toHumanReadable($trigger)){
                if(isset($triggerHumanReadable['cmd'])){
                    $triggerHumanReadable = $triggerHumanReadable['cmd'];
                }
            }

            log::add('instantt', 'debug', sprintf('%s %s - Equipement', $this->getHumanName(), $triggerHumanReadable));

            $cmds = array();
            $idEqLogic = null;

            if (is_numeric(str_replace('#', '', $trigger['cmd']))) {

                $cmd = cmd::byId(str_replace('#', '', $trigger['cmd']));
                if (!is_object($cmd)) {
                    log::add('instantt', 'debug', $this->getHumanName() . ' $cmd is not object');
                    continue;
                }

                $cmds[] = $cmd;

            } else {
                // probablement un eqLocic
                if (substr(str_replace('#', '', $trigger['cmd']), 0, 7) == 'eqLogic') {
                    $idEqLogic = str_replace('eqLogic', '', str_replace('#', '', $trigger['cmd']));
                    $cmds = $instantHelper->getCommandsInfoFromEqLogic($idEqLogic);
                }
            }


            log::add('instantt', 'debug', sprintf('%s %s - %s commande(s) infos trouvée(s)', $this->getHumanName(), $triggerHumanReadable, count($cmds)));


            try {
                foreach ($cmds as $cmd) {

                    if($cmd->getEqType() == 'groupe'){
                        $cmdStateConfig = $cmd->getConfiguration();
                        if(is_array($cmdStateConfig)){
                            $idCmdStateFromGroupe = str_replace('#', '', $cmdStateConfig['state']);
                            $cmd = cmd::byId($idCmdStateFromGroupe);
                        }
                    }

                    $state = $cmd->execCmd();
                    $instanttDb->insertInstantT($this->getId(), $idEqLogic, $cmd->getId(), $state);

                    log::add('instantt', 'debug', sprintf('%s #%s# - valeur: %s', $this->getHumanName(), $cmd->getHumanName(), $state));

                }
            } catch (Exception $e) {
                log::add('instantt', 'debug', 'ERREUR sur la commande ' . $trigger['cmd']);
            }
        }
    }

    public function loadInstantT()
    {
        $instanttDb = new instanttDB();
        $instanttHelper = new instanttHelper();

        $instantId = $this->getId();
        $instantState = $instanttDb->getStateFromInstantId($instantId);

        // permet de charger uniquement ce qui existe comme trigger et pas tout ce qu'il y a dans l'instantt
        $listTrigger = array();
        $triggers = $this->getConfiguration('triggers');
        foreach ($triggers as $trigger) {
            $listTrigger[] = str_replace('#', '', $trigger['cmd']);
        }

        foreach ($instantState as $state) {

            $cmdStateId = $state['instantt_state_id'];
            $cmdStateEqLogicId = $state['instantt_state_eqlogic'];

            if (in_array($cmdStateId, $listTrigger) || in_array('eqLogic' . $cmdStateEqLogicId, $listTrigger)) {

                $isEqLogic = (isset($state['instantt_state_eqlogic'])) ? true : false;

                // on va récuper la commande d'état ou plusieurs si eqlogics
                $cmdsState = [];
                $cmdsState[] = ['state_value' => $state['instantt_state_value'], 'cmd' => cmd::byId($cmdStateId)];
                if ($isEqLogic) {

                    if (count($cmdsState) == 0) {
                        log::add('instantt', 'error', sprintf('count($cmdsState) == 0'));
                        continue;
                    }

                    $cmdsState = $instanttHelper->getCommandsStateForEqLogic($instantId, $cmdsState[0]['cmd'], $cmdStateEqLogicId);
                }

                if (is_array($cmdsState) && count($cmdsState) > 0) {

                    foreach ($cmdsState as $data) {

                        $cmdState = $data['cmd'];
                        $stateValue = $data['state_value'];
                        $cmds = $instanttDb->getCmdsFromStateId($cmdState->getId());


                        $cmdToRun = $instanttHelper->guessCommandeForRetrieveState($cmdState, $cmds, $stateValue);
                        if ($cmdToRun) {

                            $type = strtolower($cmdToRun->getType());
                            $eqType = strtolower($cmdToRun->getEqType());
                            $subType = strtolower($cmdToRun->getSubType());
                            $genericType = strtolower($cmdToRun->getGeneric_type());
                            $subTypeCmdState = strtolower($cmdState->getSubType());

                            $error = true;
                            $complementLog= null;

                            if ($subTypeCmdState == 'binary') {
                                $error = $cmdToRun->execCmd();

                            } else if (in_array($subType, array('slider', 'color'))) {

                                $options = array($subType => $stateValue);
                                $error = $cmdToRun->execCmd($options);
                                $complementLog = sprintf(' avec options eeraee: %s', json_encode($options));

                            } else if ($subType == 'other' && $eqType == 'harmonyhub') {
                                $error = $cmdToRun->execCmd();

                            } else if ($eqType == 'thermostat') {

                                if (count($cmdToRun->getConfiguration) == 0) {
                                    $error = $cmdToRun->execCmd();
                                }

                            } else if ($eqType == 'chauffeeau') {
                                    $error = $cmdToRun->execCmd();
                            } else if ($eqType == 'squeezeboxcontrol') {

                                if (count($cmdToRun->getConfiguration) == 0) {
                                    $error = $cmdToRun->execCmd();
                                }

                            } else if ($subType == 'other' && $eqType == 'alarm') {
                                $error = $cmdToRun->execCmd();

                            } else if (count($cmds) == 1 && $eqType == 'virtual' && $type == 'info') {
                                $error = $cmdToRun->event($stateValue);
                                $complementLog = sprintf('[Event] => %s', $stateValue);

                            } else if ($genericType == 'mode_set_state') {
                                $error = $cmdToRun->execCmd();
                            } else {

                                log::add('instantt', 'debug', sprintf('loadInstantT() - Need help to know how to run commande: %s [%s]', $cmdToRun->getEqType(), $cmdToRun->getName()));
                                log::add('instantt', 'debug', sprintf('loadInstantT() - EqType: %s', $eqType));
                                log::add('instantt', 'debug', sprintf('loadInstantT() - SubType: %s', $subType));
                                log::add('instantt', 'error', sprintf('loadInstantT() - Need help to know how to run commande: %s [%s]', $cmdToRun->getEqType(), $cmdToRun->getName()));
                            }

                            if (!$error) {
                                log::add('instantt', 'debug', sprintf('%s - Execution de la commande: %s%s', $this->getHumanName(), $cmdToRun->getHumanName(), isset($complementLog) ? $complementLog : null));
                            }
                        }
                    }
                }
            }
        }
    }

    private function checkEquipement()
    {
        // interval cannot be less than period
        $triggers = $this->getConfiguration('triggers');
        foreach ($triggers as $trigger) {

            if (is_null($trigger['cmd']) || empty($trigger['cmd'])) {
                throw new Exception(__('Un équipement ou une commande sont invalde', __FILE__));
            }

            $cmd = str_replace(array('#', 'eqLogic'), '', $trigger['cmd']);;
            if (is_numeric($cmd) == false) {
                throw new Exception(__(sprintf('L\'équipement %s n\'est pas valide !', $trigger['cmd']), __FILE__));
            }
        }
    }
}

class instanttCmd extends cmd
{
    /*     * *************************Attributs****************************** */

    /*
      public static $_widgetPossibility = array();
    */

    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS*/
    public function dontRemoveCmd()
    {
        return true;
    }


    // Exécution d'une commande
    public function execute($_options = array())
    {
        log::add('instantt', 'debug', $this->getHumanName() . ' : ExecuteCommande : ' . $this->getLogicalId());

        $instantT = $this->getEqLogic();
        switch ($this->getLogicalId()) {
            case 'make_instantt':
                $instantT->makeInstantT($instantT);
                break;

            case 'load_instantt':
                $instantT->loadInstantT($instantT);
                break;

        }
    }


    /*     * **********************Getteur Setteur*************************** */
}


