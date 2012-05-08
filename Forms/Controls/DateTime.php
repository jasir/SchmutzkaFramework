<?php
/**
 * @author	Patrik Votoček
 */

namespace Schmutzka\Forms\Controls;

class DateTime extends BaseDateTime
{
	/** @var string */
	public static $format = "Y-n-j H:i";
	/** @var string */
	public static $dateFormat = "Y-n-j";
	/** @var string */
	public static $timeFormat = "H:i";

	/**
	 * @param string  control name
	 * @param string  label
	 * @param int  width of the control
	 * @param int  maximum number of characters the user may enter
	 */
	public function __construct($label = NULL, $cols = NULL, $maxLength = NULL)
	{
		parent::__construct($label, $cols, $maxLength);
		$this->control->type = "datetime";
		$this->control->data('nella-forms-date', $this->translateFormatToJs(static::$dateFormat));
		$this->control->data('nella-forms-time', $this->translateFormatToJs(static::$timeFormat));
	}
}