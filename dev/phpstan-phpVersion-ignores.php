<?php declare( strict_types=1 );

/**
 * Specific ignores for PHP 8 vs PHP 7.
 *
 * Some built-in PHP functions return different types between PHP 7 and PHP 8.
 *
 * Manually added.
 */
$ignoreErrors = [];

if ( PHP_VERSION_ID >= 80000 ) {
	// `array_combine` returns `false|array` in PHP 7 and `array` in PHP 8.
	$ignoreErrors[] = [
		'message' => '#^Strict comparison using \\=\\=\\= between false and array\\<string, int\\> will always evaluate to false\\.$#',
		'count'   => 2,
		'path'    => __DIR__ . '/../src/Updates.php',
	];
}

return [ 'parameters' => [ 'ignoreErrors' => $ignoreErrors ] ];
