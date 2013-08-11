<?php

namespace Yokai;
use \Yokai as Yokai;
use \React as React;

class Connection {

	public $IP;
	public $Nick;
	public $Ident;
	public $Host;
	public $RealName;
	public $Hostmask;
	public $Online = 0;

	public $ChannelList = [];

	protected $Socket = null;
	protected $Buffer = '';

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	public function __construct(React\Socket\Connection $cx) {
		$this->Socket = $cx;
		$this->IP = $cx->getRemoteAddress();
	}

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	public function Disconnect() {
		//$this->Socket->close();
		//return;
	}

	public function SendTo($msg) {
	/*//
	@return self
	send a message to the server.
	//*/

		// Yokai\Util::ConsoleLog('>>> %s',$msg);

		$this->Socket->write($msg.PHP_EOL);
		return $this;
	}

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	public function BufferAdd($msg) {
	/*//
	@argv string Data
	@return self
	add content to the end of the buffer.
	//*/

		$this->Buffer .= $msg;
		return $this;
	}

	public function BufferClear() {
	/*//
	@reutrn self
	drop all the buffer.
	//*/

		$this->Buffer = '';
		return $this;
	}

	public function BufferDrain() {
	/*//
	@return string
	run through the buffer data finding the first complete message it contains.
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

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	public function GetFullHost() {
		if($this->Online) return "{$this->Nick}!{$this->Ident}@{$this->Host}";
		else return null;
	}

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	public function AddChannel($channel) {

		foreach($this->ChannelList as $chan) {
			if($channel === $chan) return;
		}

		$this->ChannelList[] = $channel;

		return $this;
	}

}
