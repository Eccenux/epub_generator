<?php

class Logger
{
	public function __construct() {
		// $this->var = $var;
	}

	/** info */
	public function log($info)
	{
		$now = DateTime::createFromFormat('U.u', microtime(true));
		$now = $now->setTimeZone(new DateTimeZone(date_default_timezone_get()));
		$dt = $now->format("Y-m-d H:i:s.u");
		echo "\n[INFO] $dt: ".$info;
	}
}
