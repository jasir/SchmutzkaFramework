<?php

namespace Schmutzka\Application\UI;

use Nette;
use Nette\Utils\Html;
use Schmutzka\Forms\Controls;
use Kdyby\BootstrapFormRenderer\BootstrapRenderer;

class Form extends Nette\Application\UI\Form
{
	/** validators */
	const RC = "Schmutzka\Forms\Rules::validateRC";
	const IC = "Schmutzka\Forms\Rules::validateIC";
	const PHONE = "Schmutzka\Forms\Rules::validatePhone";
	const ZIP = "Schmutzka\Forms\Rules::validateZip";
	const DATE = "Schmutzka\Forms\Rules::validateDate";
	const TIME = "Schmutzka\Forms\Rules::validateTime";
	const EXTENSION = "Schmutzka\Forms\Rules::extension";

	/** @var string */
	public $csrfProtection = "Prosím odešlete formulář znovu, vypršel bezpečnostní token.";

	/** @var bool */
	public $useBootstrap = TRUE;

	/** @var callable */
	protected $processor;

	/** @inject @var Nette\Localization\ITranslator */
	public $translator;

	/** @var bool */
	private $isBuilt = FALSE;

	/** @var string */
	private $id;

	/** @var string */
	private $target;


	/**
	 * BeforeRender build function
	 */
	public function build()
	{
		$this->isBuilt = TRUE;

		if ($this->csrfProtection) {
			$this->addProtection($this->csrfProtection);
		}

		if ($this->id) {
			$this->setId($this->id);
		}

		if ($this->target) {
			$this->setTarget($this->target);
		}
	}


	/**
	 * Custom form render for separate form
	 * @seeks APP_DIR/Forms/{formName}.latte
	 * @seeks LIBS_DIR/Schmutzka/Modules/{moduleName}/Forms/{formName}.latte
	 */
	public function renderTemplate()
	{
		$className = strtr($this->getReflection()->name, array("\\" => "/"));
		$className = strtr($className, array("Forms" => "forms"));
		$files[] = APP_DIR . "/" . lcfirst($className) . ".latte";
		$files[] = LIBS_DIR . "/Schmutzka/Modules/" . $className . ".latte";

		foreach ($files as $file) {
			if (file_exists($file)) {
				$template = $this->createTemplate($file);
				foreach ($this as $key => $value) {
					if (is_array($value) || is_string($value) || is_int($value)) {
						$template->{$key} = $value;
					}
				}

				return $template->render();
			}
		}

		throw new \Exception("$file not found.");
	}


	/**
	 * Changes position of control
	 * @param string
	 * @param string
	 */
	public function moveBefore($name, $where)
	{
		if (!$this->isBuilt) {
			$this->build();
		}

		$component = $this->getComponent($name);
		$this->removeComponent($component);
		$this->addComponent($component, $name, $where);
	}


	/**
	 * Set defaults accepts array, object or empty string
	 * @param array|object
	 * @param bool
	 */
	public function setDefaults($defaults, $erase = FALSE)
	{
		$defaults = is_object($defaults) ? get_object_vars($defaults) : $defaults;
		parent::setDefaults($defaults, $erase);

		return $this;
	}


	/**
	 * Flash message error
	 * @param string
	 */
	public function addError($message)
	{
		$this->valid = FALSE;
		$this->flashMessage($message, "error");
	}


	/**
	 * Will be called when the component becomes attached to a monitored object
	 * @param Nette\Application\IComponent
	 */
	protected function attached($presenter)
	{
		parent::attached($presenter);

		if (!$this->isBuilt) {
			$this->build();
		}

		if (method_exists($this, "afterBuild")) {
			$this->afterBuild();
		}

		if ($this->translator) {
			$this->setTranslator($this->translator);
		}

		if ($presenter instanceof Nette\Application\IPresenter) {
			$this->attachHandlers($presenter);
		}

		if ($presenter->module != "front" && $this->useBootstrap) {
			$this->setRenderer(new BootstrapRenderer($presenter->template));
		}
	}


	/**
	 * Automatically attach methods
	 * @param Nette\Application\UI\Presenter
	 */
	protected function attachHandlers($presenter)
	{
		$formNameSent = "process" . lcfirst($this->getName());

		$possibleMethods = array(
			array($presenter, $formNameSent),
			array($this->parent, $formNameSent),
			array($this, "process"),
			array($this->parent, "process")
		);

		foreach ($possibleMethods as $method) {
			if (method_exists($method[0], $method[1])) {
				$this->onSuccess[] = callback($method[0], $method[1]);
			}
		}
	}


	/**
	 * Returns values as array
	 * @param bool
	 */
	public function getValues($removeEmpty = FALSE)
	{
		$values = parent::getValues(TRUE);

		if ($this->getHttpData()) {
			foreach ($this->getHttpData() as $key => $value) {
				if (isset($this[$key])) {
					if ($this[$key] instanceof Nette\Forms\Controls\SubmitButton) {

					} elseif (empty($values[$key]) && $value && $key != "_token_") {
						$values[$key] = $value;
					}
				}
			}
		}

		if ($this->processor && is_callable($this->processor)) {
			$values = call_user_func($this->processor, $values);

		} elseif (method_exists($this->parent, lcfirst($this->getName()) . "Processor") && is_callable($this->processor)) { // find and use values processor if exists
			$values = call_user_func($this->processor, $values);
		}

		if ($removeEmpty) {
			$values = array_filter($values);
		}

		return $values;
	}


	/**
	 * Get submit control name
	 * @return string
	 */
	public function getSubmitName()
	{
		return $this->isSubmitted()->name;
	}


	/**
	 * Set id for the form
	 * @param string
	 * @return this
	 */
	public function setId($name)
	{
		$this->elementPrototype->id = $name;
		return $this;
	}


	/**
	 * Set target for the form
	 * @param string
	 * @return this
	 */
	public function setTarget($name)
	{
		$this->elementPrototype->target = $name;
		return $this;
	}


	/* ****************************** improved inputs ****************************** */


	/**
	 * @return UploadControl
	 */
	public function addUpload($name, $label = NULL)
	{
		$basePath = $this->getHttpRequest()->url->scriptPath;
		$item = $this[$name] = new Controls\UploadControl($label, $basePath);

		return $item;
	}


	/* ****************************** seperated controls ****************************** */


	/**
	 * @return DatePicker
	 */
	public function addDatePicker($name, $label = NULL, $cols = NULL)
	{
		return $this[$name] = new Controls\DatePicker($label, $cols, NULL);
	}



	/**
	 * @return AntispamControl
	 */
	public function addAntispam($name = "antispam", $label = "Toto pole vymažte.", $msg = "Byl detekován pokus o spam")
	{
		return $this[$name] = new Controls\AntispamControl($label, NULL, NULL, $msg);
	}


	/**
	 * Adds suggest
	 * @param string
	 * @param string
	 * @param array
	 */
	public function addSuggest($name, $label = NULL, $suggestList)
	{
		return $this[$name] = new Controls\SuggestControl($label, $suggestList);
	}


	/**
	 * Adds datepicker time
	 * @param string
	 * @param string
	 */
	public function addDateTimePicker($name, $label = NULL)
	{
		return $this[$name] = new Controls\DateTimePicker($label);
	}


	/********************** helpers **********************/


	/**
	 * Create template
	 * @param string
	 * @return Nette\Templating\FileTemplate
	 */
	public function createTemplate($file = NULL)
	{
		$template = $this->getPresenter()->createTemplate();
		if ($file) {
			$template->setFile($file);
		}

		return $template;
	}


	/**
	 * @param callable
	 * @return self
	 */
	public function setProcessor($processor)
	{
		$this->processor = $processor;
		return $this;
	}

}
