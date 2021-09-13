<?php

class instanttHelper extends eqLogic
{
    public $instanttDb = false;
    public $debug = false;

    public function __construct()
    {
        $this->instanttDb = new instanttDB();
    }

    private function constructCommandeWithOrder(array $order, $cmdsLight)
    {
        $cmds = [];
        foreach ($order as $i) {
            $cmds[] = ['state_value' => $cmdsLight[$i]['instantt_state_value'], 'cmd' => cmd::byId($cmdsLight[$i]['id'])];
        }

        return $cmds;
    }

    public function getCommandsStateForEqLogic_deconzLight($idEqLogic)
    {
        $cmds = $this->instanttDb->ahhh($idEqLogic);

        $order = ['01.state::bri', '01.state::ct', '01.state::xy'];

        return $this->constructCommandeWithOrder($order, $cmds);
    }

    public function getCommandsStateForEqLogic_thermostat($idEqLogic)
    {
        $cmds = $this->instanttDb->ahhh($idEqLogic);

        $look = (isset($cmds['lock_state']) && $cmds['lock_state']['instantt_state_value']) ? 1 : 0;
        $order_Consigne = (isset($cmds['order']) && is_numeric($cmds['order']['instantt_state_value'])) ? 1 : 0;

        $order = ['mode', 'order', 'lock_state'];
        if ($look) {
            $order = ['lock_state', 'mode', 'order'];
        }

        if ($order_Consigne) {
            unset($order['order']);
        }

        return $this->constructCommandeWithOrder($order, $cmds);
    }

    public function getCommandsStateForEqLogic_philipsHue($idEqLogic)
    {
        $cmds = $this->instanttDb->ahhh($idEqLogic);

        $isRainbow = (isset($cmds['rainbow_state']) && $cmds['rainbow_state']['instantt_state_value']) ? 1 : 0;
        $isAlert = (isset($cmds['alert_state']) && $cmds['alert_state']['instantt_state_value']) ? 1 : 0;
        $isTransition = (isset($cmds['transition_state']) && $cmds['transition_state']['instantt_state_value']) ? 1 : 0;
        $color = $cmds['color_state']['instantt_state_value'];
        $luminosity = $cmds['luminosity_state']['instantt_state_value'];

        $order = ['color_temp_state', 'color_state', 'luminosity_state'];
        if ($luminosity == 0) {
            $order = ['luminosity_state'];
        }

        return $this->constructCommandeWithOrder($order, $cmds);
    }

    public function getCommandsStateForEqLogic($instantId, $cmdState, $idEqLogic)
    {
        $instanttDb = new instanttDB();
        $cmds = $instanttDb->getCmdsFromStateEqLogicId($instantId, $idEqLogic);

        $eqType = strtolower($cmdState->getEqType());
        $genericType = strtolower($cmdState->getGeneric_type());

        if ($eqType == 'philipshue') {
            return $this->getCommandsStateForEqLogic_philipsHue($idEqLogic);
        }

        if ($eqType == 'thermostat') {
            return $this->getCommandsStateForEqLogic_thermostat($idEqLogic);
        }

        if ($eqType == 'deconz' && $genericType == 'light_state') {
            return $this->getCommandsStateForEqLogic_deconzLight($idEqLogic);
        }

        $result = array();
        foreach ($cmds as $cmd) {
            $result[] = ['state_value' => $cmd['instantt_state_value'], 'cmd' => cmd::byId($cmd['instantt_state_id'])];
        }

        return $result;
    }

    public function guessCommandeForRetrieveState($cmdState, $cmds, $stateValue)
    {
        $stateName = strtolower($cmdState->getName());
        $stateSubType = strtolower($cmdState->getSubType());
        $eqType = strtolower($cmdState->getEqType());
        $genericType = strtolower($cmdState->getGeneric_type());
        $logicalId = strtolower($cmdState->getLogicalId());
        $elementComparaison = $this->getElementComparaison($cmdState);

        $valueToFind = 'undefined';
        if ($stateName == 'etat' && $stateSubType == 'binary') {
            $valueToFind = ($stateValue == 0) ? 'off' : 'on';
        } else if (substr($elementComparaison, 0, 6) == 'onoff_' && $stateSubType == 'binary') {
            $valueToFind = ($stateValue == 0) ? 'off' : 'on';
        } else if ($eqType == 'harmonyhub') {
            $valueToFind = strtolower($stateValue);
        } else if ($eqType == 'chauffeeau') {
            $valueToFind = strtolower($stateValue);
            if ($valueToFind == 'off') {
                $valueToFind = 'desactiver';
            }
        } else if ($eqType == 'alarm' && $stateName == 'mode') {
            $valueToFind = strtolower($stateValue);
        } else if ($eqType == 'thermostat') {

            if ($elementComparaison == 'lock_state') {
                $valueToFind = ($stateValue == 0) ? 'unlock' : 'lock';
            } else if (in_array($elementComparaison, ['mode', 'order'])) {
                $valueToFind = strtolower($stateValue);
                if ($valueToFind == 'aucun') {
                    $valueToFind = 'off';
                }
            }

        } else if ($genericType == 'mode_state') {
            $valueToFind = strtolower($stateValue);
        } else if ($eqType == 'squeezeboxcontrol') {
            if ($logicalId == 'etat') {
                $valueToFind = (strtolower($stateValue) == 'on') ? 'allumer' : 'eteindre';
            } else if ($logicalId == 'repeatinfo') {
                switch ($stateValue) {
                    case 0:
                        $valueToFind = 'repeter non';
                        break;
                    case 1:
                        $valueToFind = 'repeter morceau';
                        break;
                    case 2:
                        $valueToFind = 'repeter liste';
                        break;
                }
            } else if ($logicalId == 'shuffleinfo') {
                switch ($stateValue) {
                    case 0:
                        $valueToFind = 'aleatoire non';
                        break;
                    case 1:
                        $valueToFind = 'aleatoire par morceau';
                        break;
                    case 2:
                        $valueToFind = 'aleatoire par album';
                        break;
                }
            } else if ($logicalId == 'synced') {
                switch (strtolower($stateValue)) {
                    case 'aucun':
                        $valueToFind = 'desynchroniser';
                        break;
                }
            }
        }

        if (count($cmds) == 1) {
            return cmd::byId($cmds[0]['instantt_cmd_id']);
        }

        if (count($cmds) > 1) {
            foreach ($cmds as $cmd) {

                $cmdName = instanttFunction::removeAccent(strtolower($cmd['instantt_cmd_name']));
                $valueToFind = instanttFunction::removeAccent(strtolower($valueToFind));

                if ($cmdName == $valueToFind) {
                    return cmd::byId($cmd['instantt_cmd_id']);
                }
            }
        }

        log::add('instantt', 'warning', sprintf(
            'Need help for guess command action stateName: %s - logicalId %s - stateSubType: %s - eqType: %s',
            $stateName, $logicalId, $stateSubType, $eqType
        ));

        return false;
    }

    public function getElementComparaison($cmd)
    {
        $name = strtolower($cmd->getName());
        $eqType = $cmd->getEqType();
        $logicalId = strtolower($cmd->getLogicalId());

        $actionToSearch = $logicalId;
        if (empty($actionToSearch)) {
            $actionToSearch = $name;
        }

        // cas pour le pugin eibd qui renvoie une GA
        if (is_a($cmd, 'eibdCmd')) {
            $actionToSearch = $name;
        }

        if ($eqType == 'alarm') {
            if ($logicalId == 'mode') {
                $actionToSearch = 'alarm_set_mode';
            }
        }

        return instanttFunction::formatText($actionToSearch);
    }

    public function getFor_Default($cmd): array
    {
        $cmdName = strtolower($cmd->getName());
        $cmdSubType = strtolower($cmd->getSubType());
        $logicalId = strtolower($cmd->getLogicalId());

        if ($cmdName == 'etat' && $cmdSubType == 'binary') {
            return ['on', 'off'];
        }

        /* pour trouver quand il y a chiffre dedans - par exempe onoff_17*/
        if (substr($logicalId, 0, 6) == 'onoff_') {
            if ($result = preg_match_all('/\d+/', $logicalId, $out)) {
                $number = $out[0][0];
                return ['on_' . $number, 'off_' . $number];
            }
        }

        return [];
    }

    public function getFor_Deconz($stateToSearch, $cmd): array
    {
        $replaceXX = substr($cmd->getLogicalId(), 0, 2);

        switch ($stateToSearch) {
            case 'xx.state::on':
                return [sprintf('%s.on::0', $replaceXX), sprintf('%s.on::1', $replaceXX)];

            case 'xx.state::xy':
                return [sprintf('%s.xy::#color#', $replaceXX)];

            case 'xx.state::bri':
                return [sprintf('%s.bri::#slider#', $replaceXX)];

            case 'xx.state::ct':
                return [sprintf('%s.ct::#slider#', $replaceXX)];
        }

        return [];
    }

    public function getFor_Light($logicalId): array
    {
        switch ($logicalId) {
            case 'luminosity_state':
                return ['luminosity'];

            case 'color_temp_state':
                return ['color_temp'];

            case 'color_state':
                return ['color'];

            case 'rainbow_state':
                return ['rainbow_on', 'rainbow_off'];

            case 'alert_state':
                return ['alert_on', 'alert_off'];

            case 'etat':
                return ['on', 'off'];
        }

        return [];
    }

    public function getFor_HarmonyHub($stateToSearch, $cmd): array
    {
        // pour la liste des activités je renvoie toutes les activités dispo
        if ($stateToSearch == 'activityinfo') {
            $return = array();
            $cmds = cmd::byEqLogicId($cmd->getEqLogic_id(), 'action');
            foreach ($cmds as $availableCmd) {
                $logicalId = instanttFunction::formatText($availableCmd->getLogicalId());
                if ($logicalId != 'refresh') {
                    $return[] = $logicalId;
                }
            }

            return $return;
        }

        return [];
    }

    public function getFor_Mode($stateToSearch, $cmd): array
    {
        if ($stateToSearch == 'currentmode') {
            $return = array();
            $cmds = cmd::byEqLogicId($cmd->getEqLogic_id(), 'action');
            foreach ($cmds as $availableCmd) {
                $logicalId = instanttFunction::formatText($availableCmd->getLogicalId());
                if ($logicalId != 'returnpreviousmode') {
                    $return[] = $logicalId;
                }
            }

            return $return;
        }

        return [];
    }

    public function getFor_Alarm($stateToSearch, $cmd): array
    {
        if ($stateToSearch == 'alarm_set_mode') {
            $return = array();
            $cmds = cmd::byEqLogicId($cmd->getEqLogic_id(), 'action');
            foreach ($cmds as $availableCmd) {
                if (strtolower($availableCmd->getGeneric_type()) == $stateToSearch) {
                    $return[] = instanttFunction::formatText($availableCmd->getName());
                }
            }
            return $return;
        }

        return [];
    }

    public function getFor_Eibd($stateToSearch, $cmd): array
    {
        $subType = $cmd->getSubType();
        $configuration = $cmd->getConfiguration();
        $knxObjectType = $configuration['KnxObjectType'];

        //variation
        if ($subType == 'numeric' && $knxObjectType == '5.001') {
            if ($stateToSearch == 'luminosite etat') {
                return ['luminosite'];
            }
        }

        return [];
    }

    public function getFor_Kodi($stateToSearch, $cmd): array
    {
        if ($stateToSearch == 'volume') {
            return ['volumeset'];
        }

        return [];
    }

    public function getFor_Thermostat($stateToSearch, $cmd): array
    {
        if ($stateToSearch == 'lock_state') {
            $logicalIdToSearch = ['lock', 'unlock'];
        } else if ($stateToSearch == 'mode') {
            $logicalIdToSearch = ['modeaction', 'off'];
        } else if ($stateToSearch == 'order') {
            $logicalIdToSearch = ['thermostat'];
        }

        $return = array();
        if (isset($logicalIdToSearch)) {

            $cmds = cmd::byEqLogicId($cmd->getEqLogic_id(), 'action');
            foreach ($cmds as $cmdAvailable) {
                $logicalId = instanttFunction::formatText($cmdAvailable->getLogicalId());
                if (in_array($logicalId, $logicalIdToSearch)) {
                    $return[] = $logicalId;
                }
            }
        }

        return $return;
    }

    public function getFor_SqueezeboxControl($stateToSearch, $cmd): array
    {
        if ($stateToSearch == 'volume') {
            return ['setvolume'];
        } else if ($stateToSearch == 'etat') {
            return ['on', 'off'];
        } else if ($stateToSearch == 'repeatinfo') {
            return ['repeat', 'repeatall', 'unrepeat'];
        } else if ($stateToSearch == 'shuffleinfo') {
            return ['shuffle', 'shufflealbum', 'unshuffle'];
        } else if ($stateToSearch == 'synced') {
            return ['syncfrom', 'syncto', 'synctoall', 'unsync'];
        }

        return [];
    }

    public function getFor_ChauffeEau($stateToSearch, $cmd): array
    {
        if (strtolower($cmd->getLogicalId()) == 'etatcommut') {

            $return = array();
            $cmds = cmd::byEqLogicId($cmd->getEqLogic_id(), 'action');
            foreach ($cmds as $availableCmd) {
                $return[] = instanttFunction::formatText($availableCmd->getLogicalId());
            }

            return $return;
        }

        return [];
    }

    public function getCommandsInfoFromEqLogic($idEqLogic)
    {
        $cmdEqLogic = eqLogic::byId($idEqLogic);
        $cmdEqLogicType = strtolower($cmdEqLogic->getEqType_Name());
        $cmds = cmd::byEqLogicId($idEqLogic, 'info');

        $logicalIdNotAllowed = instanttFunction::mergeArrayKey(array(
            'philipsHue' => ['isreachable', 'transition_state'],
            'MerossIOT' => ['conso_totale', 'tension', 'current', 'power'],
            'plugin_Mode' => ['previousmode'],
            'squeezeboxcontrol' => ['album', 'artist', 'etatbinary', 'etatbinary2'],
            'kodi' => [
                'year', 'nextsong', 'genre_media', 'pinginfo', 'endtime_media', 'longueur', 'position',
                'plot', 'status', 'status_media', 'status_id', 'thumbnail', 'titre', 'type_media'
            ],
            'groupe' => ['last', 'statuson', 'statusoff'],
            'thermostat' => ['temperature', 'temperature_outdoor', 'actif'],
            'chauffeEau' => ['consigne', 'powertime', 'nextstart', 'nextstop', 'tempactuel', 'bacteryprotect', 'etat', 'state'],
            'deconz' => ['01-1000.state::buttonevent']
        ));

        $uniteNotAllowed = instanttFunction::mergeArrayKey(array(
            'various' => ['ma']
        ));

        $genericTypeNotAllowed = instanttFunction::mergeArrayKey(array(
            'deconz' => ['opening', 'temperature', 'generic_infooo']
        ));


        $cmdAllowed = array();
        foreach ($cmds as $cmd) {

            $elementComparaison = instanttFunction::formatText($cmd->getLogicalId());
            if (in_array($elementComparaison, $logicalIdNotAllowed) == false) {

                $elementComparaison = instanttFunction::formatText($cmd->getGeneric_Type());
                if (empty($elementComparaison) || in_array($elementComparaison, $genericTypeNotAllowed) == false) {

                    $elementComparaison = instanttFunction::formatText($cmd->getUnite());
                    if (empty($elementComparaison) || in_array($elementComparaison, $uniteNotAllowed) == false) {
                        $cmdAllowed[] = $cmd;
                    }
                }
            }
        }

        return $cmdAllowed;
    }

    public function getCommandsActions($cmd)
    {
        $type = strtolower($cmd->getType());
        $eqLogicId = $cmd->getEqLogic_id();
        $eqType = strtolower($cmd->getEqType());
        $genericType = strtolower($cmd->getGeneric_type());
        $stateToSearch = $this->getElementComparaison($cmd);

        $tabLogicalIdToSearch = [];

        if ($eqType == 'eibd') {
            $tabLogicalIdToSearch = $this->getFor_Eibd($stateToSearch, $cmd);
        } else if ($eqType == 'kodi') {
            $tabLogicalIdToSearch = $this->getFor_Kodi($stateToSearch, $cmd);
        } else if ($eqType == 'squeezeboxcontrol') {
            $tabLogicalIdToSearch = $this->getFor_SqueezeboxControl($stateToSearch, $cmd);
        } else if ($eqType == 'thermostat') {
            $tabLogicalIdToSearch = $this->getFor_Thermostat($stateToSearch, $cmd);
        } else if ($eqType == 'chauffeeau') {
            $tabLogicalIdToSearch = $this->getFor_ChauffeEau($stateToSearch, $cmd);
        }

        if (count($tabLogicalIdToSearch) == 0) {

            // pour le cas de deconz et par exemple : 01.state::on
            if (is_numeric(substr($stateToSearch, 0, 2))) {
                $stateToSearch = 'xx' . substr($stateToSearch, 2, strlen($stateToSearch));
            }

            switch ($stateToSearch) {
                case 'color_state':
                case 'color_temp_state':
                case 'luminosity_state':
                case 'rainbow_state':
                case 'alert_state':
                case 'transition_state':
                    $tabLogicalIdToSearch = $this->getFor_Light($stateToSearch, $cmd);
                    break;

                case 'xx.state::on':
                case 'xx.state::xy':
                case 'xx.state::bri':
                case 'xx.state::ct':
                    $tabLogicalIdToSearch = $this->getFor_Deconz($stateToSearch, $cmd);
                    break;

                case 'activityinfo':
                    $tabLogicalIdToSearch = $this->getFor_HarmonyHub($stateToSearch, $cmd);
                    break;

                case 'currentmode':
                    $tabLogicalIdToSearch = $this->getFor_Mode($stateToSearch, $cmd);
                    break;

                case 'alarm_set_mode':
                    $tabLogicalIdToSearch = $this->getFor_Alarm($stateToSearch, $cmd);
                    break;

                default:
                    $tabLogicalIdToSearch = $this->getFor_Default($cmd);
                    break;

            }
        }

        $tabCommands = array();

        if ($type == 'info' && $eqType == 'virtual') {
            // command event
            $tabCommands[] = [
                'id' => $cmd->getId(),
                'name' => strtolower($cmd->getName())
            ];
        }

        if (is_array($tabLogicalIdToSearch) && count($tabLogicalIdToSearch) > 0) {

            $cmds = cmd::byEqLogicId($eqLogicId, 'action');
            foreach ($cmds as $availableCmd) {

                $elementCompare = $this->getElementComparaison($availableCmd);
                if (in_array($elementCompare, $tabLogicalIdToSearch)) {
                    $tabCommands[] = array(
                        'id' => $availableCmd->getId(),
                        'name' => strtolower($availableCmd->getName())
                    );
                }
            }
        }

        return $tabCommands;
    }
}