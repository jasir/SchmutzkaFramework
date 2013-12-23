<?php

/**
 * This file is part of Schmutzka Framework
 *
 * Copyright (c) 2012 Tomas Votruba (http://tomasvotruba.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Schmutzka\Application\UI;

use Nette;
use Nette\Utils\Html;
use Nette\Utils\Validators;
use Schmutzka;
use Schmutzka\Forms\Controls;


/**
 * @method setProcessor(callable)
 */
class Form extends Nette\Application\UI\Form
{
	use Schmutzka\Forms\TFileUpload;
	use Schmutzka\Forms\Rendering\TBootstrapRenderer;

	/** validators */
	const DATE = 'Schmutzka\Forms\Rules::validateDate';
	const TIME = 'Schmutzka\Forms\Rules::validateTime';
	const EXTENSION = 'Schmutzka\Forms\Rules::extension';

	/** @var string */
	public $csrfProtection = 'Prosím odešlete formulář znovu, vypršel bezpečnostní token.';

	/** @inject @var Schmutzka\ParamService */
	public $paramService;

	/** @inject @var Models\File */
	public $fileModel;

	/** @var callable */
	protected $processor;

	/** @var bool */
	private $isBuilt = FALSE;


	/**
	 * @param string
	 * @param int
	 */
	public function __set($name, $value)
	{
		if (in_array($name, ['id', 'class', 'target', 'ajax'])) {
			$this->elementPrototype->$name = $value;
		}
	}


	/**
	 * BeforeRender build function
	 */
	public function build()
	{
		$this->isBuilt = TRUE;

		if ($this->csrfProtection) {
			$this->addProtection($this->csrfProtection);
		}
	}


	/**
	 * Changes position of control
	 * @param string
	 * @param string
	 */
	public function moveBefore($name, $where)
	{
		if ( ! $this->isBuilt) {
			$this->build();
		}

		$component = $this->getComponent($name);
		$this->removeComponent($component);
		$this->addComponent($component, $name, $where);
	}


	/**
	 * @param array|object
	 * @param bool
	 * @return  this
	 */
	public function setDefaults($defaults, $erase = FALSE)
	{
		if ($defaults instanceof NotORM_Row) {
			$defaults = $defaults->toArray();
		}

		parent::setDefaults($defaults, $erase);

		return $this;
	}


	/**
	 * @param string
	 */
	public function addError($message)
	{
		$this->presenter->flashMessage($message, 'error');
	}


	/**
	 * @param string
	 * @param string|NULL
	 */
	public function addToggleGroup($id, $label = NULL)
	{
		$fieldset = Html::el('fieldset')->id($id)
			->style('display:none');

		$this->addGroup($label)
			->setOption('container', $fieldset);
	}


	/**
	 * Is called when the component becomes attached to a monitored object
	 * @param Nette\Application\IComponent
	 */
	protected function attached($presenter)
	{
		parent::attached($presenter);

		if (property_exists($presenter, 'translator')) {
			$this->setTranslator($presenter->translator);
		}

		if ( ! $this->isBuilt) {
			$this->build();
		}

		if ($presenter instanceof Nette\Application\IPresenter) {
			$this->attachHandlers($presenter);
			$this->paramService = $presenter->paramService;

			if (property_exists($presenter, 'fileModel')) {
				$this->fileModel = $presenter->fileModel;
			}
		}

		if (($presenter->module == 'front' && isset($presenter->paramService->useBootstrapFront)) || isset($this->presenter->paramService->useBootstrap)) {
			$form = $this;
			$this->setupBootstrapRenderer($form);
		}
	}


	/**
	 * Automatically attach methods
	 * @param Nette\Application\UI\Presenter
	 */
	protected function attachHandlers($presenter)
	{
		$processMethodName = 'process' . lcfirst($this->getName());

		if (method_exists($this->parent, $processMethodName)) {
			$this->onSuccess[] = callback($this->parent, $processMethodName);
		}
	}


	/**
	 * @param  bool
	 * @return  []|ArrayHash
	 */
	public function getValues($asArray = TRUE)
	{
		$values = parent::getValues($asArray);

		$processorMethod = lcfirst($this->getName()) . 'Processor';
		if (method_exists($this->parent, $processorMethod) && is_callable($this->parent->$processorMethod)) {
			$values = call_user_func($this->parent->$processorMethod, $values);
		}

		$this->processFileUploads($values);

		return $values;
	}


	/**
	 * @return string
	 */
	public function getSubmitName()
	{
		return $this->isSubmitted()->name;
	}


	/**
	 * @param  string
	 * @param  string
	 * @param  bool
	 * @return  Schmutzka\Forms\Controls\UploadControl
	 */
	public function addUpload($name, $label = NULL, $multiple = FALSE)
	{
		return $this[$name] = new Schmutzka\Forms\Controls\UploadControl($label, $multiple);
	}


	/**
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return  Schmutzka\Forms\Controls\AntispamControl
	 */
	public function addAntispam($name = 'antispam', $label = 'Toto pole vymažte.', $msg = 'Byl detekován pokus o spam')
	{
		return $this[$name] = new Schmutzka\Forms\Controls\AntispamControl($label, NULL, NULL, $msg);
	}


	/**
	 * @param string
	 * @param string|NULL
	 * @param array
	 * @return  Controls\SuggestControl
	 */
	public function addSuggest($name, $label = NULL, $suggestList)
	{
		return $this[$name] = new Controls\SuggestControl($label, $suggestList);
	}


	/**
	 * @param  string
	 * @param  string|NULL
	 * @return  Nette\Forms\Controls\TextInput
	 */
	public function addUrl($name, $label = NULL)
	{
		$control = $this[$name] = new Nette\Forms\Controls\TextInput($label);
		$control->addFilter(function ($value) {
			return (Validators::isUrl($value) || $value == NULL) ? $value : 'http://' . $value;
		})->addCondition(Form::FILLED)
			->addRule(Form::URL, 'Opravte adresu odkazu');

		return $control;
	}


	/**
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return  Schmutzka\Forms\Controls\IconSubmitButton
	 */
	public function addIconSubmitButton($name, $label, $iconClass)
	{
		return $this[$name] = new Schmutzka\Forms\Controls\IconSubmitButton($label, $iconClass);
	}


	/**
	 * @param  string
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return  Nette\Forms\Controls\Checkbox
	 */
	public function addConditions($name, $label, $link, $flash = 'Musíte souhlasit s podmínkami')
	{
		$a = Html::el('a')
			->setText($label['link'])
			->target('_blank')
			->href($link);

		$label = Html::el('span')
			->setHtml($label['text'] . $a);

		$control = $this[$name] = new Nette\Forms\Controls\Checkbox($label);
		$control->addRule(Form::FILLED, $flash);

		return $control;
	}

}
