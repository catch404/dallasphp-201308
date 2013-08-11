<?php

require(sprintf(
	'%s/vendor/autoload.php',
	dirname(dirname(__FILE__))
));

$jar = new Demo\Jar;
$jar->Run();
