<?php
function displayError($errDescription) {	
	echo "\n\e[1;31m>>>\e[0m ========================================\n";
	echo "\e[1;31m>>>\e[0m ERROR: $errDescription\n";			
	echo "\e[1;31m>>>\e[0m SLEEP: 5 seconds.\n";			
	echo "\e[1;31m>>>\e[0m ========================================\n\n";		
	sleep(5);	
}

function getConfig() {
	if (!file_exists('sqraper_config.json')) {
		echo "\e[1;31mCREATE FILE:\e[0m sqraper_config.json did not exist. Creating and reading default configuration JSON file.\n";
		$defaultConfig = array(
			'qTrip' => '!!mG7VJxZNCI',
			'boards' => ['qresearch'],
			'domain8Kun' => '8kun.top',
			'domain8KunForLinks' => '8kun.top',
			'lokiKun' => 'http://pijdty5otm38tdex6kkh51dkbkegf31dqgryryz3s3tys8wdegxo.loki',
			'useLoki' => true,
			'saveRemoteFilesToLocal' => true,
			'readFromLocal8KunFiles' => false,
			'sleepBetweenNewQPostChecks' => 150,
			'productionPostsJSONFilename' => 'posts.json',
			'productionJSONFolder' => 'json/',
			'productionMediaFolder' => 'media/',
			'productionMediaURL' => 'https://yourserver.com/media/',
			'ftpUploadJSON' => false,
			'ftpUploadJSONFolder' => '/data/posts/',
			'ftpUploadMedia' => false,
			'ftpUploadMediaFolder' => '/data/media/',
			'ftpServer' => 'ftp.yourserver.com',
			'ftpLoginID' => 'your_user_name',
			'ftpPassword' => 'your_password'
		);		
		$GLOBALS['qTrip'] = $defaultConfig['qTrip'];
		$GLOBALS['boards'] = $defaultConfig['boards'];
		$GLOBALS['domain8Kun'] = $defaultConfig['domain8Kun'];
		$GLOBALS['domain8KunForLinks'] = $defaultConfig['domain8KunForLinks'];
		$GLOBALS['lokiKun'] = $defaultConfig['lokiKun'];
		$GLOBALS['useLoki'] = $defaultConfig['useLoki'];
		$GLOBALS['saveRemoteFilesToLocal'] = $defaultConfig['saveRemoteFilesToLocal'];
		$GLOBALS['readFromLocal8KunFiles'] = $defaultConfig['readFromLocal8KunFiles'];
		$GLOBALS['sleepBetweenNewQPostChecks'] = $defaultConfig['sleepBetweenNewQPostChecks'];
		$GLOBALS['productionPostsJSONFilename'] = $defaultConfig['productionPostsJSONFilename'];
		$GLOBALS['productionMediaFolder'] = $defaultConfig['productionMediaFolder'];
		$GLOBALS['productionMediaURL'] = $defaultConfig['productionMediaURL'];
		$GLOBALS['productionJSONFolder'] = $defaultConfig['productionJSONFolder'];
		$GLOBALS['ftpUploadJSON'] = $defaultConfig['ftpUploadJSON'];
		$GLOBALS['ftpUploadJSONFolder'] = $defaultConfig['ftpUploadJSONFolder'];
		$GLOBALS['ftpUploadMedia'] = $defaultConfig['ftpUploadMedia'];
		$GLOBALS['ftpUploadMediaFolder'] = $defaultConfig['ftpUploadMediaFolder'];
		$GLOBALS['ftpServer'] = $defaultConfig['ftpServer'];
		$GLOBALS['ftpLoginID'] = $defaultConfig['ftpLoginID'];
		$GLOBALS['ftpPassword'] = $defaultConfig['ftpPassword'];
		file_put_contents('sqraper_config.json', json_encode($defaultConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK), LOCK_EX);
	} else {		
		echo "\e[1;32mREAD CONFIG:\e[0m sqraper_config.json.\n";		
		$currentConfig = @file_get_contents('sqraper_config.json');	
		if (!$currentConfig) {		
			displayError("getConfig unable to read file contents. Halting.");
			exit;
		} else {
			$currentConfigJSON = json_decode($currentConfig, true);
			if ($currentConfigJSON == FALSE) {
				displayError("getConfig unable to parse JSON. Halting.");
				exit;
			} else {
				$GLOBALS['qTrip'] = $currentConfigJSON['qTrip'];
				$GLOBALS['boards'] = $currentConfigJSON['boards'];
				$GLOBALS['domain8Kun'] = $currentConfigJSON['domain8Kun'];
				$GLOBALS['domain8KunForLinks'] = $currentConfigJSON['domain8KunForLinks'];
				$GLOBALS['lokiKun'] = $currentConfigJSON['lokiKun'];
				$GLOBALS['useLoki'] = $currentConfigJSON['useLoki'];
				$GLOBALS['saveRemoteFilesToLocal'] = $currentConfigJSON['saveRemoteFilesToLocal'];
				$GLOBALS['readFromLocal8KunFiles'] = $currentConfigJSON['readFromLocal8KunFiles'];
				$GLOBALS['sleepBetweenNewQPostChecks'] = $currentConfigJSON['sleepBetweenNewQPostChecks'];
				$GLOBALS['productionPostsJSONFilename'] = $currentConfigJSON['productionPostsJSONFilename'];	
				$GLOBALS['productionMediaFolder'] = $currentConfigJSON['productionMediaFolder'];
				$GLOBALS['productionMediaURL'] = $currentConfigJSON['productionMediaURL'];
				$GLOBALS['productionJSONFolder'] = $currentConfigJSON['productionJSONFolder'];								
				$GLOBALS['ftpUploadJSON'] = $currentConfigJSON['ftpUploadJSON'];
				$GLOBALS['ftpUploadJSONFolder'] = $currentConfigJSON['ftpUploadJSONFolder'];
				$GLOBALS['ftpUploadMedia'] = $currentConfigJSON['ftpUploadMedia'];
				$GLOBALS['ftpUploadMediaFolder'] = $currentConfigJSON['ftpUploadMediaFolder'];
				$GLOBALS['ftpServer'] = $currentConfigJSON['ftpServer'];
				$GLOBALS['ftpLoginID'] = $currentConfigJSON['ftpLoginID'];
				$GLOBALS['ftpPassword'] = $currentConfigJSON['ftpPassword'];
			}
		}
	}		
}

getConfig();

/* ================================================ */
/* = Download and save the latest posts.json file = */
/* ================================================ */

$jsonUrl = "https://qalerts.app/data/json/posts.json";

/*
https://qanon.pub/data/json/posts.json
https://keybase.pub/qntmpkts/data/json/posts.json
https://qalerts.app/data/json/posts.json
*/

if (!file_exists($productionJSONFolder)) {
	echo "\e[1;31mCREATE FOLDER:\e[0m " . $productionJSONFolder . "\n";
	mkdir($productionJSONFolder, 0777, true);
}
echo "\e[1;32mDOWNLOAD JSON:\e[0m " . $productionJSONFolder . "posts.json" . "\n";
$thisMedia = @file_get_contents($jsonUrl);
if ($thisMedia) {
	file_put_contents($productionJSONFolder . "posts.json", $thisMedia, LOCK_EX);	
}	

/* ================================================ */


/* ===============================================+= */
/* = Download and save the latest media collection = */
/* ===============================================+= */

$mediaUrl = "https://qalerts.net/media/";
$thisMedia = @file_get_contents($mediaUrl);
if (!file_exists($productionMediaFolder)) {
	echo "\e[1;31mCREATE FOLDER:\e[0m " . $productionMediaFolder . "\n";
	mkdir($productionMediaFolder, 0777, true);
}
$jsonMedia = @json_decode($thisMedia, true);
if (($jsonMedia == FALSE) && (json_last_error() !== JSON_ERROR_NONE)) {
	displayError("JSON parse error");
} else {					
	if (!empty($jsonMedia)) {
		foreach($jsonMedia as $media) {					
			if (!file_exists($productionMediaFolder . basename($media))) {
				echo "\e[1;32mDOWNLOAD MEDIA:\e[0m " . $productionMediaFolder . basename($media) . "\n";
				$thisFile = @file_get_contents($media);
				file_put_contents($productionMediaFolder . basename($media), $thisFile, LOCK_EX);	
			} else {
				echo "\e[1;33mSKIP EXISTING MEDIA:\e[0m " . $productionMediaFolder . basename($media) . "\n";			
			}
		}
	}
	unset($jsonMedia);		
}

/* ===============================================+= */

echo "Finished\n";
?>
