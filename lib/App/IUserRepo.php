<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use LogicException;

interface IUserRepo {
    /**
     * @return mixed
     */
    public function saveUser(array $user);

    public function deleteUser(array $user);

    /**
     * @return array|false Returns an array with information about User on success, false otherwise.
     */
    public function findUserByLogin(string $login);

    /**
     * @param string|int $id
     * @throws LogicException if the User with the given ID is not found.
     */
    public function userById($id): array;
}