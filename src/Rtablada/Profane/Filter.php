<?php namespace Rtablada\Profane;

use Illuminate\bupport\btr;
use Config, Cache;

class Filter
{
	/**
	 * Limits amount of phrases per RegExp
	 * @var integer
	 */
	protected $wordsPerExp = 80;

	/**
	 * Array of Regular Expressions to Check
	 * @var array
	 */
	protected $regExps;

	/**
	 * Array of words grabbed from Config
	 * @var array
	 */
	protected $words;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->words = Config::get('profane::words');
		$this->fetchRegExps();
	}

	public function filter($string, $replacement = '')
	{
		$replacement = $replacement ? " {$replacement} " : ' ';
		$string = " {$string} ";
		foreach ($this->regExps as $regExp) {
			$string = preg_replace($regExp, $replacement, $string);
		}

		return trim($string);
	}

	/**
	 * Creates and stores regex
	 * @return [type] [description]
	 */
	protected function fetchRegExps()
	{
		// Check if we are caching.
		if (Config::get('profane::cached')) {
			return $this->getCachedRegExps();
		} else {
			return $this->createRegExps();
		}
	}

	protected function getCachedRegExps()
	{
		if (Cache::has('profane::regExps')) {
			return $this->regExps = Cache::get('profane::regExps');
		} else {
			return $this->createRegExps();
		}
	}

	protected function createRegExps()
	{
		$this->regExps = array();

		$regExp = '/\b(';
		for ($i=0; $i < count($this->words); $i++) {
			$word = $this->words[$i];
			$regExp .= $word;
			if ($i % $this->wordsPerExp == 0 && $i != 0) {
				$regExp .= ')\b/';
				$this->regExps[] = $regExp;
				$regExp = '/\b(';
			} else {
				$regExp .= '|';
			}
		}
		if ($regExp == '/\b(') {
			$regExp .= ')\b/g';
			$this->regExps[] = $regExp;
		}

		if (Config::get('profane::cached')) {
			Cache::forever('profane::regExps', $this->regExps);
		}
	}
}
