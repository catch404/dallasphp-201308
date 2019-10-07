<?php

namespace Yokai;
use \Yokai as Yokai;
use \React as React;
use \Nether as Nether;

class Server extends React\Socket\Server {

	protected $ClientList = [];
	/*//
	@type array
	a list of all the clients currently connected to the server.
	//*/

	protected $ChannelList = [];
	/*//
	@type array
	a list of all the channels this server is managing.
	//*/

	protected $Name;
	/*//
	@type string
	the name of the server. probably a hostname.
	//*/

	protected $Port;
	/*//
	@type int
	the port number to use for the server to accept connections on.
	//*/

	protected $MOTD;
	/*//
	@type string
	the filename for the MOTD text.
	//*/

	protected $EventLoop;
	/*//
	@type React\EventLoop
	the loop this server will manage for the mainline application.
	//*/

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	public function __construct($opt=null) {
		$opt = new Nether\Object\Mapped($opt,[
			'Name' => 'dev.majdak.net',
			'Port' => 6667,
			'MOTD' => sprintf(
				'%s/conf/motd.txt',
				dirname(dirname(dirname(__FILE__)))
			)
		]);

		$this->Name = $opt->Name;
		$this->Port = $opt->Port;
		$this->MOTD = $opt->MOTD;
		$this->EventLoop = React\EventLoop\Factory::create();

		parent::__construct($this->EventLoop);

		$this->Start();
		return;
	}

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	protected function Start() {
	/*//
	set the server to listen on the configured port and teach it what to do when
	clients connect to it.
	//*/

		if(!is_numeric($this->Port))
		throw new Yokai\Error\InvalidPortNumber($this->Port);

		try { $this->listen($this->Port,'dev.majdak.net'); }
		catch(\Exception $e) {
			throw new Yokai\Error\PortUnavailable($this->Port);
		}

		$this->on(
			'connection',
			[$this,'OnConnect']
		);

		Yokai\Util::ConsoleLog("server listening on {$this->Port}");
	}

	public function Run() {
		return $this->EventLoop->run();
	}

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	public function Send($msg, Yokai\Connectoin $client) {
	/*//
	send a message to a specific client.
	//*/

		$client->SendTo($msg);
		return;
	}

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	protected function OnConnect(React\Socket\Connection $cx) {
	/*//
	@argv React\Socket\Connction Connection
	handles clients connecting to the server.
	//*/

		$this->ClientList[] = $client = new Yokai\Connection($cx);
		Yokai\Util::ConsoleLog(
			'client connection: %s',
			$client->IP
		);

		// because irc is a newline based syntax we are going to add a buffer
		// property to the object to has data in as it is possible that a
		// single command to get broken up into multiple messages over the
		// network.
		$cx->Buffer = '';

		// when data is received from this client then we need to read it out
		// into a buffer and then process any currently available commands.
		// wrapping this in a callback so we can pass the nice connection
		// wrapper.
		$cx->on('data',function($data,$cx) use($client) {
			$this->OnRecv($data,$client);
		});

		// how to handle when a client gets disconnected.
		$cx->on('close',function($cx) use ($client) {
			$client->Online = false;
			$this->Command_QUIT('client lost connection',$client);
		});

		return;
	}

	protected function OnRecv($data, Yokai\Connection $client) {
	/*//
	@argv string Data, React\Socket\Connection Connection
	handles clients sending data to the server.
	//*/

		// uncomment this and use windows command line telnet to illustrate the
		// effect of incomplete messages being sent to the server.
		// Yokai\Util::ConsoleLog('added %s',$data);

		// add the data to the buffer.
		$client->BufferAdd($data);

		// then see if the buffer contains complete commands.
		while($input = $client->BufferDrain())
		$this->HandleInput($input,$client);

		return;
	}

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	protected function DigestInput($input) {
	/*//
	we are now dealing with IRC protocol.

	digest the IRC message into something we can program around. breaks the
	message into chunks and merges any bold chunks (things preceeded with colon)
	into the single chunk they really are.
	//*/
		$chunks = explode(' ',$input);
		$output = [];

		$boldfound = false;
		$boldchunk = '';

		foreach($chunks as $chunk) {
			if($boldfound) {
				$boldchunk .= " {$chunk}";
				continue;
			} else {
				if(strpos($chunk,':') === 0) {
					$boldfound = true;
					$boldchunk = ltrim($chunk,':');
					continue;
				} else {
					$output[] = $chunk;
				}
			}
		}

		// append the bold chunk to the output.
		if($boldfound) $output[] = $boldchunk;

		return $output;
	}

	protected function HandleInput($input, Yokai\Connection $client) {
		$msg = $this->DigestInput($input);

		switch(count($msg)) {
			case 2: { $this->HandleInputType2($msg,$client); break; }
			case 3: { $this->HandleInputType3($msg,$client); break; }
			case 5: { $this->HandleInputType5($msg,$client); break; }
			default: {
				Yokai\Util::ConsoleLog('unknown or malformed input: %s',$input);
				break;
			}
		}

		return;
	}

	protected function HandleInputType2(array $msg, Yokai\Connection $client) {

		switch($msg[0]) {
			case 'JOIN': {
				$this->Command_JOIN($msg[1],$client);
				break;
			}

			case 'NICK': {
				$this->Command_NICK($msg[1],$client);
				break;
			}

			case 'PING': {
				$this->Command_PING($msg[1],$client);
				break;
			}

			case 'QUIT': {
				$this->Command_QUIT($msg[1],$client);
				break;
			}

			default: {
				Yokai\Util::ConsoleLog('unknown type 2: %s',$msg[0]);
				break;
			}
		}

		return;
	}

	protected function HandleInputType3(array $msg, Yokai\Connection $client) {

		switch($msg[0]) {
			case 'PRIVMSG': {
				$this->Command_PRIVMSG($msg[1],$msg[2],$client);
				break;
			}

			default: {
				Yokai\Util::ConsoleLog('unknown type 3: %s',$msg[0]);
				break;
			}
		}

	}

	protected function HandleInputType5(array $msg, Yokai\Connection $client) {

		switch($msg[0]) {
			case 'USER': {
				$this->Command_USER($msg[1],$msg[2],$msg[3],$msg[4],$client);
				break;
			}

			default: {
				Yokai\Util::ConsoleLog('unknown type 5: %s',$msg[0]);
				break;
			}
		}

		return;
	}

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	protected function Command_JOIN($channel,$client) {
	/*//
	@return boolean
	handle clients wanting to join channels.
	//*/

		$channel = strtolower($channel);
		Yokai\Util::ConsoleLog(
			'JOIN request: %s => %s',
			$client->Nick,
			$channel
		);

		// if the channel does not yet exist create an instance for it.
		if(!array_key_exists($channel,$this->ChannelList))
		$this->ChannelList[$channel] = new Yokai\Channel($channel,$server);

		// add the client to the channel.
		$this->ChannelList[$channel]
		->AddClient($client)
		->Names($client)
		->Topic($client);

		$client->AddChannel($this->ChannelList[$channel]);

		return false;
	}

	protected function Command_NICK($nick,$client) {
	/*//
	@return boolean
	handle clients wanting to change their nickname.
	//*/

		Yokai\Util::ConsoleLog('NICK request: %s',$nick);

		$requested = strtolower($nick);
		foreach($this->ClientList as $other) {
			//if($other === $client) continue;

			if(strtolower($other->Nick) === $requested) {
				$client->SendTo(sprintf(
					":%s 433 %s :This nickname is already in use.",
					$this->Name,
					$nick
				));
				return false;
			}
		}

		if($client->Online) {
			// tell the people who need to know about this nick change, about
			// this nick change.
			foreach($client->ChannelList as $chan) {
				foreach($chan->ClientList as $other) {
					$other->SendTo(sprintf(
						':%s NICK :%s',
						$client->GetFullHost(),
						$nick
					));
				}
			}

			$client->Nick = $nick;
		}

		else {
			$client->Nick = $nick;

			// check if this completed a registration.
			if($client->Nick && $client->Ident)
			$this->Welcome($client);
		}

		return true;
	}

	protected function Command_PING($host,$client) {
	/*//
	@return boolean
	handle ping requests.
	//*/

		Yokai\Util::ConsoleLog("PING from {$client->Nick}");

		$client->SendTo(":{$this->Name} PONG {$this->Name} :{$host}");
		return;
	}

	protected function Command_PRIVMSG($who,$what,$client) {

		if(strpos($who,'#') === 0) {
			if(array_key_exists($who,$this->ChannelList))
			$this->ChannelList[$who]->Message($what,$client);
		} else {
			// user to user
		}

		return;
	}

	protected function Command_QUIT($msg,$client) {

		// tell everyone who can see this user that they have disconnected
		// from the network.
		foreach($client->ChannelList as $chan) {
			foreach($chan->ClientList as $other) {
				if($other === $client) continue;

				if($client->Online)
				$other->SendTo(sprintf(
					':%s QUIT :%s',
					$client->GetFullHost(),
					$msg
				));
			}

			$chan->RemoveClient($client);
		}

		// remove the client from the pool.
		foreach($this->ClientList as $key => $other) {
			if($other === $client) {
				unset($this->ClientList[$key]);
				break;
			}
		}

		return;
	}

	protected function Command_USER($user,$host,$server,$real,$client) {
	/*//
	@return boolean
	handle clients registering their user.
	//*/

		Yokai\Util::ConsoleLog('USER request: %s %s',$user,$real);

		$client->Ident = $user;
		$client->Host = $client->IP;
		$client->RealName = $real;

		// check if this completed a registration.
		if(!$client->Online) {
			if($client->Nick && $client->Ident)
			$this->Welcome($client);
		}

		return true;
	}

	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////

	protected function Welcome(Yokai\Connection $client) {
		Yokai\Util::ConsoleLog("welcoming {$client->Nick} to the network");

		$client->Online = true;
		$client->SendTo(":{$this->Name} 001 {$client->Nick} :Welcome to this IRC Network.");

		$this->MessageOfTheDay($client);
		return;
	}

	protected function MessageOfTheDay(Yokai\Connection $client) {
		if(!file_exists($this->MOTD) || !is_readable($this->MOTD)) {
			Yokai\Util::ConsoleLog("WARNING: error reading {$this->MOTD}");
			return;
		}

		$client->SendTo(":{$this->Name} 375 {$client->Nick} :MOTD START");
		$data = file($this->MOTD);
		foreach($data as $line) {
			$client->SendTo(sprintf(
				':%s 372 %s :%s',
				$this->Name,
				$client->Nick,
				rtrim($line)
			));
		}
		$client->SendTo(":{$this->Name} 376 {$client->Nick} :MOTD END");

		return;
	}

}
