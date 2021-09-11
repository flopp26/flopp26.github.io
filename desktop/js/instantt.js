
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



/* Permet la réorganisation des commandes dans l'équipement */
$("#table_cmd").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true
});

/* Fonction permettant l'affichage des commandes dans l'équipement */
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
     var _cmd = {configuration: {}};
   }
   if (!isset(_cmd.configuration)) {
     _cmd.configuration = {};
   }
   var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';

   tr += '<td style="width:60px; display: none;">';
   tr += '<span class="cmdAttr" data-l1key="id"></span>';
   tr += '</td>';

   tr += '<td style="max-width:80px;width:80px;">';
   tr += '<div class="row">';
   tr += '<div class="col-xs-4">';
   tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom de la commande}}">';
   tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="{{Commande information liée}}">';
   tr += '<option value="">{{Aucune}}</option>';
   tr += '</select>';
   tr += '</div>';
   tr += '</div>';
   tr += '</td>';
   tr += '<td style="max-width:80px;width:80px;">';
   tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label>';
   tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label>';
   tr += '</td>';
   tr += '<td style="max-width:80px;width:80px;">';
   if (is_numeric(_cmd.id)) {
     tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
     tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>';
   }
   tr += '</tr>';
   $('#table_cmd tbody').append(tr);
   var tr = $('#table_cmd tbody tr').last();
   jeedom.eqLogic.builSelectCmd({
     id:  $('.eqLogicAttr[data-l1key=id]').value(),
     filter: {type: 'info'},
     error: function (error) {
       $('#div_alert').showAlert({message: error.message, level: 'danger'});
     },
     success: function (result) {
       tr.find('.cmdAttr[data-l1key=value]').append(result);
       tr.setValues(_cmd, '.cmdAttr');
       jeedom.cmd.changeType(tr, init(_cmd.subType));
     }
   });
 }

/********************************************************************************/

/**
 * Presentation et recuperation des donnees
 */
function saveEqLogic(_eqLogic) {

    if (!isset(_eqLogic.configuration)) {
        _eqLogic.configuration = {};
    }
    _eqLogic.configuration.triggers = $('#div_trigger .trigger').getValues('.expressionAttr');
    _eqLogic.configuration.actions  = $('#div_action .action').getValues('.expressionAttr');
    //console.log('triggers');
    //console.dir(_eqLogic.configuration.triggers);
    //console.log('actions');
    //console.dir(_eqLogic.configuration.actions);
    return _eqLogic;
}

function printEqLogic(_eqLogic) {

    actionOptions = [];
    $('#div_trigger').empty();
    $('#div_action').empty();
    if (isset(_eqLogic.configuration)) {
        if (isset(_eqLogic.configuration.triggers)) {
            for (var i in _eqLogic.configuration.triggers) {

                addTrigger(_eqLogic.configuration.triggers[i], 'tt', 'trigger');
            }
        }
        if (isset(_eqLogic.configuration.actions)) {
            for (var i in _eqLogic.configuration.actions) {
                addAction(_eqLogic.configuration.actions[i], 'action');
            }
        }
    }
    jeedom.cmd.displayActionsOption({
        params : actionOptions,
        async : false,
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success : function(data){
            for(var i in data){
                $('#'+data[i].id).append(data[i].html.html);
            }
            taAutosize();
        }
    });

}


/********************************************************************************/


/********************************************************************************/
/*
 * table des triggers
 * function addTrigger
 * function removeTrigger
 */
$('#div_trigger').sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

$('.addTrigger').off('click').on('click', function () {
    addTrigger({}, false, $(this).attr('data-type'));
});


$('body').off('click','.listCmdTrigger').on('click','.listCmdTrigger', function () {
    var type = $(this).attr('data-type');
    var el = $(this).closest('.' + type).find('.expressionAttr[data-l1key=cmd]');
    jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function (result) {
        el.value(result.human);
    });
});

$('body').off('click','.listCmdEqLogic').on('click','.listCmdEqLogic', function () {
    var type = $(this).attr('data-type');
    var el = $(this).closest('.' + type).find('.expressionAttr[data-l1key=cmd]');
    jeedom.eqLogic.getSelectModal({ eqLogic: { eqType_name: 'all' } }, function (result) {
        el.value(result.human);
    });
});

$('body').off('focusout','.cmdTrigger.expressionAttr[data-l1key=cmd]').on('focusout','.cmdTrigger.expressionAttr[data-l1key=cmd]',function (event) {
    var type = $(this).attr('data-type');
    var expression = $(this).closest('.' + type).getValues('.expressionAttr');
    var el = $(this);
});

$('body').off('click','.bt_removeInfo').on('click','.bt_removeInfo',function () {
    var type = $(this).attr('data-type');
    $(this).closest('.' + type).remove();
});

function addTrigger(trigger, stateValue, _type) {

    var div = '<div class="' + _type + '">';
    div += '<div class="form-group ">';
    div += '<div class="col-sm-4">';
    div += '<div class="input-group">';
    div += '<span class="input-group-btn">';
    div += '<a class="btn btn-default bt_removeInfo roundedLeft" data-type="' + _type + '"><i class="fas fa-minus-circle"></i></a>';
    div += '</span>';
    div += '<input readonly="readonly" class="expressionAttr form-control cmdTrigger" data-l1key="cmd" data-type="' + _type + '" />';
    div += '<span class="input-group-btn">';
    div += '<a class="btn btn-default listCmdEqLogic" data-type="' + _type + '"><i class="fas fa-list"></i></a>';
    div += '</span>';
    div += '<span class="input-group-btn">';
    div += '<a class="btn btn-default listCmdTrigger roundedRight" data-type="' + _type + '"><i class="fas fa-list-alt"></i></a>';
    div += '</span>';
    div += '</div>';
    div += '</div>';

    if(stateValue == 'ooooooooo') {
        div += '<label class="col-sm-1 control-label">{{Etat InstantT}}</label>';
        div += '<div class="col-sm-4">';
        div += '<div class="input-group">';
        div += '<input class="expressionAtt form-control cmdTrigge" readonly="readonly" data-l1key="cmd" value="' + stateValue + '" />';
        div += '</div>';
        div += '</div>';
    }


    div += '</div>';

    $('#div_' + _type).append(div);
    $('#div_' + _type + ' .' + _type + '').last().setValues(trigger, '.expressionAttr');
}

/*********************************************************************************************/