<?php

namespace Yokai\Error;

class PortUnavailable extends \Exception {
	public function __construct($port) {
		parent::__construct("unable to open port ({$port})");
		return;
	}
}
