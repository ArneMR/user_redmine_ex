<?php

/**
 * ownCloud - user_redmine_ex
 *
 * @author Arne Maximilian Richter
 * @copyright 2013 Arne Maximilian Richter <arne.richter@haw-hamburg.de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

OC_Util::checkAdminUser();
OC_Util::isCallRegistered();

$params = array(
    'redmine_ex_db_host',
    'redmine_ex_db_port',
    'redmine_ex_db_user',
    'redmine_ex_db_password',
    'redmine_ex_db_name',
    'redmine_ex_db_driver'
);

if ($_POST) {
    foreach($params as $param){
        if(isset($_POST[$param])){
            OC_Appconfig::setValue('user_redmine_ex', $param, $_POST[$param]);
        }
    }
}

// fill template
$tmpl = new OC_Template( 'user_redmine_ex', 'settings');
foreach($params as $param){
    $default = '';
    if ($param == 'redmine_ex_db_driver') {
        $default = 'mysql';
    }
    if ($param == 'redmine_ex_db_port') {
        $default = '3306';
    }
    if ($param == 'redmine_ex_db_host') {
        $default = 'localhost';
    }

    $value = OC_Appconfig::getValue('user_redmine_ex', $param, $default);
    $tmpl->assign($param, $value);
}

return $tmpl->fetchPage();
