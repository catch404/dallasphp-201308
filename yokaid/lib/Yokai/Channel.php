<?php

namespace Yokai;

class Channel {

	public $Name;
	/*//
	@type string
	the name of this channel.
	//*/

	public $Topic = 'A New Yokai Channel';
	/*//
	@type string
	the topic for this channel.
	//*/

	public $ClientList = [];
	/*//
	@type array
	a list of references of all the clients in this channel.
	//*/

	protected $Server;
	/*//
	@type Yokai\Server
	a reference back to the server running this channel.
	//*/

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	public function __construct($name,$server) {
		$this->Name = $name;
		$this->Server = $server;
		return;
	}

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	public function SendTo($msg) {
	/*//
	@return self
	send a message to all the users in the channel.
	//*/

		foreach($this->ClientList as $client)
		$client->SendTo($msg);

		return $this;
	}

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	public function AddClient($client) {
	/*//
	@return self
	add a client reference to this channel.
	//*/

		$found = false;

		// do not add the client if they are already there.
		foreach($this->ClientList as $other) {
			if($other === $client) return $this;
		}

		// add new client
		$this->ClientList[] = $client;

		// announce the new client joining the channel to all the things
		// already there, as well as the guy that just joined.
		$this->SendTo(sprintf(
			":%s JOIN %s",
			$client->GetFullHost(),
			$this->Name
		));

		return $this;
	}

	public function RemoveClient($client,$part=true) {
	/*//
	@argv Yokai\Connection Client
	@return self
	remove a client from this channel.
	//*/

		foreach($this->ClientList as $key => $other) {
			if($other === $client) {
				unset($this->ClientList[$key]);
				return;
			}
		}

		if($part) {
			// send a part message to all the other clients in this channel.
		}

		return $this;
	}

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	public function Message($msg,$from) {
	/*//
	@argv string Message, Yokai\Connection From
	@return self
	send a message to all the clients in this channel.
	//*/

		foreach($this->ClientList as $to) {
			if($to === $from) continue;

			$to->SendTo(sprintf(
				":%s PRIVMSG %s :%s",
				$from->GetFullHost(),
				$this->Name,
				$msg
			));
		}

		return $this;
	}

	public function Names($to) {
	/*//
	@argv Yokai\Connction To
	return self
	send a list of all the names in this channel to a client.
	//*/

		// irc protocol allows you to send multiple users per 353 but i do not
		// feel like dealing with line maxlen checks for this demo.

		foreach($this->ClientList as $other) {
			$to->SendTo(sprintf(
				':%s 353 %s = %s :%s',
				$this->Server->Name,
				$to->Nick,
				$this->Name,
				$other->Nick
			));
		}

		$to->SendTo(sprintf(
			':%s 366 %s %s :End of /NAMES list.',
			$this->Server->Name,
			$to->Nick,
			$this->Name
		));

		return $this;
	}

	public function Topic($to) {
	/*//
	@argv Yokai\Connection To
	@return self
	send the channel topic to a client.
	//*/

		$to->SendTo(sprintf(
			':yokaid.local 332 %s %s :%s',
			$to->Nick,
			$this->Name,
			$this->Topic
		));

		return $this;
	}

}
