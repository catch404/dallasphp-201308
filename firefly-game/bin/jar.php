<?php

require(sprintf(
	'%s/vendor/autoload.php',
	dirname(dirname(__FILE__))
));

Ratchet\Server\IoServer::factory(
	new Ratchet\WebSocket\WsServer(new Demo\Jar), 7890
)->run();