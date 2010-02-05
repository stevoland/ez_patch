<?php

$patches = array(
    'varnish-4.2.0',
	'eznewsletter-1.6b',
	'ezoe-5.0.4',
	'ezpublish-4.2.0'
);

$base_path = 'patch/';
for ( $i = count($patches)-1; $i >= 0; $i-- )
{
	set_include_path($base_path . $patches[$i] . PATH_SEPARATOR . get_include_path());
}

?>