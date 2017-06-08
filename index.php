<?php
function endsWith( $str, $sub ) {
   return ( substr( $str, strlen( $str ) - strlen( $sub ) ) === $sub );
}

function listConfigurations() {
	$dir    = './etc';
	$files = scandir($dir);

	foreach ($files as $file) {
		if (endsWith($file, '.json')) {
			echo '<li><a href="?config='.$file.'">'.$file.'</a></li>';
		}
	}
}

function runScript($config) {
	echo "Running script $config";
}
?>

<html>
	<body>
<?php if (!isset($_GET["config"])): ?>
	<p>Select a configuration to run:</p>
	<ul>
		<?php listConfigurations(); ?>
	</ul>
<?php else: ?>
	<?php runScript($_GET["config"]); ?>
<?php endif; ?>
	</body>
</html>