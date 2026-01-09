<?php
	$envPath = __DIR__ . '/.env';

	if (file_exists($envPath)) {
		$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '' || str_starts_with($line, '#')) continue;
			putenv($line);
		}
	}

	$dbhost = "localhost";
	$dbuser = "root";
	$dbpass = "";
	$db = "electripid";

	$conn = new mysqli($dbhost, $dbuser, $dbpass,$db) or die("Connect failed: %s\n". $conn -> error);

	if(!$conn){
		die("Connection Failed. ". mysqli_connect_error());
		echo "can't connect to database";
	}

	date_default_timezone_set('Asia/Manila');
	mysqli_query($conn, "SET time_zone = '+08:00'");

	function executeQuery($query){
		$conn = $GLOBALS['conn'];
		return mysqli_query($conn, $query);
	}
?>
