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
include_file('core', 'authentification', 'php');
include_file('desktop', 'configuration.instantt', 'js', 'instantt');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>

      <div class="form-group">
          <label class="col-md-4 control-label">{{Afficher le débug pour les commandes non trouvées}} </label>
          <div class="col-sm-1">
              <input type="checkbox" class="configKey form-control" data-l1key="show-debug-cmds"/>
          </div>
      </div>

      <div class="form-group">
          <label class="col-md-4 control-label">{{Supprimer l'ensemble des commandes actions trouvées}}
              <sup>
                  <i class="fas fa-question-circle floatright"></i>
              </sup>
          </label>
          <div class="col-sm-1">
              <a class="btn btn-danger" id="reinitAllCmds"><i class="fas fa-exclamation-triangle"></i> {{Réinitialiser}}
              </a>
          </div>
      </div>

    </div>
  </fieldset>
</form>
