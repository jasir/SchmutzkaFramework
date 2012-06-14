<?php

namespace Webloader\Filter;

/**
 * Less CSS filter
 *
 * @author Jan Marek
 * @license MIT
 */
class LessFilter
{

	private $lc;

	/**
	 * @return \lessc
	 */
	private function getLessC()
	{
		// lazy loading
		if (empty($this->lc)) {
			$this->lc = new \lessc();
		}

		return $this->lc;
	}

	/**
	 * Invoke filter
	 * @param string $code
	 * @param \WebLoader\Compiler $loader
	 * @param string $file
	 * @return string
	 */
	public function __invoke($code, \WebLoader\Compiler $loader, $file)
	{
		return $this->getLessC()->parse($code);
	}

}