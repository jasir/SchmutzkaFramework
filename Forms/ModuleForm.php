<?php

namespace Schmutzka\Forms;

use Schmutzka;
use Schmutzka\Application\UI\Form;

class ModuleForm extends Form
{
	/** @persistent */
	public $id;

	/** @inject @var Schmutzka\Security\User */
	public $user;

	/** @inject @var Schmutzka\Config\ParamService */
	public $paramService;

	/** @var string */
	protected $onEditRedirect = "default";

	/** @var bool */
	protected $nullId = "id";


	public function attached($presenter)
	{
		parent::attached($presenter);
		if ($this->id = $presenter->id) {
			$this->addSubmit("cancel", "Zrušit")
				->setValidationScope(FALSE);

			$this->setDefaults($this->model->item($this->id));
		}
	}


	public function afterBuild()
	{
		$this->addSubmit("send", "Uložit")
			->setAttribute("class", "btn btn-primary");
	}


	/**
	 * @param array
	 */
	protected function preProcess($values)
	{
		return $values;
	}


	public function process($form)
	{
		if ($this->id && $form["cancel"]->isSubmittedBy()) {
			$this->redirect("default", array("id" => NULL));
		}

		$values = $form->values;
		$values = $this->preProcess($values);

		if ($this->id) {
			$this->model->update($values, $this->id);

		} else {
			$this->model->insert($values);
		}

		$this->flashMessage("Uloženo.", "success");

		if ($this->nullId) {
			$this->redirect($this->onEditRedirect, array($this->nullId => NULL));

		} else {
			$this->redirect($this->onEditRedirect);
		}
	}


	/**
	 * @return  Nette\ArrayHash
	 */
	public function getModuleParams()
	{
		return $this->paramService->getModuleParams($this->presenter->module);
	}


	/**
	 * @return  *\Model\*
	 */
	public function getModel()
	{
		$className = $this->getReflection()->getName();
		$classNameParts = explode("\\", $className);
		$modelName = lcfirst(substr(array_pop($classNameParts), 0, -4)) . "Model";

		return $this->{$modelName};
	}

}
