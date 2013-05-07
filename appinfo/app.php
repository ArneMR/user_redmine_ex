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

require_once('apps/user_redmine_ex/user_redmine_ex.php');
require_once('apps/user_redmine_ex/group_redmine_ex.php');

OCP\App::registerAdmin('user_redmine_ex','settings');

// register user backend
OC_User::useBackend( new OC_User_Redmine_ex() );
OC_Group::useBackend( new OC_Group_Redmine_ex() );

// add settings page to navigation
$entry = array(
    'id'   => 'user_redmine_ex_settings',
    'order'=> 1,
    'href' => OC_Helper::linkTo( "user_redmine_ex", "settings.php" ),
    'name' => 'Redmine_ex'
);
