<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Method Go_Live_Update_Urls\\\\Core\\:\\:flush_caches\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Core.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Go_Live_Update_Urls\\\\Core\\:\\:plugin_action_link\\(\\) has parameter \\$actions with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Core.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Go_Live_Update_Urls\\\\Core\\:\\:plugin_action_link\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Core.php',
];
$ignoreErrors[] = [
	'message' => '#^Construct empty\\(\\) is not allowed\\. Use more strict comparison\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Database.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Go_Live_Update_Urls\\\\Database\\:\\:get_all_table_names\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Database.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Go_Live_Update_Urls\\\\Database\\:\\:get_serialized_tables\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Database.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Go_Live_Update_Urls\\\\Database\\:\\:update_the_database\\(\\) has parameter \\$tables with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Database.php',
];
$ignoreErrors[] = [
	'message' => '#^Construct empty\\(\\) is not allowed\\. Use more strict comparison\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/../src/Serialized.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Go_Live_Update_Urls\\\\Serialized\\:\\:has_missing_classes\\(\\) has parameter \\$data with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Serialized.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Go_Live_Update_Urls\\\\Serialized\\:\\:replace_tree\\(\\) has parameter \\$data with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Serialized.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Go_Live_Update_Urls\\\\Serialized\\:\\:replace_tree\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Serialized.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Go_Live_Update_Urls\\\\Serialized\\:\\:set_dry_run\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Serialized.php',
];
$ignoreErrors[] = [
	'message' => '#^Construct empty\\(\\) is not allowed\\. Use more strict comparison\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../src/Skip_Rows.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Go_Live_Update_Urls\\\\Skip_Rows\\:\\:\\$primary_keys type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Skip_Rows.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Go_Live_Update_Urls\\\\Skip_Rows\\:\\:\\$skip type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Skip_Rows.php',
];
$ignoreErrors[] = [
	'message' => '#^Construct empty\\(\\) is not allowed\\. Use more strict comparison\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Updates.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
