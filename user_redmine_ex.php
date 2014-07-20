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


class OC_User_Redmine_ex extends OC_User_Backend {

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
	    $this->rm_db->exec('SET NAMES utf8');
            // Connection established:
            $this->rm_db_connected = true;
        } catch (PDOException $e) {
            // Was not able to connect to database. Create an error-message for log:
            OC_Log::write('OC_User_Redmine_ex',
                'OC_User_Redmine_ex, Failed to connect to redmine database: ' . $e->getMessage(),
                OC_Log::ERROR);
        }

        return false;
    }

    function __destructor() {
        // End database connection?
        return false;
    }

    /**
     * @brief Set email address
     * @param $uid The username
     */
    private function setEmail($uid) {
        $sql = 'SELECT mail FROM users WHERE login = :uid';
        $sth = $this->rm_db->prepare($sql);
        if ($sth->execute(array(':uid' => $uid))) {
            $row = $sth->fetch();

            if ($row) {
                if (OC_Preferences::setValue($uid, 'settings', 'email', $row['mail'])) {
                    return true;
                }
            }
        }
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
     * @brief Check if the password is correct
     * @param $uid The username
     * @param $password The password
     * @returns string
     *
     * Check if the password is correct without logging in the user
     * returns the user id or false
     */
    public function checkPassword($uid, $password) {
        if(!$this->rm_db_connected) {
            return false;
        }
        
        $sql = 'SELECT login FROM users WHERE login = :uid';
        $sql .= ' AND hashed_password = SHA1(CONCAT(salt, SHA1(:password)))';
        $sql .= ' AND status = 1';
        $sth = $this->rm_db->prepare($sql);
        if ($sth->execute(array(':uid' => $uid, ':password' => $password))) {
            $row = $sth->fetch();

            if ($row) {
                $this->setEmail($uid);
                return $row['login'];
            }
        }
        return false;
    }

    /**
     * @brief Get a list of all users
     * @returns array with all uids
     *
     * Get a list of all users.
     */
    public function getUsers($search = '', $limit = null, $offset = null) {
        if(!$this->rm_db_connected) {
            return array();
        }

        $users = array();
        $offset = (int)$offset;
        $limit = (int)$limit;

        // All users or only active users?
        $sql = 'SELECT login FROM users WHERE status <= 3';
        $sql .= ' AND type = \'User\'';
        if (!empty($search)) {
            $sql .= " AND login LIKE :search";
        }
        $sql .= ' ORDER BY login';
        if ($limit) {
            $sql .= ' LIMIT :offset,:limit';
        }

        $sth = $this->rm_db->prepare($sql);
        if (!empty($search)) {
            $searchterm = '%'.$search.'%';
            $sth->bindParam(':search', $searchterm, PDO::PARAM_STR);
        }
        if ($limit) {
            $sth->bindParam(':offset', $offset, PDO::PARAM_INT);
            $sth->bindParam(':limit', $limit, PDO::PARAM_INT);
        }

        if ($sth->execute()) {
            while ($row = $sth->fetch()) {
                $users[] = $row['login'];
            }
        }
        return $users;
    }

    /**
     * @brief check if a user exists
     * @param string $uid the username
     * @return boolean
     */
    public function userExists($uid) {
        if(!$this->rm_db_connected) {
            return false;
        }

        $sql = 'SELECT login FROM users WHERE login = :uid';
        $sth = $this->rm_db->prepare($sql);
        if ($sth->execute(array(':uid' => $uid))) {
            $row = $sth->fetch();

            return !empty($row);
        }
        return false;
    }

    /**
     * @brief get the user's home directory
     * @param string $uid the username
     * @return boolean
     *//*
    public function getHome($uid) {
        if(!$this->rm_db_connected) {
            return false;
        }
        
        return false;
    }*/

    /**
     * @brief get display name of the user
     * @param $uid user ID of the user
     * @return display name
     */
    public function getDisplayName($uid) {
        if(!$this->rm_db_connected) {
            return false;
        }
        $sql = 'SELECT firstname, lastname FROM users WHERE login = :uid';
        $sth = $this->rm_db->prepare($sql);
        if ($sth->execute(array(':uid' => $uid))) {
            $row = $sth->fetch();

            return $row['firstname']. ' ' .$row['lastname'];
        }

        return false;
    }

    /**
     * @brief Get a list of all display names
     * @returns array with  all displayNames (value) and the corresponding uids (key)
     *
     * Get a list of all display names and user ids.
     */
    public function getDisplayNames($search = '', $limit = null, $offset = null) {
        if(!$this->rm_db_connected) {
            return array();
        }

        $displayNames = array();
        $offset = (int)$offset;
        $limit = (int)$limit;

        // All users or only active users?
        $sql = 'SELECT login, firstname, lastname FROM users WHERE status <= 3';
        $sql .= ' AND type = \'User\'';
        if (!empty($search)) {
            $sql .= ' AND CONCAT(firstname, \' \', lastname) LIKE :search';
        }
        $sql .= ' ORDER BY CONCAT(firstname, \' \', lastname)';
        if ($limit) {
            $sql .= ' LIMIT :offset,:limit';
        }

        $sth = $this->rm_db->prepare($sql);
        if (!empty($search)) {
            $searchterm = '%'.$search.'%';
            $sth->bindParam(':search', $searchterm, PDO::PARAM_STR);
        }
        if ($limit) {
            $sth->bindParam(':offset', $offset, PDO::PARAM_INT);
            $sth->bindParam(':limit', $limit, PDO::PARAM_INT);
        }

        if ($sth->execute()) {
            while ($row = $sth->fetch()) {
                $displayNames[$row['login']] = $row['firstname'].' '.$row['lastname'];
            }
        }
        return $displayNames;
    }

    /**
     * @brief Check if a user list is available or not
     * @return boolean if users can be listed or not
     */
    public function hasUserListings() {
        if(!$this->rm_db_connected) {
            return false;
        }

        return true;
    }

    // Functions that change the Redmine database. 
    // Not supported now.

    /**
     * @brief Create a new user
     * @param $uid The username of the user to create
     * @param $password The password of the new user
     * @returns true/false
     *
     * Creates a new user. Basic checking of username is done in OC_User
     * itself, not in its subclasses.
     *//*
    public function createUser($uid, $password) {
        if(!$this->rm_db_connected) {
            return false;
        }
        
        return false;
    }*/

    /**
     * @brief Set password
     * @param $uid The username
     * @param $password The new password
     * @returns true/false
     *
     * Change the password of a user
     *//*
    public function setPassword($uid, $password) {
        if(!$this->rm_db_connected) {
            return false;
        }
        
        return false;
    }*/

    /**
     * @brief delete a user
     * @param $uid The username of the user to delete
     * @returns true/false
     *
     * Deletes a user
     *//*
    public function deleteUser( $uid ) {
        if(!$this->rm_db_connected) {
            return false;
        }
        
        return false;
    }*/

    /**
     * @brief Set display name
     * @param $uid The username
     * @param $displayName The new display name
     * @returns true/false
     *
     * Change the display name of a user
     *//*
    public function setDisplayName( $uid, $displayName ) {
        if(!$this->rm_db_connected) {
            return false;
        }
        
        return false;
    }*/
}
