<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Only booleans are allowed in an if condition, array\\<int\\> given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Admin.php',
];
$ignoreErrors[] = [
	'message' => '#^Error suppression via "@" should not be used\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../src/Core.php',
];
$ignoreErrors[] = [
	'message' => '#^Casting to string something that\'s already string\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/../src/Serialized.php',
];
$ignoreErrors[] = [
	'message' => '#^Error suppression via "@" should not be used\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/../src/Serialized.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable property access on mixed\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/../src/Serialized.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable property access on object\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/../src/Serialized.php',
];
$ignoreErrors[] = [
	'message' => '#^Casting to int something that\'s already int\\.$#',
	'count' => 7,
	'path' => __DIR__ . '/../src/Updates.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
