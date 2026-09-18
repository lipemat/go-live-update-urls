<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Method Go_Live_Update_Urls\\\\Core\\:\\:flush_caches\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/../src/Core.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to protected property wpdb\\:\\:\\$dbname\\.$#',
	'identifier' => 'property.protected',
	'count' => 1,
	'path' => __DIR__ . '/../src/Database.php',
];
$ignoreErrors[] = [
	'message' => '#^Casting to int something that\'s already int\\.$#',
	'identifier' => 'cast.useless',
	'count' => 1,
	'path' => __DIR__ . '/../src/Database.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$query of method wpdb\\:\\:prepare\\(\\) expects literal\\-string, non\\-falsy\\-string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/../src/Database.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$query of method wpdb\\:\\:query\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/Database.php',
];
$ignoreErrors[] = [
	'message' => '#^Property wpdb\\:\\:\\$links \\(string\\) in isset\\(\\) is not nullable\\.$#',
	'identifier' => 'isset.property',
	'count' => 1,
	'path' => __DIR__ . '/../src/Database.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Go_Live_Update_Urls\\\\Serialized\\:\\:set_dry_run\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/../src/Serialized.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Go_Live_Update_Urls\\\\Skip_Rows\\:\\:\\$primary_keys type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/../src/Skip_Rows.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to protected property wpdb\\:\\:\\$dbname\\.$#',
	'identifier' => 'property.protected',
	'count' => 1,
	'path' => __DIR__ . '/../src/Updates.php',
];
$ignoreErrors[] = [
	'message' => '#^Construct empty\\(\\) is not allowed\\. Use more strict comparison\\.$#',
	'identifier' => 'empty.notAllowed',
	'count' => 1,
	'path' => __DIR__ . '/../src/Updates.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$query of method wpdb\\:\\:prepare\\(\\) expects literal\\-string, non\\-falsy\\-string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/Updates.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
