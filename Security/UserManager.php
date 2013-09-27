<?php

namespace Schmutzka\Security;

use Nette;
use Nette\Security\AuthenticationException as AE;
use Nette\Utils\Strings;
use NotORM;


class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{
	/** @inject @var Schmutzka\Models\User */
	public $userModel;

	/** @inject @var Schmutzka\ParamService */
	public $paramService;


	/**
	 * Performs an authentication
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($login, $password) = $credentials;

		if (isset($this->paramService->loginColumn)) {
			$loginColumn = $this->paramService->loginColumn;

		} else {
			$loginColumn = strpos($login, '@') ? 'email' : 'login';
		}

		$row = $this->userModel->fetch([$loginColumn => $login]);

		if (!$row) {
			throw new AE("Uživatel '$login' neexistuje.", self::IDENTITY_NOT_FOUND);
		}
		
		if ($row['password'] !== $this->calculateHash($password, $row['salt'])) {
			throw new AE('Chybné heslo.', self::INVALID_CREDENTIAL);
		}

		unset($row['password']);
		return new Nette\Security\Identity($row['id'], $row['role'], $row);
	}


	/**
	 * Computes salted password hash
	 * @param  string
	 * @return string
	 */
	public static function calculateHash($password, $salt = NULL)
	{
		if ($password === Strings::upper($password)) { // perhaps caps lock is on
			$password = Strings::lower($password);
		}
		return crypt($password, $salt ?: '$2a$07$' . Strings::random(22));
	}


	/**
	 * Register user
	 * @param array $values user data
	 * @return  array
	 * @throws \Exception
	 */
	public function register($values)
	{
		if (isset($values['login'])) {
			if ($this->userModel->item(['login' => $values['login']])) {
				throw new \Exception('Toto jméno je již registrováno, zadejte jiné.');
			}
		}

		if ($this->userModel->item(['email' => $values['email']])) {
			throw new \Exception('Tento email je již registrován, zadejte jiný.');
		}

		$values['salt'] = Strings::random(22);
		$values['password'] = self::calculateHash($values['password'], $values['salt']);
		$values['created'] = new Nette\DateTime;

		$userId = $this->userModel->insert($values);

		return $this->userModel->item($userId);
	}


	/**
	 * Update user data
	 * @param  array $values user data
	 * @param int $id user id
	 * @throws  \Exception
	 */
	public function update($values, $id)
	{
		if ($this->userModel->item(array('login' => $values['login'], 'id != %i' => $id))) {
			throw new \Exception('Toto jméno je již registrováno, zadejte jiné.');
		}

		if ($this->userModel->item(array('email' => $values['email'], 'id != %i' => $id))) {
			throw new \Exception('Tento email je již registrován, zadejte jiný.');
		}

		if ($values['password']) {
			$this->updatePasswordForUser($id, $values['password']);
		}

		unset($values['password']);

		$this->userModel->update($values, $id);
	}


	/**
	 * Create hashed password and salt and update for specific user.
	 * (Note: this is an update helper.)
	 *
	 * @param array $cond
	 * @param string $password
	 */
	public function updatePasswordForUser($cond, $password)
	{
		$salt = Strings::random(22);
		$password = self::calculateHash($password, $salt);

		$user = array(
			'salt' => $salt,
			'password' => $password
		);

		$this->userModel->update($user, $cond);
	}

}
