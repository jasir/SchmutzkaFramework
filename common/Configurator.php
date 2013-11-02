<?php

namespace Schmutzka;

use Nette;
use Schmutzka\Utils\Neon;


class Configurator extends Nette\Configurator
{

	/**
	 * @param bool|string|array
	 * @param bool
	 */
	public function __construct($debug = NULL, $autoloadConfig = TRUE)
	{
		parent::__construct();

		$this->parameters = $this->getParameters();

		if ($debug !== NULL) {
			$this->setDebugMode($debug);
		}

		$this->enableDebugger($this->parameters['logDir']);

		// robot loader
		$this->setTempDirectory($this->parameters['appDir'] . '/../temp');
		$this->createRobotLoader()
			->addDirectory($this->parameters['appDir'])
			->addDirectory($this->parameters['libsDir'])
			->register();

		// modules
		$this->registerModules();

		// configs
		$this->addConfig($this->parameters['libsDir'] . '/Schmutzka/configs/default.neon');
		if ($autoloadConfig) {
			$name = $this->parameters['environment'] == 'development' ? 'local' : 'prod';
			$this->loadConfigByName($name);
		}
	}


	/**
	 * @param  array { [ string => string ] }
	 * @param  string
	 */
	public function loadConfigByHost($hostConfigs, $host)
	{
		$configLoaded = FALSE;
		foreach ($hostConfigs as $key => $config) {
			if ($key == $host) {
				$this->addConfig($this->parameters['appDir'] . '/config/' . $config, FALSE);
				$configLoaded = TRUE;
			}
		}

		if ($configLoaded == FALSE) {
			$this->loadConfigByName('local');
		}
	}


	/**
	 * Include paths to directories
	 * @return array
	 */
	private function getParameters()
	{
		$parameters = parent::getDefaultParameters();

		$rootDir = realpath(__DIR__ . '/../../..');
		$parameters['appDir'] = $rootDir . '/app';
		$parameters['libsDir'] =  $rootDir . '/libs';
		$parameters['logDir'] =  $rootDir . '/log';
		$parameters['wwwDir'] =  $rootDir . '/www';
		$parameters['assetsDir'] =  $rootDir . '/libs/Schmutzka/assets';
		$parameters['modulesDir'] =  $rootDir . '/libs/Schmutzka/Modules';

		return $parameters;
	}


	/**
	 * Add configs of active modules
	 */
	private function registerModules()
	{
		$parameters = Neon::fromFile($this->parameters['appDir'] . '/config/config.neon', 'parameters');

		if (isset($parameters['modules'])) {
			$this->addConfig($this->parameters['modulesDir'] . '/AdminModule/config.neon');
			foreach ($parameters['modules'] as $module) {
				$moduleDirConfig = ucfirst($module) . 'Module/config.neon';
				if (file_exists($config = $this->parameters['modulesDir'] . '/' . $moduleDirConfig)) {
					$this->addConfig($config);

				} elseif (file_exists($config = $this->parameters['appDir'] . '/' . $moduleDirConfig)) {
					$this->addConfig($config);
				}
			}
		}
	}


	/**
	 * @param  string
	 */
	private function loadConfigByName($name)
	{
		$file = $this->parameters['appDir'] . '/config/config.' . $name . '.neon';
		if (file_exists($file)) {
			$this->addConfig($file, FALSE);

		} else {
			$this->addConfig($this->parameters['appDir'] . '/config/config.neon', FALSE);
		}
	}

}
