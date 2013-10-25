<?php

namespace Schmutzka;

use Nette;

class DateTime extends Nette\DateTime
{
	/** @var array */
	private static $holidays = array('12-24', '12-25', '12-26', '01-01', '05-01', '05-08', '07-05', '07-06', '09-28', '10-28', '11-17');

	/** @var array */
	private $weekDayLocalized = [
		'cs' => [
			'short' => [1 => 'Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne'],
			'long' => [1 => 'pondělí', 'úterý', 'středa', 'čtvtek', 'pátek', 'sobota', 'neděle']
		]
	];

	/** @var array */
	private $monthLocalized = [
		'cs' => [1 => 'leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec']
	];


	/**
	 * Object factory
	 * @param mixed
	 * @param string
	 * @return self
	 */
	public static function get($data = NULL, $format = NULL)
	{
		$self = new static($data);
		if ($format) {
			return $self->modify($format);
		}

		return $self;
	}


	/**
	 * Is date between limts
	 * @param Nette\DateTime|string
	 * @param Nette\DateTime|string
	 * @return bool
	 */
	public function isBetween($from, $to)
	{
		$from = self::from($from);
		$to = self::from($to);

		return ($this >= $from && $this <= $to);
	}


	/********************** month/year **********************/


	/**
	 * Get number of days in month
	 * @return int
	 */
	public function daysInMonth()
	{
		$month = $this->format('m');
		$year = $this->format('Y');
		return cal_days_in_month(CAL_GREGORIAN, $month, $year);
	}


	/**
	 * Get week start and end
	 */
	public function weekStartEnd()
	{
		$year = $this->format('Y');
		$week = $this->format('m') - 1; // - intentionally

		$time = strtotime('1 January $year', time());
		$day = date('w', $time);
		$time += ((7 * $week) + 1 - $day) * 24 * 3600;

		$result = array(
			'start' => new self($time),
			'end' => self::get($time)->modify('+6 days'),
		);

		return $result;
	}


	/********************** change position **********************/


	/**
	 * Minus another DateTime
	 * @param  string
	 * @return int
	 */
	public function minus($dateTime)
	{
		$time1 = strtotime($this);
		$time2 = strtotime($dateTime . ':00');

		return $time2 - $time1;
	}


	/**
	 * @param int
	 */
	public function addWorkday($amount = 1)
	{
		for ($i = 0; $i < $amount; $i++) {
			$this->modify('+1 day');
			while ( ! $this->isWorkingDay()) {
				$this->modify('+1 day');
			}
		}

		return $this;
	}


	/********************** get position **********************/


	/**
	 * Get nearest day of the week from today
	 * @param int 1-7
	 * @return DateTime
	 */
	public function getNextNearestDay($day)
	{
		$currentDay = $this->format('N');
		$dayShift = ((7 + $day - $currentDay) % 7) ?: 7;

		$this->modify('+$dayShift days');
		return $this;
	}


	/**
	 * Get distance from now in days
	 * @return int
	 */
	public function getFromNow()
	{
		$today = new self;
		$diff = strtotime($this) - strtotime($today); // in secs

		$days = floor($diff / (60 * 60 * 24));

		return $days;
	}


	/**
	 * Get age from birthdate
	 * @return float
	 */
	public function getAge()
	{
		return floor((date('Ymd') - date('Ymd', $this)) / 10000);
	}


	/********************** localization **********************/


	/**
	 * @param string
	 * @param string
	 * @param bool
	 * @return string
	 */
	public function dayLocalized($lang = 'cs', $type = 'short', $ucfirst = TRUE)
	{
		$day = $this->format('N');

		if (isset($this->weekDayLocalized[$lang][$type][$day])) {
			$return = $this->weekDayLocalized[$lang][$type][$day];
			return ($ucfirst ? ucfirst($return) : strtolower($return));
		}

		return $this->format('D');
	}


	/**
	 * @param string
	 * @param bool
	 * @param bool
	 */
	public function monthLocalized($lang = 'cs', $ucfirst = TRUE)
	{
		$month = $this->format('n');

		if (isset($this->monthLocalized[$lang][$month])) {
			$return = $this->monthLocalized[$lang][$month];
			return ($ucfirst ? ucfirst($return) : strtolower($return));
		}

		return $this->format('F');
	}


	/**
	 * @param string
	 * @return string
	 */
	public function format($mask = 'Y-m-d H:i:s')
	{
		return parent::format($mask);
	}


	/********************** state **********************/


	/**
	 * @return bool
	 */
	public function isToday()
	{
		if ($this->format('Y-m-d') == self::from(NULL)->format('Y-m-d')) {
			return TRUE;
		}

		return FALSE;
	}


	/**
	 * @return bool
	 */
	public function isWorkingDay()
	{
		if ($this->format('N') >= 6 || $this->isHoliday()) {
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * @return bool
	 */
	public function isWeekend()
	{
		return ($this->format('N') >= 6);
	}


	/**
	 * @return bool
	 */
	public function isHoliday()
	{
		if (in_array($this->format('m-d'), self::$holidays)) {
			return TRUE;
		}

		if ($this->format('m-d') == strftime('%m-%d', easter_date($this->format('Y')))) { // easter
			return TRUE;
		}

		return FALSE;
	}


	/**
	 * @return bool
	 */
	public function isPast()
	{
		if ($this < new self) {
			return TRUE;
		}

		return FALSE;
	}


	/**
	 * @return bool
	 */
	public function isFuture()
	{
		return ! $this->isPast();
	}

}
