<?php

/**
 * This file is part of Schmutzka Framework
 *
 * Copyright (c) 2012 Tomas Votruba (http://tomasvotruba.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Components;

use Schmutzka\Application\UI\Control;
use Schmutzka\Application\UI\Form;
use Nette\Utils\Strings;


class RemindPasswordControl extends Control
{
	/** @inject @var Models\User */
	public $userModel;

	/** @inject @var Nette\Mail\IMailer */
	public $mailer;

	/** @inject @var Schmutzka\Security\UserManager */
	public $userManager;

	/** @inject @var Schmutzka\Mail\IMessage */
	public $message;

	/** @inject @var Schmutzka\ParamService */
	public $paramService;


	public function __construct(Nette\Localization\ITranslator $translator = NULL)
	{
		$this->translator = $translator ?: new RemindPasswordControlCzechTranslator();
	}


	protected function createComponentForm()
	{
		$form = new Form;
		$form->addText('email', 'forms.email')
			->addRule($form::FILLED, 'forms.emailFilledRule')
			->addRule($form::EMAIL, 'forms.emailFormatRule');

		$form->addSubmit('send', 'forms.send')
			->setAttribute('class', 'btn btn-success');

		return $form;
	}


	public function processForm($form)
	{
		$values = $form->values;

		if ($this->userModel->fetch(['email' => $values['email']])) {
			$message = $this->message->create();
			$message->setFrom($this->paramService->email->from)
				->addTo($values['email']);

			$values['new_password'] = $password = Strings::random(10);
			$this->userManager->updatePasswordForUser(['email' => $values['email']], $password);

			$message->addCustomTemplate($this->getMessageUid(), $values);
			$this->mailer->send($message);

			$this->presenter->flashMessage('components.remindPassword.newPasswordSetUp', 'success');

		} else {
			$this->presenter->flashMessage('components.remindPassword.userNotExist', 'danger');
		}

		$this->redirect('this');
	}


	protected function renderAdmin()
	{
		$form = $this['form'];

		$form->id = 'recoverform';
		$form['email']->setAttribute('class', 'form-control')
			->setAttribute('placeholder', 'Zadejte Váš email');
		$form['send']->setAttribute('class', 'btn btn-success');
	}


	/**
	 * @return  string
	 */
	private function getMessageUid()
	{
		return 'remindPassword' . ($this->translator ? '_' . $this->translator->getLocale() : NULL);
	}

}
