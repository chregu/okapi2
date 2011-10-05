<?php
/* Licensed under the Apache License, Version 2.0
 * See the LICENSE and NOTICE file for further information
 */

/**
 * Gateway class to the PAM authentication and permissions functions.
 *
 * Example of a config.yml entry:
 *
 * \code
 * pam:
 *     auth:
 *         class: pearwrapper
 *         options:
 *             container: Array
 *             users:
 *                 local: search4me
 *     perm:
 *         # "everything" is a permission class which always redirects
 *         # to the login page unless the user has a valid session.
 *         # Attention: this class is not part of Okapi but serves as an
 *         # example for the configuration.
 *         class: everything
 *         options:
 *             loginurl: /login/
 * \endcode
 *
 * @config <b>pam</b> (hash): Contains configuration for authentication
 *         and permission.
 * @config <b>pam->auth</b> (hash): Configuration for the authentication.
 * @config <b>pam->auth->class</b> (string): Class to use for
 *         authentication. "api_pam_auth_" is prepended to the string
 *         to get a class name to load.
 * @config <b>pam->auth->options</b> (hash): Options for the authentication
 *         object. This has is passed as argument to the constructor of the
 *         authentication object.
 * @config <b>pam->perm</b> (hash): Configuration for the permission.
 * @config <b>pam->perm->class</b> (string): Class to use for
 *         permission. "api_pam_perm_" is prepended to the string
 *         to get a class name to load.
 * @config <b>pam->perm->options</b> (hash): Options for the permission
 *         object. This has is passed as argument to the constructor of
 *         the permission object.
 *
 * @author   Silvan Zurbruegg
 *
 * @dispatches pam.logged_in successful user login
 * @dispatches pam.logged_out successful user logout
 */
class api_pam {
    /**
     * api_pam: Instance returned by getInstance()
     * @var api_pam
     */
    protected static $_instance;

    /**
     * auth instance
     * @var api_pam_Iauth
     */
    protected $auth;

    /**
     * perm instance
     * @var api_pam_Iperm
     */
    protected $perm;

    /**
     * event dispatcher instance
     * @var sfEventDispatcher
     */
    protected $dispatcher;

    /** array: Configuration of the authentication part. */
    protected $authConf = array();

    /** array: Configuration of the permission part. */
    protected $permConf = array();

    /** string constant: Key for default settings. */
    protected $confDefaultName = 'default';

    /** string: Authentication scheme in use. */
    protected $authScheme = '';

    /** string: Permission scheme in use. */
    protected $permScheme = '';

    /**
     * Constructor. Loads the PAM configuration.
     */
    public function __construct($dispatcher, $auth, $perm) {
        $this->auth = $auth;
        $this->perm = $perm;
        $this->dispatcher = $dispatcher;
        if ( ! isset(self::$_instance)) {
            self::$_instance = $this;
        }
    }

    /**
     * Login in with the given username and password. Calls the login
     * method on the authentication object. The authentication object
     * is responsible for handling the session state.
     *
     * @param string $user User name
     * @param string $pass Password
     * @param bool $persistent Whether to set a cookie for persistent login or not (aka "Remember me")
     * @return bool Return value of the authentication login method
     * @see api_pam_Iauth::login()
     */
    public function login($user, $pass, $persistent=false) {
        if (($ao = $this->getAuthObj()) !== false) {
            $res = $ao->login($user, $pass, $persistent);
            if ($res) {
                $params = array('user' => $ao->getAuthData(), 'forcedLogin' => false, 'persistent' => $persistent);
                $event = new sfEvent($this, 'pam.logged_in', $params);
                $this->dispatcher->notifyUntil($event);
            }
            return $res;
        }
        return false;
    }

    /**
     * Force the login to the given user id without requiring to know
     * the password or anything
     *
     * @param int $id  User id
     * @param bool $persistent Whether to set a cookie for persistent login or not (aka "Remember me")
     * @return bool Return value of the authentication forceLogin method
     * @see api_pam_Iauth::forceLogin()
     */
    public function forceLogin($id, $persistent=false) {
        if (($ao = $this->getAuthObj()) !== false) {
            $res = $ao->forceLogin($id, $persistent);
            if ($res) {
                $params = array('user' => $ao->getAuthData(), 'forcedLogin' => true, 'persistent' => $persistent);
                $event = new sfEvent($this, 'pam.logged_in', $params);
                $this->dispatcher->notifyUntil($event);
            }
            return $res;
        }
        return false;
    }

    /**
     * Log out the current user. Calls the logout method of the
     * authentication object.
     *
     * @return bool Return value of the authentication logout method
     * @see api_pam_Iauth::logout()
     */
    public function logout() {
        if (($ao = $this->getAuthObj()) !== false) {
            $res = $ao->logout();
            if ($res) {
                $event = new sfEvent($this, 'pam.logged_out', array());
                $this->dispatcher->notifyUntil($event);
            }
            return $res;
        }
        return false;
    }

    /**
     * Forces the session user object to be reloaded from the database
     * in case it changed
     *
     * @return bool success
     */
    public function reload() {
        if (($ao = $this->getAuthObj()) !== false) {
            return $ao->reload();
        }
        return false;
    }

    /**
     * Check if the user is currently logged in. Calls the checkAuth
     * method of the authentication object.
     *
     * @return bool true if the user is logged in.
     * @see api_pam_Iauth::checkAuth()
     */
    public function checkAuth() {
        if (($ao = $this->getAuthObj()) !== false) {
            return $ao->checkAuth();
        }
        return false;
    }

    /**
     * Gets the ID of the currently logged in user. Calls the getUserId()
     * method of the authentication object.
     *
     * @return mixed User ID. Variable type depends on authentication class.
     * @see api_pam_Iauth::getUserId()
     */
    public function getUserId() {
        if (($ao = $this->getAuthObj()) !== false) {
            return $ao->getUserId();
        }
        return 0;
    }

    /**
     * Sets a new password on an arbitrary user
     *
     * @param int $id user id to alter
     * @param string $password new user password
     * @return bool success
     */
    public function setPassword($userid, $password) {
        if (($ao = $this->getAuthObj()) !== false) {
            return $ao->setPassword($userid, $password);
        }
        return false;
    }

    /**
     * Gets the user name of the currently logged in user. Calls the
     * getUserName() method of the authentication object.
     *
     * @return string User name
     * @see api_pam_Iauth::getUserName()
     */
    public function getUserName() {
        if (($ao = $this->getAuthObj()) !== false) {
            return $ao->getUserName();
        }
        return "";
    }

    /**
     * Gets the additional meta information about the currently logged in
     * user. Calls the getAuthData() method of the authentication object.
     *
     * @param string $attribute an optional attribute value
     * @return array|mixed Information key/value pair or only one value if
     * $attribute is given
     * @see api_pam_Iauth::getAuthData()
     */
    public function getAuthData($attribute = null) {
        if (($ao = $this->getAuthObj()) !== false) {
            return $ao->getAuthData($attribute);
        }
        return null;
    }

    /**
     * Checks if the logged in user has access to the given object.
     * Calls isAllowed() of the permission object.
     *
     * @param string $acObject Access control object. An arbitrary value
     *        can be passed in, which the permission class uses to determine
     *        if the user has access or not.
     * @param string $acValueAccess control value. Used in the same way as
     *        the $acObject param.
     * @param int $uid user id, if nothing is passed the current user id
     *        will be used
     * @return bool True if the user is allowed to access the object or no
     *        perm container has been defined in the configuration
     * @see api_pam_Iperm::isAllowed()
     */
    public function isAllowed($acObject, $acValue, $uid = null) {
        if (($po = $this->getPermObj()) !== false) {
            if ($uid === null) {
                $uid = $this->getUserId();
            }
            return $po->isAllowed($uid, $acObject, $acValue);
        }
        return true;
    }

    /**
     * Set an authentication scheme to use. This makes it possible to
     * run more than one different ways of authentication inside the
     * same application.
     *
     * To specify more than the default authentication scheme in the
     * configuration, use an array:
     *
     * \code
     * pam:
     *     auth:
     *         -
     *             name: default
     *             class: lclpearwrapper
     *             # ......
     *         -
     *             name: other
     *             class: lclpearwrapper
     *             # ......
     * \endcode
     *
     * This defines two schemes "default" and "other". To use
     * the other scheme, set it like this:
     *
     * \code
     * $pam->setAuthScheme('other');
     * \endcode
     *
     * @param string $schemeName Name of the scheme to use.
     * @return bool True if the given scheme exists.
     */
    public function setAuthScheme($schemeName) {
        if (isset($this->authConf[$schemeName]) || $schemeName == $this->confDefaultName) {
            $this->authScheme = $schemeName;
            return true;
        }

        return false;
    }

    /**
     * Returns the name of the currently active authentication
     * scheme. See api_pam::setAuthScheme() for details about
     * schemes.
     *
     * @return string Current authentication scheme.
     */
    public function getAuthScheme() {
        return (empty($this->authScheme)) ? $this->confDefaultName : $this->authScheme;
    }

    /**
     * Set a permission scheme to use. This works exactly the same way
     * as authentication schemes, documented under api_pam::setAuthScheme().
     *
     * @param string $schemeName Name of the scheme to use.
     * @return bool true if the given scheme exists.
     */
    public function setPermScheme($schemeName) {
        if (isset($this->permConf[$schemeName]) || $schemeName == $this->permConfDefault) {
            $this->permScheme = $schemeName;
            return true;
        }

        return false;
    }

    /**
     * Returns the name of the currently active permission
     * scheme. See api_pam::setAuthScheme() for details about
     * schemes.
     *
     * @return string Current permission scheme.
     */
    public function getPermScheme() {
        return (empty($this->permScheme)) ? $this->confDefaultName : $this->permScheme;
    }

    /**
     * Returns the current permission object.
     *
     * @return api_pam_Iperm Permission object.
     */
    protected function getPermObj() {
        return $this->perm;
    }

    /**
     * Returns the current authentication object.
     *
     * @return api_pam_Iauth Authentication object.
     */
    protected function getAuthObj() {
        return $this->auth;
    }
    
    public static function getInstance()
    {
//        if ( ! isset(self::$_instance)) {
//            self::$_instance = new self();
//        }
        return self::$_instance;
    }
}
