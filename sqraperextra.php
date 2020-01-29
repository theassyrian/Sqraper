<?php
function runExtraOnNewQ() {
	
	/*
	Sqraper will check for existence of the file "sqraperextra.php" and include it if found.
	It will then check for the existence of a function called "runExtraOnNewQ" within the file
	and if found execute it ONLY when new Q is found.
	This is useful if you want to run additional routines such as parsing to a RSS file, sending
	push notifications, etc.	
	*/	

	echo "\n" . $GLOBALS['fgGreen'] . "EXECUTE SQRAPEREXTA.PHP FUNCTION: " . $GLOBALS['colorEnd'] . "runExtraOnNewQ\n";
	
	// Insert your custom code here.
	
}

function runExtraAlways() {

	/*
	Sqraper will check for existence of the file "sqraperextra.php" and include it if found.
	It will then check for the existence of a function called "runExtraAlways" within the file
	and if found execute it at the end of each loop. This is useful if you want to run
	additional routines.	
	*/	

	echo "\n" . $GLOBALS['fgGreen'] . "EXECUTE SQRAPEREXTA.PHP FUNCTION: " . $GLOBALS['colorEnd'] . "runExtraAlways\n";
	
	// Insert your custom code here.

}
?>
