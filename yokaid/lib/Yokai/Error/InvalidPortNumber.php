<?php

namespace Yokai\Error;

class InvalidPortNumber extends \Exception {
	public function __construct($port) {
		parent::__construct("invalid port number ({$port})");
		return;
	}
}
