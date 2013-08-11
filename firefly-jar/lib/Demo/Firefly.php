<?php

namespace Demo;
use \Demo as Demo;
use \React as React;

class Firefly {

	protected $ID;
	/*//
	@type string
	my unique ID given to me by the server on connection.
	//*/

	protected $Socket;
	/*//
	@type stream
	the open connection to the server.
	//*/

	protected $EventLoop;
	/*//
	@type React\EventLoop\LoopInterface
	an event loop object we are going to keep to ourselves.
	//*/

	protected $Buffer;
	/*//
	@type string
	the input buffer for making sure network messages get fully constructed
	before we attempt to parse them.
	//*/

	////////////////
	////////////////

	public function __construct($host,$port) {
		$this->EventLoop = React\EventLoop\Factory::create();

		// open up a generic stream socket to the server.
		$fp = stream_socket_client("tcp://{$host}:{$port}");
		if(!$fp) {
			echo "ERROR: unable to connect to {$host}:{$port}.", PHP_EOL;
			exit(1);
		} else {
			// set the stream to non-blocking.
			stream_set_blocking($fp,false);
		}

		// reuse React's connection class to wrap the socket and give us nice
		// event interface again.
		$this->Socket = new React\Socket\Connection($fp,$this->EventLoop);
		$this->Socket->on('data',[$this,'OnDataIn']);
		$this->Socket->on('close',[$this,'OnDisconnect']);

		return;
	}

	////////////////
	////////////////

	public function Run() {
		return $this->EventLoop->run();
	}

	public function BufferAdd($input) {
		$this->Buffer .= $input;
		return;
	}

	public function BufferClear() {
		$this->Buffer = '';
		return;
	}

	public function BufferDrain() {

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

	public function Send($msg) {
		if(is_array($msg))
		$msg = json_encode($msg);

		//echo "<<< {$msg}", PHP_EOL;
		$this->Socket->write("{$msg}\n");

		return;
	}

	public function OnDataIn($data,$cx) {

		$this->BufferAdd($data);
		while($msg = $this->BufferDrain()) {
			$obj = json_decode($msg);
			if(!$obj || !property_exists($obj,'Type')) {
				echo "WARNING: malformed data from server.", PHP_EOL;
				continue;
			}

			$this->HandleMessageObject($obj);
		}

		return;
	}

	public function OnDisconnect($cx) {
		echo "!!! the server has dropped.", PHP_EOL;
		exit(0);
		return;
	}

	////////////////
	////////////////

	protected function HandleMessageObject($obj) {
		switch($obj->Type) {
			case 'blink': { $this->Handle_Blink($obj); break; }
			case 'count': { $this->Handle_Count($obj); break; }
			case 'join': { $this->Handle_Join($obj); break; }
			case 'leave': { $this->Handle_Leave($obj); break; }
			case 'welcome': { $this->Handle_Welcome($obj); break; }
			default: {
				echo "WARNING: unhandlable data from server.", PHP_EOL;
				print_r($obj);
				break;
			}
		}

		return;
	}

	protected function Handle_Welcome($obj) {
		if(!property_exists($obj,'ID') || !$obj->ID) {
			echo "WARNING: malformed welcome message from server.", PHP_EOL;
			return;
		}

		$this->ID = $obj->ID;
		echo ">>> {$obj->Message}", PHP_EOL;
		echo "### I am firefly #{$this->ID}.", PHP_EOL;

		$this->ReadyButtLamp();
		return;
	}

	protected function Handle_Blink($obj) {
		if(!property_exists($obj,'ID') || !$obj->ID) {
			echo "WARNING: malformed blink message from server.", PHP_EOL;
			return;
		}
		if(!property_exists($obj,'Message')) {
			echo "WARNING: malformed blink message from server.", PHP_EOL;
			return;
		}

		echo "<firefly #{$obj->ID}> {$obj->Message}", PHP_EOL;
		return;
	}

	protected function Handle_Join($obj) {
		if(!property_exists($obj,'ID') || !$obj->ID) {
			echo "WARNING: malformed join message from server.", PHP_EOL;
			return;
		}

		echo "### firefly #{$obj->ID} has entered the jar.", PHP_EOL;
		return;
	}

	protected function Handle_Leave($obj) {
		if(!property_exists($obj,'ID') || !$obj->ID) {
			echo "WARNING: malformed leave message from server.", PHP_EOL;
			return;
		}

		echo "### firefly #{$obj->ID} has left the jar.", PHP_EOL;
		return;
	}

	protected function Handle_Count($obj) {
		if(!property_exists($obj,'Count')) {
			echo "WARNING: malformed leave message from server.", PHP_EOL;
			return;
		}

		echo "~~~ There are {$obj->Count} fireflies present.", PHP_EOL;
		return;
	}

	////////////////
	////////////////

	public function ReadyButtLamp() {
		$delay = rand(3,16);

		//echo "### blinking in {$delay} seconds.", PHP_EOL;

		$this->EventLoop->addTimer(
			$delay,
			[$this,'EngageButtLamp']
		);

		return;
	}

	public function EngageButtLamp() {

		$message = trim(str_repeat('BLINK ',rand(1,8)));
		echo "<<< {$message}", PHP_EOL;

		$this->Send([
			'Type'    => 'blink',
			'Message' => $message
		]);

		$this->ReadyButtLamp();
		return;
	}

}
