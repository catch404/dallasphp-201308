<?php

namespace Demo;
use \Demo as Demo;
use \Ratchet;
use \Exception;

class Jar implements Ratchet\MessageComponentInterface {

	protected $NextID = 1;
	protected $Clients = [];

	public function onOpen(Ratchet\ConnectionInterface $cx) {

		$cx->FireflyID = $this->NextID++;
		$cx->FireflyOnline = false;
		$cx->PosX = 0;
		$cx->PosY = 0;
		$this->Clients[$cx->FireflyID] = $cx;

		echo "client connected.", PHP_EOL;
		return;
	}

	public function onClose(Ratchet\ConnectionInterface $cx) {
		echo "client disconnected.", PHP_EOL;
		unset($this->Clients[$cx->FireflyID]);
		$this->Handle_Leave($cx);
		return;
	}

	public function onError(Ratchet\ConnectionInterface $cx, Exception $e) {

		return;
	}

	public function onMessage(Ratchet\ConnectionInterface $from, $msg) {
		echo "<{$from->FireflyID}> {$msg}", PHP_EOL;

		$obj = json_decode($msg);
		if(!$obj || !property_exists($obj,'Type')) {
			echo "WARNING: malformed data from client.", PHP_EOL;
			return;
		}

		switch($obj->Type) {
			case 'register': { $this->Handle_Register($from,$obj); break; }
			case 'move': { $this->Handle_Move($from,$obj); break; }
			case 'lamp': { $this->Handle_Lamp($from,$obj); break; }
			default: {
				echo "WARNING: unhandled type: {$obj->Type}", PHP_EOL;
				break;
			}
		}

		return;
	}

	////////////////
	////////////////

	public function SendTo($to,$msg) {
		if(is_array($msg))
		$msg = json_encode($msg);

		echo ">>> {$msg}", PHP_EOL;

		$to->send($msg);

		return;
	}

	public function SendToAll($msg,$except=null) {
		foreach($this->Clients as $client) {
			if($client === $except) continue;

			$this->SendTo($client,$msg);
		}
	}

	////////////////
	////////////////

	public function Handle_Register($from,$obj) {

		echo "%%% client registration", PHP_EOL;

		$x = rand(60,260);
		$y = rand(160,480);

		// notify the client that they can join the game.
		$from->FireflyOnline = true;
		$from->PosX = $x;
		$from->PosY = $y;
		$this->SendTo($from,[
			'Type'     => 'welcome',
			'ID'       => $from->FireflyID,
			'Position' => [$x,$y]
		]);

		// notify the other clients that someone has joined.
		$this->SendToAll([
			'Type'     => 'join',
			'ID'       => $from->FireflyID,
			'Position' => [$x,$y]
		],$from);

		// notify the current client about the others.
		foreach($this->Clients as $client) {
			if($client === $from) continue;

			$this->SendTo($from,[
				'Type'     => 'join',
				'ID'       => $client->FireflyID,
				'Position' => [$client->PosX,$client->PosY]
			]);
		}

		return;
	}

	public function Handle_Leave($from) {

		// notify all the other clients that someone has left.
		$this->SendToAll([
			'Type' => 'leave',
			'ID'   => $from->FireflyID
		]);

		return;
	}

	public function Handle_Move($from,$obj) {

		switch($obj->Dir) {
			case 'up': { $from->PosY-=4; break; }
			case 'down': { $from->PosY+=4; break; }
			case 'left': { $from->PosX-=4; break; }
			case 'right': { $from->PosX+=4; break; }
		}

		if($from->PosY < 160) $from->PosY = 160;
		if($from->PosY > 480) $from->PosY = 480;
		if($from->PosX < 60) $from->PosX = 60;
		if($from->PosX > 260) $from->PosX = 260;

		$this->SendToAll([
			"Type" => "move",
			"ID" => $from->FireflyID,
			"Position" => [$from->PosX,$from->PosY]
		]);

		return;
	}

	public function Handle_Lamp($from,$obj) {

		$this->SendToAll([
			"Type"  => "lamp",
			"ID"    => $from->FireflyID,
			"State" => $obj->State
		]);

		return;
	}

	////////////////
	////////////////

}
