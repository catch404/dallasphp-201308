<?php

namespace Demo;
use \Demo as Demo;
use \React as React;

class Jar extends React\Socket\Server {

	protected $Port = 7890;
	/*//
	@type int
	the port number the server will listen upon.
	//*/

	protected $EventLoop;
	/*//
	@type React\EventLoop\LoopInterface
	an event loop object we are going to keep to ourselves.
	//*/

	protected $Contents = [];
	/*//
	@type array
	a list of all the contents of the jar. (array of CapturedFirefly)
	//*/

	protected $NextClientNumber = 1;
	/*//
	@type int
	a just a counter to give firefly's a unique id.
	//*/

	////////////////
	////////////////

	public function __construct($port=null) {
		if($port) $this->Port = $port;

		// create a protected event loop and use it to initialize the
		// React\Socket\Server base of this class. this creates the core jar
		// designed to do its one job, hold things.
		$this->EventLoop = React\EventLoop\Factory::create();
		parent::__construct($this->EventLoop);

		// attempt to open the port.
		try { $this->listen($this->Port); }
		catch(\Exception $e) {
			echo "ERROR: unable to open port {$this->Port}. already taken?";
			echo PHP_EOL;
			exit(1);
		}

		// setup a connection event handler. teach the jar how to open it's lid
		// to allow fireflies in.
		$this->on('connection',[$this,'OnConnect']);

		return;
	}

	////////////////
	////////////////

	public function Run() {
	/*//
	engage this server's event loop. set the jar on the table and walk away.
	//*/

		echo "INFO: server started.", PHP_EOL;
		echo "INFO: accepting connections on port {$this->Port}.", PHP_EOL;
		return $this->EventLoop->run();
	}

	////////////////
	////////////////

	public function SendToAll($msg,$except=null) {
		if(is_array($msg))
		$msg = json_encode($msg);

		foreach($this->Contents as $firefly) {
			if($firefly === $except) continue;
			else $firefly->SendTo($msg);
		}

		return;
	}

	////////////////
	////////////////

	public function OnConnect($cx) {
	/*//
	handle new connections to the server. putting fireflies into the jar.
	//*/

		$firefly = new Demo\CapturedFirefly($cx,$this);
		$firefly->ID = $this->NextClientNumber++;

		// keep a reference of all the fireflies currently in the jar.
		$this->Contents[$firefly->ID] = $firefly;

		$cx->on('data',function($input,$cx) use($firefly) {
			$this->OnDataIn($input,$firefly);
			return;
		});

		$cx->on('close',function($cx) use($firefly) {
			$this->OnDisconnect($firefly);
			return;
		});

		$firefly->SendTo([
			"Type"    => "welcome",
			"ID"      => $firefly->ID,
			"Message" => "Welcome to the jar. You are firefly #{$firefly->ID}"
		]);

		echo "### firefly #{$firefly->ID} connected.", PHP_EOL;

		// notify all the fireflies that a new one has entered.
		$this->SendToAll([
			'Type' => 'join',
			'ID'   => $firefly->ID
		],$firefly);

		// and send an updated count of how many are in there.
		$this->SendToAll([
			'Type'  => 'count',
			'Count' => count($this->Contents)
		]);

		return;
	}

	public function OnDisconnect($firefly) {
		unset($this->Contents[$firefly->ID]);

		echo "### firefly #{$firefly->ID} disconnected.", PHP_EOL;

		$this->SendToAll([
			'Type' => 'leave',
			'ID'   => $firefly->ID
		]);
		return;
	}

	public function OnDataIn($input,$firefly) {
		$firefly->BufferAdd($input);

		while($msg = $firefly->BufferDrain()) {
			$obj = json_decode($msg);

			if(!$obj || !property_exists($obj,'Type')) {
				echo "WARNING: malformed message from client.", PHP_EOL;
				continue;
			}

			$this->HandleMessageObject($obj,$firefly);
		}

		return;
	}

	////////////////
	////////////////

	public function HandleMessageObject($obj,$firefly) {
		switch($obj->Type) {
			case 'blink': { $this->Handle_Blink($obj,$firefly); break; }
			default: {
				echo "WARNING: unhandled message from client.", PHP_EOL;
				print_r($obj);
				break;
			}
		}
	}

	public function Handle_Blink($obj,$firefly) {
		if(!property_exists($obj,'Message')) {
			echo "WARNING: malformed blink message from client.", PHP_EOL;
			return;
		}

		echo ">>> <firefly #{$firefly->ID}> {$obj->Message}", PHP_EOL;

		// notify all the other fireflies that this happened.
		$this->SendToAll([
			'Type'    => 'blink',
			'ID'      => $firefly->ID,
			'Message' => $obj->Message
		],$firefly);

		return;
	}

}
