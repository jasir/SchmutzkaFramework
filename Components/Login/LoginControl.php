<?php

namespace Components;

use Nette;
use Schmutzka\Application\UI\Form;
use Schmutzka\Application\UI\Control;


/**
 * @method setForgotLink(string)
 * @method getForgotLink()
 */
class LoginControl extends Control
{
	/** @persistent @var string */
	public $backlink;

	/** @inject @var Schmutzka\Security\User */
	public $user;

	/** @inject @var Nette\Http\Session */
	public $session;

	/** @var string */
	private $forgotLink = NULL;

	/** @var string */
	public $loginColumn = 'email';


	public function attached($presenter)
	{
		parent::attached($presenter);
		$this->backlink = $presenter->backlink;
	}


	protected function createComponentForm()
	{
		$form = new Form;

		$form->addText('email', 'Email')
			->addRule(Form::EMAIL, 'Zadejte email');
		$form->addPassword('password', 'Heslo')
			->addRule(Form::FILLED, 'Zadejte heslo');

		$form->addSubmit('send', 'Přihlásit se')
			->setAttribute('class', 'btn btn-primary');

		return $form;
	}


	public function processForm($form)
	{
		try {
			$values = $form->values;
			$this->user->setExpiration('+ 14 days', FALSE);
			$this->user->login($values[$this->loginColumn], $values['password'], $this->loginColumn);

			if ($this->paramService->flashes->onLogin) {
				$this->presenter->flashMessage($this->paramService->flashes->onLogin, 'success');
			}

			$this->presenter->restoreRequest($this->backlink);
			$this->presenter->redirect('Homepage:default');

		} catch (Nette\Security\AuthenticationException $e) {
			$this->presenter->flashMessage($e->getMessage(), 'danger');
		}
	}


	protected function renderDefault()
	{
		if ($this->forgotLink) {
			$this->template->forgotLink = $this->forgotLink;
		}
	}


	protected function renderAdmin()
	{
		$form = $this['form'];
		$form->id = 'loginform';
		$form['email']->setAttribute('class', 'form-control')
			->setAttribute('placeholder', 'Email');
		$form['password']->setAttribute('class', 'form-control')
			->setAttribute('placeholder', 'Password');
		$form['send']->setAttribute('class', 'btn btn-success');
	}

}
