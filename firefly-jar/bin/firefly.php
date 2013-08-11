<?php

require(sprintf(
	'%s/vendor/autoload.php',
	dirname(dirname(__FILE__))
));

$firefly = new Demo\Firefly('localhost',7890);
$firefly->Run();
