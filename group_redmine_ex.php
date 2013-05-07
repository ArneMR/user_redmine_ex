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


class OC_Group_Redmine_ex extends OC_Group_Backend {

    protected $rm_db;
    protected $rm_db_connected;

    function __construct() {
        $this->rm_db_connected = false;

        // Fetch the plug-in-settings from the admin-settings:
        $rm_db_host     = OC_Appconfig::getValue('user_redmine_ex', 'redmine_ex_db_host','');
        $rm_db_name     = OC_Appconfig::getValue('user_redmine_ex', 'redmine_ex_db_name','');
        $rm_db_driver   = OC_Appconfig::getValue('user_redmine_ex', 'redmine_ex_db_driver', 'mysql');
        $rm_db_user     = OC_Appconfig::getValue('user_redmine_ex', 'redmine_ex_db_user','');
        $rm_db_password = OC_Appconfig::getValue('user_redmine_ex', 'redmine_ex_db_password','');
        $rm_db_port     = OC_Appconfig::getValue('user_redmine_ex', 'redmine_ex_db_port','');
    
        // Prepare the connection request:
        $dsn = "${rm_db_driver}:host=${rm_db_host};port=${rm_db_port};dbname=${rm_db_name}";

        // Check if there are database-settings set:
        if($rm_db_name == '') {
            return false;
        }

        // Connect with database:
        try {
            $this->rm_db = new PDO($dsn, $rm_db_user, $rm_db_password);
            // Connection established:
            $this->rm_db_connected = true;
        } catch (PDOException $e) {
            // Was not able to connect to database. Create an error-message for log:
            OC_Log::write('OC_Group_Redmine_ex',
                'OC_Group_Redmine_ex, Failed to connect to redmine database: ' . $e->getMessage(),
                OC_Log::ERROR);
        }

        return false;
    }

    function __destructor() {
        // End database connection?
        return false;
    }

    /**
     * @brief Return the internal Redmine-ID.
     * @param $uid User id
     * @return false if not found. Int if the id is found.
     */
    private function rmUserId($uid) {
        $sql = 'SELECT id FROM users WHERE login = :uid';
        $sth = $this->rm_db->prepare($sql);

        if ($sth->execute(array(':uid' => $uid))) {
            $row = $sth->fetch();

            if ($row) {
                return $row['id'];
            }
        }
        return false;
    }

    /**
     * @brief Return the internal Redmine-ID.
     * @param $gid Group id
     * @return false if not found. Int if the id is found.
     */
    private function rmGroupId($gid) {
        $sql = 'SELECT id FROM users WHERE lastname = :gid';
        $sth = $this->rm_db->prepare($sql);

        if ($sth->execute(array(':gid' => $gid))) {
            $row = $sth->fetch();

            if ($row) {
                return $row['id'];
            }
        }
        return false;
    }

    /**
     * @brief get a list of all users in a group
     * @param string $gid
     * @param string $search
     * @param int $limit
     * @param int $offset
     * @return array with user ids
     */
    public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
        if(!$this->rm_db_connected) {
            return array();
        }

        $users = array();
        $offset = (int)$offset;
        $limit = (int)$limit;

        // All users or only active users?
        $sql = 'SELECT login FROM users JOIN groups_users ON (id = user_id)';
        $sql .= ' WHERE group_id = (SELECT id FROM users WHERE lastname = :gid AND type = \'Group\')';
        if (!empty($search)) {
            $sql .= ' AND login LIKE :search';
        }
        $sql .= ' ORDER BY login';
        if ($limit > 0) {
            $sql .= ' LIMIT :offset,:limit';
        }

        $sth = $this->rm_db->prepare($sql);
        if (!empty($search)) {
            $searchterm = '%'.$search.'%';
            $sth->bindParam(':search', $searchterm, PDO::PARAM_STR);
        }
        if ($limit > 0) {
            $sth->bindParam(':offset', $offset, PDO::PARAM_INT);
            $sth->bindParam(':limit', $limit, PDO::PARAM_INT);
        }

        if ($sth->execute(array(':gid' => $gid))) {
            while ($row = $sth->fetch()) {
                $users[] = $row['login'];
            }
        }
        return $users;
    }
    
    /**
     * @brief is user in group?
     * @param string $uid uid of the user
     * @param string $gid gid of the group
     * @return bool
     *
     * Checks whether the user is member of a group or not.
     */
    public function inGroup($uid, $gid) {
        if(!$this->rm_db_connected) {
            return false;
        }
        
        // SQL query:
        $sql = 'SELECT lastname FROM users JOIN groups_users ON (id = group_id)';
        $sql .= ' WHERE user_id = (SELECT id FROM users WHERE login = :uid)';
        $sql .= ' AND lastname = :gid';
        
        // Do query:
        $sth = $this->rm_db->prepare($sql);
        if ($sth->execute(array(':gid' => $gid, ':uid' => $uid))) {
            $row = $sth->fetch();

            // Has the first row one element, then the user is in the group.
            return !empty($row);
        }
        return false;
    }

    /**
     * @brief Get all groups a user belongs to
     * @param string $uid Name of the user
     * @return array with group names
     *
     * This function fetches all groups a user belongs to. It does not check
     * if the user exists at all.
     */
    public function getUserGroups($uid) {
        if(!$this->rm_db_connected) {
            return array();
        }
        
        // Target array:
        $groups = array();

        // SQL query:
        $sql = 'SELECT lastname FROM users JOIN groups_users ON (id = group_id)';
        $sql .= ' WHERE user_id = (SELECT id FROM users WHERE login = :uid)';
        $sql .= ' ORDER BY lastname';
        
        // Do query:
        $sth = $this->rm_db->prepare($sql);
        if ($sth->execute(array(':uid' => $uid))) {
            while($row = $sth->fetch()) {
                $groups[] = $row['lastname'];
            }
        }
        
        return $groups;
    }

    /**
     * @brief get a list of all groups
     * @param string $search
     * @param int $limit
     * @param int $offset
     * @return array with group names
     *
     * Returns a list with all groups
     */
    public function getGroups($search = '', $limit = -1, $offset = 0) {
        if(!$this->rm_db_connected) {
            return array();
        }

        $groups = array();
        $offset = (int)$offset;
        $limit = (int)$limit;

        $sql = 'SELECT lastname FROM users';
        $sql .= ' WHERE type = \'Group\'';
        if (!empty($search)) {
            $sql .= ' AND login LIKE :search';
        }
        $sql .= ' ORDER BY login';
        if ($limit > 0) {
            $sql .= ' LIMIT :offset,:limit';
        }

        $sth = $this->rm_db->prepare($sql);
        if (!empty($search)) {
            $searchterm = '%'.$search.'%';
            $sth->bindParam(':search', $searchterm, PDO::PARAM_STR);
        }
        if ($limit > 0) {
            $sth->bindParam(':offset', $offset, PDO::PARAM_INT);
            $sth->bindParam(':limit', $limit, PDO::PARAM_INT);
        }

        if ($sth->execute()) {
            while ($row = $sth->fetch()) {
                $groups[] = $row['lastname'];
            }
        }
        return $groups;
    }

    /**
     * check if a group exists
     * @param string $gid
     * @return bool
     */
    public function groupExists($gid) {
        if(!$this->rm_db_connected) {
            return false;
        }
        
        // SQL query:
        $sql = 'SELECT lastname FROM users';
        $sql .= ' WHERE lastname = :gid';
        $sql .= ' AND type = \'Group\'';
        
        // Do query:
        $sth = $this->rm_db->prepare($sql);
        if ($sth->execute(array(':gid' => $gid))) {
            $row = $sth->fetch();

            // Has the first row one element, then the group does exist.
            return !empty($row);
        }
        return false;
    }

    // Functions that change the Redmine database. 
    // Not supported now.

    /**
     * @brief Try to create a new group
     * @param $gid The name of the group to create
     * @returns true/false
     *
     * Trys to create a new group. If the group name already exists, false will
     * be returned.
     */
    public function createGroup($gid) {
        if(!$this->rm_db_connected) {
            return false;
        }
        
        return false;
    }
/* Check first: groupExists($gid)!
INSERT users VALUES () <- You need the highest ID at first!
*/
    
    /**
     * @brief delete a group
     * @param $gid gid of the group to delete
     * @returns true/false
     *
     * Deletes a group and removes it from the group_user-table
     */
    public function deleteGroup($gid) {
        if(!$this->rm_db_connected) {
            return false;
        }
        
        return false;
    }
/* Check first the Redmine database for more dependencies!
DELETE FROM groups_users
WHERE group_id = (SELECT id FROM users
                  WHERE lastname = :gid
                  AND type = \'Group\')

DELETE FROM users
WHERE lastname = :gid
AND type = \'Group\'
*/

    /**
     * @brief Add a user to a group
     * @param $uid Name of the user to add to group
     * @param $gid Name of the group in which add the user
     * @returns true/false
     *
     * Adds a user to a group.
     */
    public function addToGroup($uid, $gid) {
        if(!$this->rm_db_connected) {
            return false;
        }
        
        return false;
    }
/* First check usersInGroup!
INSERT INTO groups_users VALUES ( ,)
*/

    /**
     * @brief Removes a user from a group
     * @param $uid NameUSER of the user to remove from group
     * @param $gid Name of the group from which remove the user
     * @returns true/false
     *
     * removes the user from a group.
     */
    public function removeFromGroup($uid, $gid) {
        if(!$this->rm_db_connected) {
            return false;
        }
        
        return false;
    }
/*
DELETE FROM groups_users
WHERE user_id = (SELECT id FROM users
                 WHERE login = :uid)
AND group_id = (SELECT id FROM users
                WHERE lastname = :gid
                AND type = \'Group\')
*/

}
