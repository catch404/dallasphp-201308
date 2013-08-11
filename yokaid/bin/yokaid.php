<?php

require(sprintf(
	'%s/vendor/autoload.php',
	dirname(dirname(__FILE__))
));

(new Yokai\Server)
->Run();
