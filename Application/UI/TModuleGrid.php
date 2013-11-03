<?php

namespace Schmutzka\Application\UI;


trait TModuleGrid
{
	/** @inject @var Schmutzka\ParamService */
	public $paramService;

	/** @inject @var Models\User */
	public $userModel;


	public function attached($presenter)
	{
		$this->build();
		$this->setPrimaryKey('id');
		$this->setDataLoader($this->dataLoader);

		parent::attached($presenter);
		$this->setTemplateFile($this->getTemplatePath());
		$this->addTemplateValue('moduleGridTemplate', $this->paramService->modulesDir . '/templates/moduleGrid.latte');
	}


	/**
	 * @param  int
	 */
	public function deleteRecord($id)
	{
		$this->model->delete($id);
	}


	/**
	 * @param  int
	 */
	public function editRecord($id)
	{
		$this->presenter->redirect('edit', ['id' => $id]);
	}


	/**
	 * @param  self $grid
	 * @param  array  $columns
	 * @param  array  $filters
	 * @param  array  $order
	 * @return NotORM_Result
	 */
	public function dataLoader($grid, array $columns, array $filters, array $order)
	{
		$result = $this->model->fetchAll();
		return $result;
	}


	public function addEditRowAction()
	{
		$this->addRowAction('edit', 'Upravit', $this->editRecord);
	}


	public function addDeleteRowAction()
	{
		$this->addRowAction('delete', 'Smazat', $this->deleteRecord);
	}


	/**
	 * @return  Models\Base
	 */
	public function getModel()
	{
		$className = $this->getReflection()->getName();
		$classNameParts = explode('\\', $className);
		$modelName = lcfirst(substr(array_pop($classNameParts), 0, -4)) . 'Model';

		return $this->{$modelName};
	}


	/**
	 * @return  Nette\ArrayHash
	 */
	protected function getModuleParams()
	{
		return $this->paramService->getModuleParams($this->presenter->module);
	}


	/**
	 * @param string
	 * @return string
	 */
	private function getTemplatePath($name = NULL)
	{
		$class = $this->getReflection();
		return dirname($class->getFileName()) . '/templates/grid.latte';
	}

}