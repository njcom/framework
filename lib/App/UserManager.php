<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use LogicException;
use Morpho\App\Web\Session;
use Morpho\Base\NotImplementedException;
use RuntimeException;

use function trim;

class UserManager {
    public const LOGIN_NOT_FOUND_ERROR = 'loginNotFound';
    public const PASSWORDS_DONT_MATCH_ERROR = 'passwordsDontMatch';
    public const EMPTY_LOGIN_OR_PASSWORD = 'emptyPassword';
    protected Session $session;
    protected $user;
    protected $repo;

    public function __construct(IUserRepo $repo, Session $session) {
        $this->repo = $repo;
        $this->session = $session;
    }

    public function loggedInUser() {
        if (null !== $this->user) {
            return $this->user;
        }
        if (!isset($this->session->userId)) {
            throw new RuntimeException("The user was not logged in");
        }
        $this->user = $this->registeredUserById($this->session->userId);
        return $this->user;
    }

    private function registeredUserById($id) {
        $registeredUser = $this->repo->userById($id);
        unset($registeredUser['passwordHash']);
        return $registeredUser;
    }

    public function isUserLoggedIn(): bool {
        return !empty($this->session->userId);
    }

    public function isUserRegistered(array $user): bool {
        return (bool) $this->repo->findUserByLogin($user['login']);
    }

    /**
     * Log in into the system by ID without any checks.
     */
    public function logInById($userId) {
        $registeredUser = $this->registeredUserById($userId);
        $this->session->userId = $registeredUser['id'];
        $this->user = $registeredUser;
    }

    /**
     * @return true|array Returns true on success, array with errors otherwise.
     */
    public function logIn(array $user) {
        $login = trim($user['login']);
        $password = trim($user['password']);
        if (empty($login) || empty($password)) {
            return [self::EMPTY_LOGIN_OR_PASSWORD];
        }
        $registeredUser = $this->repo->findUserByLogin($login);
        if (false === $registeredUser) {
            return [self::LOGIN_NOT_FOUND_ERROR];
        }
        if (!PasswordManager::isValidPassword($password, $registeredUser['passwordHash'])) {
            return [self::PASSWORDS_DONT_MATCH_ERROR];
        }
        unset($registeredUser['passwordHash']);
        $this->session->userId = $registeredUser['id'];
        $this->user = $registeredUser;
        return true;
    }

    /**
     * This operation usually must be done in transaction.
     *
     * @return mixed
     */
    public function registerUser(array $user) {
        if ($this->repo->findUserByLogin($user['login'])) {
            throw new LogicException("Such user already exists");
        }
        $user['passwordHash'] = PasswordManager::passwordHash($user['password']);
        $userId = $this->repo->saveUser($user);
        return $userId;
    }

    public function deleteRegisteredUser(array $user) {
        $this->logOut();
        $this->repo->deleteUser($user);
    }

    public function logOut() {
        $this->user = null;
        unset($this->session->userId);
    }

    public function userInGroup(array $group): bool {
        throw new NotImplementedException();
    }
}