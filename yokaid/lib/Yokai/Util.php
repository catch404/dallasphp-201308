<?php

namespace Yokai;

class Util {


	static function ConsoleLog($fmt) {
		$argv = array_slice(func_get_args(),1);
		if(!count($argv)) $fmt = str_replace('%','%%',$fmt);

		echo '[', gettimeofday(true), '] ', vsprintf($fmt,$argv), PHP_EOL;
		return;
	}

}
