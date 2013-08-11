<?php

namespace Demo;
use \Demo as Demo;
use \React as React;

class CapturedFirefly {

	public $ID;
	/*//
	@type string
	a quick and dirty unique id name thing for this connection.
	//*/

	protected $Socket;
	/*//
	@type React\Socket\Connection
	the react connection object for handling data transfer.
	//*/

	protected $Server;
	/*//
	@type Demo\Jar
	a reference back to the server handling this client.
	//*/

	protected $Buffer;
	/*//
	@type string
	the input buffer for making sure network messages get fully constructed
	before we attempt to parse them.
	//*/

	////////////////
	////////////////

	public function __construct(React\Socket\Connection $cx, Demo\Jar $server) {
		$this->Socket = $cx;
		$this->Server = $server;
		$this->ID = md5(microtime(true).rand(1,999999).rand(1,999999));
		$this->BufferClear();
		return;
	}

	////////////////
	////////////////

	public function BufferAdd($input) {
		$this->Buffer .= $input;
		return;
	}

	public function BufferClear() {
		$this->Buffer = '';
		return;
	}

	public function BufferDrain() {
	/*//
	@return string

	because network communications can have their messages split across multiple
	packets as they traverse the world of tcp/ip we needed to queue all the data
	received so that we could process them whole.

	the protocol i invented for this demo is based off NEW LINE TECHNOLOGY so
	we just throw data onto the buffer. after adding data we can then check if
	we have any new lines... the start to a new line is a complete message we
	can run with.

	nodejs has a readline func for this that didn't even work last time i used
	it. php has one too but i don't feel like installing that extention just for
	this.
	//*/

		while(($pos = strpos($this->Buffer,chr(10))) !== false) {
			$cmd = trim(substr($this->Buffer,0,$pos));
			$this->Buffer = substr($this->Buffer,($pos+1));

			// if the message was empty do not bother with it, else return it
			// so the server can decide what to do about it.
			if(!$cmd) continue;
			else return $cmd;
		}

		return null;
	}

	////////////////
	////////////////

	public function SendTo($msg) {
		if(is_array($msg))
		$msg = json_encode($msg);

		$this->Socket->write("{$msg}\n");

		return;
	}

}
