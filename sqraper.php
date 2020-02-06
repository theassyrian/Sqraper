<?php

/*

Sqraper
Version: 2.1.2
Last Updated: February 6, 2020
Author: DevAnon from QAlerts.app
Email: qalertsapp@gmail.com

In order to have previous posts, before starting the sqraper for the 1st time, get a posts.json file from one of the following sources
and place it in the folder you configure for your "productionJSONFolder" configuration variable. If you have configured something other
than "posts.json" for the "productionPostsJSONFilename" configuration variable then rename the JSON file that name.

https://qanon.pub/data/json/posts.json
https://keybase.pub/qntmpkts/data/json/posts.json
https://qalerts.app/data/json/posts.json

You will also more than likely need to grab all of the images contained in posts thus far from a site operator who already has them all, or you can find them here:

https://keybase.pub/qntmpkts/data/media/

Special thanks to "qntmpkts" for all of his work compiling posts since the very beginning! He has been a pioneer in the movement to say the least. 

As of the writing of this, you will have to install LOKINET from https://loki.network/ and run this script with "lokiKun" set to true. This is because
8kun DDoS protection is blocking scripts.

The first time you run the script it will create the configuration file "sqraper_config.json" in the same folder as sqraper.php lives in.
You can then edit the file to set your configuration. Once the sqraper is running you can change "sqraper_config.json" anytime to make
config changes as the config file is re-read at the end of each loop.

*/

/* ============================= */
/* ======= Configuration ======= */
/* ============================= */

$scriptTitle = "Sqraper";
$scriptVersion = "2.1.2";
$scriptUpdated = "Last Updated: February 6, 2020";
$scriptAuthor = "DevAnon from QAlerts.app";
$scriptAuthorEmail = "qalertsapp@gmail.com";

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $GLOBALS['isWindows'] = true;
} else {
    $GLOBALS['isWindows'] = false;
}

if (file_exists('sqraperextra.php')) {
	include ("sqraperextra.php");
}

date_default_timezone_set("America/New_York");
getConfig();


/* DEBUG - Only works with threads less than about a day old that are still within the catalog.json file from 8kun */
$debugWithAPost = false;
$debugThreadNo = 7356269;
$debugPostNo = 7356331;
/* DEBUG */


/* ============================= */


/* ============================= */
/* ========= Variables ========= */
/* ============================= */

$GLOBALS['postsAddedThisLoop'] = 0;
$newlyAddedQPosts = [];
$continue = true;
$threadMap = [];
$newQSinceStart = 0;
$sqraperStarted  = date('m/d/Y h:i:s a', time());

/* ============================= */


/* ============================= */
/* ========= Functions ========= */
/* ============================= */

function displayError($errDescription) {
	
	echo "\n" . $GLOBALS['fgRed'] . ">>>" . $GLOBALS['colorEnd'] . " ========================================\n";
	echo $GLOBALS['fgRed'] . ">>>" . $GLOBALS['colorEnd'] . " ERROR: $errDescription\n";			
	echo $GLOBALS['fgRed'] . ">>>" . $GLOBALS['colorEnd'] . " SLEEP: 2 seconds.\n";			
	echo $GLOBALS['fgRed'] . ">>>" . $GLOBALS['colorEnd'] . " ========================================\n\n";		
	sleep(2);
	
}

function mask ($str, $start = 0, $length = null) {
	$mask = preg_replace ( "/\S/", "*", $str );
    if( is_null ( $length )) {
		$mask = substr ( $mask, $start );
        $str = substr_replace ( $str, $mask, $start );
    } else {
        $mask = substr ( $mask, $start, $length );
        $str = substr_replace ( $str, $mask, $start, $length );
    }
    return $str;
}

function createBlueBGText($text) {
	$l = strlen($text);	
	$a = (60 - $l);
	return $GLOBALS['fgGreyBGBlue'] . $text . str_repeat(' ', $a) . $GLOBALS['colorEnd'];
}

function uploadViaFTP($localFile, $remoteFile, $isMedia) {

	if (!$GLOBALS['isWindows']) {
		$curlFilename = "curlScriptTemp.sh";
	} else {
		// For Windows, make sure you have downloaded cURL, and that curl.exe is in your path!
		$curlFilename = "curlScriptTemp.bat";			
	}

	if (isset($GLOBALS['ftpServers'])) {
		foreach($GLOBALS['ftpServers'] as $ftpServer) {															

			if ((($isMedia) && ($ftpServer['uploadMedia'])) || ((!$isMedia) && ($ftpServer['uploadJSON']))) {

				if ($isMedia) {
					$dataType = FTP_BINARY;
					$localFilePath = $GLOBALS['productionMediaFolder'] . $localFile;
					$remoteFilePath = $ftpServer['mediaFolder'] . $remoteFile;
				} else {
					$dataType = FTP_ASCII;
					$localFilePath = $GLOBALS['productionJSONFolder'] . $localFile;
					$remoteFilePath = $ftpServer['jsonFolder'] . $remoteFile;
				}

				if (($ftpServer['useCurl']) || ($GLOBALS['useTor'])) {

					/*
					" || ($GLOBALS['useTor'])" is above because:
					We have to spawn a shell outside of the TorSock and use cURL (or something else via a shell),
					otherwise, FTP will send ONE file and will then error out beyond that. Something to do with Tor.
					*/

					echo $GLOBALS['fgGreen'] . "--- CURL UPLOAD: " . $localFilePath . ' > ' . $remoteFilePath . "." . $GLOBALS['colorEnd'] . "\n";				
					
					$curlScriptContent = "curl " . $ftpServer['curlExtraParameters'] . " -u " . $ftpServer['loginId'] . ":" . $ftpServer['password'] . " -T " . $localFilePath . " " . $ftpServer['protocol'] . "://" . $ftpServer['server'] . $remoteFilePath;
					echo $GLOBALS['fgGreen'] . "--- WRITE: $curlFilename." . $GLOBALS['colorEnd'] . "\n";				
					file_put_contents($curlFilename, $curlScriptContent, LOCK_EX);		
					echo $GLOBALS['fgGreen'] . "--- EXEC." . $GLOBALS['colorEnd'] . "\n";				
					if (!$GLOBALS['isWindows']) {
						echo $GLOBALS['fgGreen'] . "--- CHMOD." . $GLOBALS['colorEnd'] . "\n";				
						chmod($curlFilename, 0777);
						echo shell_exec("./" . $curlFilename);
					} else {
						shell_exec($curlFilename);
					}

				} else {

					echo $GLOBALS['fgGreen'] . "--- FTP CONNECT." . $GLOBALS['colorEnd'] . "\n";	
					$ftpConnection = ftp_connect($ftpServer['server']);
					$login_result = ftp_login($ftpConnection, $ftpServer['loginId'], $ftpServer['password']);
					if ($GLOBALS['useTor']) {
						// Tor requires PASV mode for outbound FTP. When launching Sqraper with "torsocks php ~/Sqraper/sqraper.php"
						// you also need to include --passive-ftp at the end. Example: "torsocks php ~/Sqraper/sqraper.php --passive-ftp"
						echo $GLOBALS['fgGreen'] . "--- FTP PASV." . $GLOBALS['colorEnd'] . "\n";	
						ftp_pasv($ftpConnection, true); 
					}

					echo $GLOBALS['fgGreen'] . "--- FTP PUT: " . $localFilePath . ' > ' . $remoteFilePath . "." . $GLOBALS['colorEnd'] . "\n";

					if (ftp_put($ftpConnection, $remoteFilePath, $localFilePath, $dataType)) {
						echo $GLOBALS['fgGreen'] . "--- FTP PUT SUCCESS: " . $localFilePath . ' > ' . $remoteFilePath . "." . $GLOBALS['colorEnd'] . "\n";
					} else {
						$last_error = error_get_last();
						echo $GLOBALS['fgRed'] . "--- FTP PUT FAILED: " . $localFilePath . ' > ' . $remoteFilePath . " " . $last_error['message'] . "." . $GLOBALS['colorEnd'] . "\n";
					}

					echo $GLOBALS['fgGreen'] . "--- FTP CLOSE." . $GLOBALS['colorEnd'] . "\n";	
					ftp_close($ftpConnection);	
					
				}
				
			}						
		
		}
	}
	
}

function assignColors($useColors) {

	if ($useColors == true) {

		$GLOBALS['fgGrey'] = "\e[1;30m";
		$GLOBALS['fgRed'] = "\e[1;31m";
		$GLOBALS['fgGreen'] = "\e[1;32m";
		$GLOBALS['fgYellow'] = "\e[1;33m";
		$GLOBALS['fgBlue'] = "\e[1;34m";
		$GLOBALS['fgGreyBGRed'] = "\e[0;37;41m";
		$GLOBALS['fgGreyBGBlue'] = "\e[0;37;44m";
		$GLOBALS['fgGreyBGGreen'] = "\e[0;37;42m";
		$GLOBALS['colorEnd'] = "\e[0m";		
		
	} else {

		$GLOBALS['fgGrey'] = "";
		$GLOBALS['fgRed'] = "";
		$GLOBALS['fgGreen'] = "";
		$GLOBALS['fgYellow'] = "";
		$GLOBALS['fgBlue'] = "";
		$GLOBALS['fgGreyBGRed'] = "";
		$GLOBALS['fgGreyBGBlue'] = "";
		$GLOBALS['fgGreyBGGreen'] = "";
		$GLOBALS['colorEnd'] = "";
		
	}	
	
}

function getConfig() {

	if (!file_exists('sqraper_config.json')) {

		assignColors(true);

		echo $GLOBALS['fgRed'] . "CREATE FILE:" . $GLOBALS['colorEnd'] . " sqraper_config.json did not exist. Creating and reading default configuration JSON file.\n";
		$defaultConfig = array(
			'qTrips' => ['!!mG7VJxZNCI','!!Hs1Jq13jV6'],
			'bogusTrips' => [],
			'boards' => ['projectdcomms','qresearch'],
			'domain8Kun' => '8kun.top',
			'domain8KunForLinks' => '8kun.net',
			'useLoki' => true,
			'lokiKun' => 'http://pijdty5otm38tdex6kkh51dkbkegf31dqgryryz3s3tys8wdegxo.loki',
			'useTor' => false,
			'torKun' => 'http://www.jthnx5wyvjvzsxtu.onion',
			'saveRemoteFilesToLocal' => true,
			'readFromLocal8KunFiles' => false,
			'sleepBetweenNewQPostChecks' => 150,
			'offPeakSleepBetweenNewQPostChecks' => 300,
			'maxDownloadAttempts' => 10,
			'pauseBetweenDownloadAttempts' => 1,			
			'productionPostsJSONFilename' => 'posts.json',
			'productionJSONFolder' => 'json/',
			'productionMediaFolder' => 'media/',
			'productionMediaURL' => 'https://yourserver.com/media/', // If not blank, the media URL in the file will be build with this domain and path.
			'ftpServers' => [],
			'useColors' => true
		);		
		
		array_push($defaultConfig[ftpServers], array('protocol' => 'ftp','server' => 'ftp.yourserver.com','loginId' => 'your_user_name', 'password' => 'your_password', 'uploadJSON' => false, 'uploadMedia' => false, 'jsonFolder' => '/data/json/', 'mediaFolder' => '/media/', 'useCurl' => false, 'curlExtraParameters' => '--insecure'));
		array_push($defaultConfig[ftpServers], array('protocol' => 'ftp','server' => 'ftp.yourserver2.com','loginId' => 'your_user_name2', 'password' => 'your_password2', 'uploadJSON' => false, 'uploadMedia' => false, 'jsonFolder' => '/data/json/', 'mediaFolder' => '/media/', 'useCurl' => false, 'curlExtraParameters' => '--insecure'));
		
		$GLOBALS['qTrips'] = $defaultConfig['qTrips'];
		$GLOBALS['bogusTrips'] = $defaultConfig['bogusTrips'];
		$GLOBALS['boards'] = $defaultConfig['boards'];
		$GLOBALS['domain8Kun'] = $defaultConfig['domain8Kun'];
		$GLOBALS['domain8KunForLinks'] = $defaultConfig['domain8KunForLinks'];
		$GLOBALS['lokiKun'] = $defaultConfig['lokiKun'];
		$GLOBALS['torKun'] = $defaultConfig['torKun'];
		$GLOBALS['useLoki'] = $defaultConfig['useLoki'];
		$GLOBALS['useTor'] = $defaultConfig['useTor'];
		$GLOBALS['saveRemoteFilesToLocal'] = $defaultConfig['saveRemoteFilesToLocal'];
		$GLOBALS['readFromLocal8KunFiles'] = $defaultConfig['readFromLocal8KunFiles'];
		$GLOBALS['sleepBetweenNewQPostChecks'] = $defaultConfig['sleepBetweenNewQPostChecks'];
		$GLOBALS['offPeakSleepBetweenNewQPostChecks'] = $defaultConfig['offPeakSleepBetweenNewQPostChecks'];		
		$GLOBALS['maxDownloadAttempts'] = $defaultConfig['maxDownloadAttempts'];		
		$GLOBALS['pauseBetweenDownloadAttempts'] = $defaultConfig['pauseBetweenDownloadAttempts'];		
		$GLOBALS['productionPostsJSONFilename'] = $defaultConfig['productionPostsJSONFilename'];
		$GLOBALS['productionMediaFolder'] = $defaultConfig['productionMediaFolder'];
		$GLOBALS['productionMediaURL'] = $defaultConfig['productionMediaURL'];
		$GLOBALS['productionJSONFolder'] = $defaultConfig['productionJSONFolder'];
		$GLOBALS['ftpServers'] = $defaultConfig['ftpServers'];		
		$GLOBALS['useColors'] = $defaultConfig['useColors'];
		file_put_contents('sqraper_config.json', json_encode($defaultConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK), LOCK_EX);
						
	} else {

		if (!isset($GLOBALS['useColors'])) {
			assignColors(true);
		}
		
		echo $GLOBALS['fgGreen'] . "READ CONFIG:" . $GLOBALS['colorEnd'] . " sqraper_config.json.\n";
		
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
				
				$GLOBALS['qTrips'] = $currentConfigJSON['qTrips'];
				$GLOBALS['bogusTrips'] = $currentConfigJSON['bogusTrips'];
				$GLOBALS['boards'] = $currentConfigJSON['boards'];
				$GLOBALS['domain8Kun'] = $currentConfigJSON['domain8Kun'];
				$GLOBALS['domain8KunForLinks'] = $currentConfigJSON['domain8KunForLinks'];
				$GLOBALS['lokiKun'] = $currentConfigJSON['lokiKun'];
				$GLOBALS['torKun'] = $currentConfigJSON['torKun'];
				$GLOBALS['useLoki'] = $currentConfigJSON['useLoki'];
				$GLOBALS['useTor'] = $currentConfigJSON['useTor'];
				$GLOBALS['saveRemoteFilesToLocal'] = $currentConfigJSON['saveRemoteFilesToLocal'];
				$GLOBALS['readFromLocal8KunFiles'] = $currentConfigJSON['readFromLocal8KunFiles'];
				$GLOBALS['sleepBetweenNewQPostChecks'] = $currentConfigJSON['sleepBetweenNewQPostChecks'];
				$GLOBALS['offPeakSleepBetweenNewQPostChecks'] = $currentConfigJSON['offPeakSleepBetweenNewQPostChecks'];

				if (!isset($currentConfigJSON['maxDownloadAttempts'])) {
					$GLOBALS['maxDownloadAttempts'] = 10;
				} else {
					$GLOBALS['maxDownloadAttempts'] = $currentConfigJSON['maxDownloadAttempts'];					
				}

				if (!isset($currentConfigJSON['pauseBetweenDownloadAttempts'])) {
					$GLOBALS['pauseBetweenDownloadAttempts'] = 1;				
				} else {
					$GLOBALS['pauseBetweenDownloadAttempts'] = $currentConfigJSON['pauseBetweenDownloadAttempts'];					
				}
				
				$GLOBALS['productionPostsJSONFilename'] = $currentConfigJSON['productionPostsJSONFilename'];	
				$GLOBALS['productionMediaFolder'] = $currentConfigJSON['productionMediaFolder'];
				$GLOBALS['productionMediaURL'] = $currentConfigJSON['productionMediaURL'];
				$GLOBALS['productionJSONFolder'] = $currentConfigJSON['productionJSONFolder'];								

				if (!isset($currentConfigJSON['ftpServers'])) {
					$GLOBALS['ftpServers'] = [];
				} else {
					$GLOBALS['ftpServers'] = $currentConfigJSON['ftpServers'];
				}

				if (!isset($currentConfigJSON['useColors'])) {
					$GLOBALS['useColors'] = true;					
				} else {
					$GLOBALS['useColors'] = $currentConfigJSON['useColors'];					
				}

				assignColors($GLOBALS['useColors']);
								
			}
		}

	}

}

function cleanHtmlText($htmlText) {
		
	$htmlText = preg_replace('#<a onclick=\"highlightReply.*?>(.*?)</a>#i', '${1}', $htmlText);
	$htmlText = str_replace(' rel="nofollow" target="_blank"', '', $htmlText);	
	
	$htmlText = preg_replace('#<a.*?>(.*?)</a>#i', '\1', $htmlText);	
	
	$htmlText = str_replace('<p class="body-line empty">', '', $htmlText);
	$htmlText = str_replace('<p class="body-line empty ">', '', $htmlText);

	$htmlText = str_replace('<p class="body-line ltr quote">', '', $htmlText);
	$htmlText = str_replace('<p class="body-line ltr quote ">', '', $htmlText);

	$htmlText = str_replace('<p class="body-line ltr ">', '', $htmlText);
	$htmlText = str_replace('</p>', "\n", $htmlText);	
		
	if (file_exists('search_replace.json')) {	
		
		// Example: [{"replace":"&hellip;","with":"..."},{"replace":"&ndash;","with":"-"}]
		
		$searchReplace = @file_get_contents('search_replace.json');
		if (!$searchReplace) {		
			displayError("!searchReplace.");
		} else {
			$searchReplaceJSON = json_decode($searchReplace, true);
			if ($searchReplaceJSON == FALSE) {
				displayError("searchReplaceJSON parse error.");
			} else {
				foreach($searchReplaceJSON as $sr) {					
					if (isset($sr['replace']) && isset($sr['with'])) {
						$htmlText = str_replace($sr['replace'], $sr['with'], $htmlText);
					}
				}						
			}
		}	
	}	
	
	return $htmlText;
		
}

function getReferencedPostNumbers($searchStr) {
	
	// index 0 is the content as it matched with the >>
	// index 1 is the content without the >>
	// Use print_r($matches); to see the array
	
	$findReferencesPattern = "~>>(\d+)~is";
	preg_match_all($findReferencesPattern, cleanHtmlText($searchStr), $matches, PREG_PATTERN_ORDER);
	return $matches;
	
}

function downloadMediaFile($thisUrl, $thisStorageFilename) {

	if ((isset($GLOBALS['productionMediaFolder'])) && ($GLOBALS['productionMediaFolder'] != '')) {

		if (file_exists($GLOBALS['productionMediaFolder'] . $thisStorageFilename)) {

			echo $GLOBALS['fgYellow'] . "--- SKIP DOWNLOAD MEDIA (ALREADY EXISTS): \n    " . $thisUrl . " > " . $GLOBALS['productionMediaFolder'] . $thisStorageFilename . $GLOBALS['colorEnd'] . "\n";	
			
		} else {

			$currentDownloadAttempt = 1;			
			do {
				if ($currentDownloadAttempt > 1) {
					echo $GLOBALS['fgYellow'] . "--- DOWNLOAD MEDIA: " . $thisUrl . " > " . $GLOBALS['productionMediaFolder'] . $thisStorageFilename . " Attempt $currentDownloadAttempt of " . $GLOBALS['maxDownloadAttempts'] . $GLOBALS['colorEnd'] . "\n";
				} else {
					echo $GLOBALS['fgGreen'] . "--- DOWNLOAD MEDIA: " . $thisUrl . " > " . $GLOBALS['productionMediaFolder'] . $thisStorageFilename . $GLOBALS['colorEnd'] . "\n";
				}				
				$thisMedia = @file_get_contents($thisUrl);
				if (!$thisMedia) {
					sleep($GLOBALS['pauseBetweenDownloadAttempts']);
				}
				$currentDownloadAttempt++;
			}
			while ( ($thisMedia === false) && ($currentDownloadAttempt <= $GLOBALS['maxDownloadAttempts']) );
			
			if (!$thisMedia) {				
				displayError("Could not get media from URL \"$thisUrl");				
			} else {
				if (!file_exists($GLOBALS['productionMediaFolder'])) {
					echo $GLOBALS['fgRed'] . "CREATE FOLDER:" . $GLOBALS['colorEnd'] . " " . $GLOBALS['productionMediaFolder'] . "\n";
					mkdir($GLOBALS['productionMediaFolder'], 0777, true);
				}
				file_put_contents($GLOBALS['productionMediaFolder'] . $thisStorageFilename, $thisMedia, LOCK_EX);
				unset($thisMedia);
				uploadViaFTP($thisStorageFilename, $thisStorageFilename, true);
			}
			
		}
		
	}

}

function getMediaObject($inArray) {

	$returnArray = [];
	if ((isset($inArray['filename'])) && (isset($inArray['ext'])) && (isset($inArray['tim']))){			
		
		if ((isset($GLOBALS['productionMediaURL'])) && ($GLOBALS['productionMediaURL'] != '')) {
			$thisUrl = $GLOBALS['productionMediaURL'] . $inArray['tim'] . $inArray['ext'];
		} else {
			$thisUrl = "https://media." . $GLOBALS['domain8KunForLinks'] . "/file_store/" . $inArray['tim'] . $inArray['ext'];			
		}		
		
		$thisFilename = $inArray['filename'] . $inArray['ext'];
		$thisStorageFilename = $inArray['tim'] . $inArray['ext'];
		$thisMedia = array(
			'filename' => $thisFilename,
			'url' => $thisUrl
		);
		array_push($returnArray, $thisMedia);		
		
		if (($GLOBALS['useLoki']) || ($GLOBALS['useTor'])) {			
			if ($GLOBALS['useLoki']) {
				$thisDownload = "http://media." . str_replace("http://", "", $GLOBALS['lokiKun']) . "/file_store/" . $inArray['tim'] . $inArray['ext'];
				downloadMediaFile($thisDownload, $thisStorageFilename);				
			}
			if ($GLOBALS['useTor']) {
				$thisDownload = "http://media." . str_replace("http://www.", "", $GLOBALS['torKun']) . "/file_store/" . $inArray['tim'] . $inArray['ext'];
				downloadMediaFile($thisDownload, $thisStorageFilename);				
			}			
		} else {
			$thisDownload = "https://media." . $GLOBALS['domain8Kun'] . "/file_store/" . $inArray['tim'] . $inArray['ext'];
			downloadMediaFile($thisDownload, $thisStorageFilename);
		}	
		
		if (isset($inArray['extra_files'])) {
			foreach($inArray['extra_files'] as $extraFile) {															
				if ((isset($extraFile['filename'])) && (isset($extraFile['ext'])) && (isset($extraFile['tim']))){																
					if ((isset($GLOBALS['productionMediaURL'])) && ($GLOBALS['productionMediaURL'] != '')) {
						$thisUrl = $GLOBALS['productionMediaURL'] . $extraFile['tim'] . $extraFile['ext'];
					} else {
						$thisUrl = "https://media." . $GLOBALS['domain8KunForLinks'] . "/file_store/" . $extraFile['tim'] . $extraFile['ext'];			
					}		
					$thisFilename = $extraFile['filename'] . $extraFile['ext'];
					$thisStorageFilename = $extraFile['tim'] . $extraFile['ext'];
					$thisMedia = array(
						'filename' => $thisFilename,
						'url' => $thisUrl
					);
					array_push($returnArray, $thisMedia);					

					if (($GLOBALS['useLoki']) || ($GLOBALS['useTor'])) {
						if ($GLOBALS['useLoki']) {
							$thisDownload = "http://media." . str_replace("http://", "", $GLOBALS['lokiKun']) . "/file_store/" . $extraFile['tim'] . $extraFile['ext'];
							downloadMediaFile($thisDownload, $thisStorageFilename);
						}
						if ($GLOBALS['useTor']) {
							$thisDownload = "http://media." . str_replace("http://www.", "", $GLOBALS['torKun']) . "/file_store/" . $extraFile['tim'] . $extraFile['ext'];
							downloadMediaFile($thisDownload, $thisStorageFilename);
						}						
					} else {
						$thisDownload = "https://media." . $GLOBALS['domain8Kun'] . "/file_store/" . $extraFile['tim'] . $extraFile['ext'];
						downloadMediaFile($thisDownload, $thisStorageFilename);
					}					
				}
			}
		}
	}
	return $returnArray;
}

function getReferencesObject($searchStr, $digDeeper) {

	// index 0 is the content as it matched with the >>
	// index 1 is the content without the >>
	// Use print_r($matches); to see the array
	
	$findReferencesPattern = "~&gt;&gt;(\d+)~is";
	preg_match_all($findReferencesPattern, $searchStr, $matches, PREG_PATTERN_ORDER);

	$returnArray = [];

	if (!empty($matches)) {		
		$post_references = ['referencesONE'];
		foreach($matches[1] as $match) {
						
			$jsonThreads['posts'] = $GLOBALS['jsonThreads']['posts'];
			
			if (!empty($jsonThreads['posts'])) {
			
				foreach($jsonThreads['posts'] as $postReference) { // Loop through all of the posts in the current thread of the current catalog.

					if ($postReference['no'] == $match) {

						echo "--------- " . $GLOBALS['fgGreen'] . "REFERENCE POST FOUND: $match" . $GLOBALS['colorEnd'] . "\n";

						if (isset($postReference['email'])) {
							$postReference_email = $postReference['email'];	
						} else {
							$postReference_email = null;
						}
						if (isset($postReference['subject'])) {
							$postReference_subject = $postReference['subject'];	
						} else {
							$postReference_subject = null;
						}
						if (isset($postReference['no'])) {
							$postReference_id = $postReference['no'];	
						} else {
							$postReference_id = 0;
						}
						if (isset($postReference['resto'])) {
							$postReference_threadId = $postReference['resto'];	
						} else {
							$postReference_threadId = 0;
						}
						$postReference_link = "https://" . $GLOBALS['domain8KunForLinks'] . "/" . $GLOBALS['board'] . "/res/$postReference_threadId.html#$postReference_id";
						if (isset($postReference['name'])) {
							$postReference_name = trim($postReference['name']);	
						} else {
							$postReference_name = null;
						}
						$postReference_source = explode(".", $GLOBALS['domain8KunForLinks'])[0] . "_" . $GLOBALS['board'];

						if (isset($postReference['com'])) {
							$postReference_text = cleanHtmlText(trim($postReference['com']));	
						} else {
							$postReference_text = null;
						}
						if (isset($postReference['time'])) {
							$postReference_timestamp = $postReference['time'];	
						} else {
							$postReference_timestamp = 0;
						}
						if (isset($postReference['last_modified'])) {
							$postReference_lastModified = $postReference['last_modified'];	
						} else {
							$postReference_lastModified = 0;
						}															
						if (isset($postReference['trip'])) {
							$postReference_trip = $postReference['trip'];	
						} else {
							$postReference_trip = null;
						}
						if (isset($postReference['id'])) {
							$postReference_userId = $postReference['id'];	
						} else {
							$postReference_userId = 0;
						}

						$thisReferencesPost = array(
							'email' => $postReference_email,
							'id' => $postReference_id,
							'link' => $postReference_link,
							'name' => $postReference_name,
							'source' => $postReference_source,
							'subject' => $postReference_subject,
							'text' => $postReference_text,
							'threadId' => $postReference_threadId,
							'timestamp' => $postReference_timestamp,
							'trip' => $postReference_trip,
							'userId' => $postReference_userId
						);															
						
						$post_referencesMedia = [];	
						if ((isset($postReference['filename'])) && (isset($postReference['ext'])) && (isset($postReference['tim']))){			

							if ((isset($GLOBALS['productionMediaURL'])) && ($GLOBALS['productionMediaURL'] != '')) {
								$thisPostReferenceUrl = $GLOBALS['productionMediaURL'] . $postReference['tim'] . $postReference['ext'];
							} else {
								$thisPostReferenceUrl = "https://media." . $GLOBALS['domain8KunForLinks'] . "/file_store/" . $postReference['tim'] . $postReference['ext'];			
							}		

							$thisPostReferenceFilename = $postReference['filename'] . $postReference['ext'];

							$thisPostReferenceMedia = array(
								'filename' => $thisPostReferenceFilename,
								'url' => $thisPostReferenceUrl
							);
							array_push($post_referencesMedia, $thisPostReferenceMedia);															
							
							$thisStorageFilename = $postReference['tim'] . $postReference['ext'];
							if (($GLOBALS['useLoki']) || ($GLOBALS['useTor'])) {
								if ($GLOBALS['useLoki']) {
									$thisDownload = "http://media." . str_replace("http://", "", $GLOBALS['lokiKun']) . "/file_store/" . $postReference['tim'] . $postReference['ext'];
									downloadMediaFile($thisDownload, $thisStorageFilename);
								}
								if ($GLOBALS['useTor']) {
									$thisDownload = "http://media." . str_replace("http://www.", "", $GLOBALS['torKun']) . "/file_store/" . $postReference['tim'] . $postReference['ext'];
									downloadMediaFile($thisDownload, $thisStorageFilename);
								}								
							} else {
								$thisDownload = "https://media." . $GLOBALS['domain8Kun'] . "/file_store/" . $postReference['tim'] . $postReference['ext'];
								downloadMediaFile($thisDownload, $thisStorageFilename);
							}	

							if (isset($postReference['extra_files'])) {
								foreach($postReference['extra_files'] as $extraPostReferenceFile) {															
									if ((isset($extraPostReferenceFile['filename'])) && (isset($extraPostReferenceFile['ext'])) && (isset($extraPostReferenceFile['tim']))){
										
										if ((isset($GLOBALS['productionMediaURL'])) && ($GLOBALS['productionMediaURL'] != '')) {
											$thisPostReferenceUrl = $GLOBALS['productionMediaURL'] . $extraPostReferenceFile['tim'] . $extraPostReferenceFile['ext'];
										} else {
											$thisPostReferenceUrl = "https://media." . $GLOBALS['domain8KunForLinks'] . "/file_store/" . $extraPostReferenceFile['tim'] . $extraPostReferenceFile['ext'];			
										}		
										
										$thisPostReferenceFilename = $extraPostReferenceFile['filename'] . $extraPostReferenceFile['ext'];
										$thisPostReferenceMedia = array(
											'filename' => $thisPostReferenceFilename,
											'url' => $thisPostReferenceUrl
										);										
										array_push($post_referencesMedia, $thisPostReferenceMedia);

										$thisStorageFilename = $extraPostReferenceFile['tim'] . $extraPostReferenceFile['ext'];
										if (($GLOBALS['useLoki']) || ($GLOBALS['useTor'])) {
											if ($GLOBALS['useLoki']) {
												$thisDownload = "http://media." . str_replace("http://", "", $GLOBALS['lokiKun']) . "/file_store/" . $extraPostReferenceFile['tim'] . $extraPostReferenceFile['ext'];
												downloadMediaFile($thisDownload, $thisStorageFilename);
											}
											if ($GLOBALS['useTor']) {
												$thisDownload = "http://media." . str_replace("http://www.", "", $GLOBALS['torKun']) . "/file_store/" . $extraPostReferenceFile['tim'] . $extraPostReferenceFile['ext'];
												downloadMediaFile($thisDownload, $thisStorageFilename);
											}
										} else {
											$thisDownload = "https://media." . $GLOBALS['domain8Kun'] . "/file_store/" . $extraPostReferenceFile['tim'] . $extraPostReferenceFile['ext'];
											downloadMediaFile($thisDownload, $thisStorageFilename);
										}	

									}
								}
							}																
							$thisReferencesPost['media'] = $post_referencesMedia;
						} else {
							$thisReferencesPost['media'] = [];
						}
						
						if ($digDeeper == true) {
							$subSub_References_Result = getReferencesObject($postReference_text, true); // If you want to dig unlimited levels deep set to true
							if (!empty($subSub_References_Result)) {
								$thisReferencesPost['references'] = $subSub_References_Result;
							}							
						}
						
						array_push($returnArray, $thisReferencesPost);
						break;
					
					}
				}
				
			}
		}
		return $returnArray;
	} else {
		return $returnArray;
	}
	
}

function isInPostsJSON($threadId, $postId) {

	$entryExists = false;
	$contents = @file_get_contents($GLOBALS['productionJSONFolder'] . $GLOBALS['productionPostsJSONFilename']);	
	
	if (!$contents) {		
		displayError("isInPostsJSON threadId: $threadId, postId: $postId, File: " . $GLOBALS['productionJSONFolder'] . $GLOBALS['productionPostsJSONFilename']);
		$entryExists = true;
	} else {
		$jsonContents = @json_decode($contents, true);
		if (($jsonContents == FALSE) && (json_last_error() !== JSON_ERROR_NONE)) {
			displayError("JSON parse error in isInPostsJSON threadId: $threadId, postId: $postId, File: " . $GLOBALS['productionJSONFolder'] . $GLOBALS['productionPostsJSONFilename'] . " " . json_last_error());
			$entryExists = true;
		} else {					
			if (!empty($jsonContents[0])) {
				if ((array_key_exists("threadId", $jsonContents[0])) && (array_key_exists("id", $jsonContents[0]))) { // In the post.json format
					foreach($jsonContents as $entry) {					
						if (isset($entry['threadId']) && isset($entry['id'])) {
							if (($entry['threadId'] == $threadId) && ($entry['id'] == $postId)) {								
								if ($GLOBALS['debugWithAPost']) {
									if (($entry['threadId'] == $GLOBALS['debugThreadNo']) && ($entry['id'] == $GLOBALS['debugPostNo'])) {
										$entryExists = false;
									}								
								} else {
									$entryExists = true;
								}
								break;					
							}
						}					
					}						
				} else {
					$entryExists = false;				
				}
				
			} else {
				$entryExists = false;
			}				
			unset($jsonContents);
			
		}
	}
	
	return $entryExists;
	
}	

function hasThreadUpdated($varNo, $varLastModified) {

	$entryUpdated = false;
	$foundEntry = false;

	foreach($GLOBALS['threadMap'] as $key => $entry) {					
		if (isset($entry['no']) && isset($entry['last_modified'])) {
			if ($entry['no'] == $varNo) {
				$foundEntry = true;
				if ($entry['last_modified'] == $varLastModified) {
					$entryUpdated = false;		
				} else {

					/* 					
					Commented line below and added function setThreadUpdated since before, if
					the download failed, it would still update the thread as processed and thus
					would not catch changes until the thread changed again.					
					*/
					
					// $GLOBALS['threadMap'][$key]['last_modified'] = $varLastModified; 

					$entryUpdated = true;
				}						
				break;					
			}					
		} else {
			$entryUpdated = true;
		}			
	}

	if (($GLOBALS['debugWithAPost']) && ($varNo == $GLOBALS['debugThreadNo']))    {
		return true;
	} else {
		if (!$foundEntry) {

			/*
			Commented below and added function setThreadUpdated since before, if
			the download failed, it would still update the thread as processed and thus
			would not catch changes until the thread changed again.					
			*/

			// array_push($GLOBALS['threadMap'], array(
			// 	'no' => $varNo,
			// 	'last_modified' => $varLastModified
			// ));

			$entryUpdated = true;
		}
		return $entryUpdated;		
	}

}	

function setThreadUpdated($varNo, $varLastModified) {

	$foundEntry = false;

	foreach($GLOBALS['threadMap'] as $key => $entry) {					
		if (isset($entry['no']) && isset($entry['last_modified'])) {
			if ($entry['no'] == $varNo) {
				$foundEntry = true;
				if ($entry['last_modified'] != $varLastModified) {
					echo "--------- " . $GLOBALS['fgGreen'] . "UPDATE THREAD MAP:" . $GLOBALS['colorEnd'] . " ThreadId: $varNo, Prior last_modified: " . $GLOBALS['threadMap'][$key]['last_modified'] . ", New last_modified: $varLastModified\n";
					$GLOBALS['threadMap'][$key]['last_modified'] = $varLastModified;
				}						
				break;					
			}					
		}			
	}

	if (($GLOBALS['debugWithAPost']) && ($varNo == $GLOBALS['debugThreadNo']))    {
		// Do nothing
	} else {
		if (!$foundEntry) {
			array_push($GLOBALS['threadMap'], array(
				'no' => $varNo,
				'last_modified' => $varLastModified
			));
		}
	}

}	


/* ============================= */


/* ============================= */
/* ======== Main Routine ======= */
/* ============================= */

/* Color codes: https://joshtronic.com/2013/09/02/how-to-use-colors-in-command-line-output */

echo "\n$fgGreyBGRed************************************************************$colorEnd\n";
echo createBlueBGText($scriptTitle) . "\n";
echo createBlueBGText("Version: " . $scriptVersion) . "\n";
echo createBlueBGText("Updated: " . $scriptUpdated) . "\n";
echo createBlueBGText("Author: " . $scriptAuthor) . "\n";
echo createBlueBGText("Email: " . $scriptAuthorEmail) . "\n";
echo "$fgGreyBGRed************************************************************$colorEnd\n";
echo "\n";

do {
	
	$noCache = time();
	
	$timeStarted  = strtotime(date('m/d/Y h:i:s a', time()));
	$GLOBALS['postsAddedThisLoop'] = 0;
	
	echo "============================================================\n";

	$strBoards = "";
	foreach($boards as $board) {	
		$strBoards = $strBoards . $board . " ";
	}

	$strQTrips = "";
	foreach($qTrips as $qTrip) {	
		$strQTrips = $strQTrips . $qTrip . " ";
	}

	$strBogusTrips = "";
	foreach($bogusTrips as $bogusTrip) {	
		$strBogusTrips = $strBogusTrips . $bogusTrip . " ";
	}

	echo $fgBlue . "Sqraper Started:" . $colorEnd . " $sqraperStarted\n";
	echo $fgBlue . "New Q Drops Since Start:" . $colorEnd . " $newQSinceStart\n";
	echo $fgBlue . "Configuration:" . $colorEnd . "\n";
	echo "   " . $fgBlue . "Trips:" . $colorEnd . " $strQTrips\n";
	echo "   " . $fgBlue . "Bogus Trips:" . $colorEnd . " $strBogusTrips\n";
	echo "   " . $fgBlue . "Boards:" . $colorEnd . " " . trim($strBoards) . "\n";

	if (file_exists('search_replace.json')) {	
		$searchReplace = @file_get_contents('search_replace.json');
		if ($searchReplace) {		
			echo "   " . $fgBlue . "msearch_replace.json:" . $colorEnd . " " . trim($searchReplace) . "\n";

		}	
	}	

	echo "   " . $fgBlue . "Internet Domain:" . $colorEnd . " $domain8Kun\n";
	echo "   " . $fgBlue . "Internet Domain for Links in JSON:" . $colorEnd . " $domain8KunForLinks\n";
	echo "   " . $fgBlue . "Use Tor Network:" . $colorEnd . " $useTor\n";
	echo "   " . $fgBlue . "Tor Network Address:" . $colorEnd . " $torKun\n";
	echo "   " . $fgBlue . "Use Loki.Network:" . $colorEnd . " $useLoki\n";
	echo "   " . $fgBlue . "Loki.Network Address:" . $colorEnd . " $lokiKun\n";
	echo "   " . $fgBlue . "Production JSON Filename:" . $colorEnd . " $productionPostsJSONFilename\n";
	echo "   " . $fgBlue . "Production JSON (Local) Folder:" . $colorEnd . " $productionJSONFolder\n";
	echo "   " . $fgBlue . "Production Media (Local) Folder:" . $colorEnd . " $productionMediaFolder (if blank photos/videos will not be downloaded)\n";
	echo "   " . $fgBlue . "Production Media (Remote) URL:" . $colorEnd . " $productionMediaURL\n";
	echo "   " . $fgBlue . "Save Downloaded 8kun JSON Files To Local:" . $colorEnd . " $saveRemoteFilesToLocal\n";
	echo "   " . $fgBlue . "Read From Local 8Kun Files (for debugging/testing):" . $colorEnd . " $readFromLocal8KunFiles\n";
	echo "   " . $fgBlue . "Max Download Attempts:" . $colorEnd . " $maxDownloadAttempts\n";
	echo "   " . $fgBlue . "Pause Between Download Attempts:" . $colorEnd . " $pauseBetweenDownloadAttempts\n";
	echo "   " . $fgBlue . "Sleep Between Loops:" . $colorEnd . " $sleepBetweenNewQPostChecks\n";
	echo "   " . $fgBlue . "Off Peak Sleep Between Loops:" . $colorEnd . " $offPeakSleepBetweenNewQPostChecks\n";
	echo "   " . $fgBlue . "FTP Server(s): " . $colorEnd . json_encode($GLOBALS['ftpServers'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS) . "\n";	
	
	echo $fgBlue . "Loop Started:" . $colorEnd . " " . date("m/d/Y h:i:s a") . "\n";
	echo "============================================================\n\n";

	if ((isset($productionJSONFolder)) && ($productionJSONFolder !== '')) {
		if (!file_exists($productionJSONFolder)) {
			echo $fgRed . "CREATE FOLDER:" . $colorEnd . " $productionJSONFolder.\n";
			mkdir($productionJSONFolder, 0777, true);
		}
	}
	
	if ((isset($productionMediaFolder)) && ($GLOBALS['productionMediaFolder'] != '')) {
		if (!file_exists($productionMediaFolder)) {
			echo $fgRed . "CREATE FOLDER:" . $colorEnd . " $productionMediaFolder.\n";
			mkdir($productionMediaFolder, 0777, true);
		}
	}
	
	if (!file_exists($productionJSONFolder . $productionPostsJSONFilename)) {
		echo $fgRed . "CREATE FILE:" . $colorEnd . " $productionJSONFolder$productionPostsJSONFilename did not exist. Creating empty JSON file.\n";
		$blankPosts = array();
		file_put_contents($productionJSONFolder . $productionPostsJSONFilename, json_encode($blankPosts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK), LOCK_EX);
	}			

	foreach($boards as $board) { // Loop through all boards defined in the array in the configuration section at the top of the page.
		
		
		// echo getcwd();
		/*
		You can add a ^ to the end of a board name in the config file sqraper_config.json to allow searching for Q posts that do not
		have a trip. Used on Q private boards. CAUTION: Do NOT attempt this on a public board!
		*/
		if (strpos($board, "^") !== false) {
			$includeNoTripPosts = true;
			$board = str_replace('^', '', $board);	
		} else {
			$includeNoTripPosts = false;
		}
		/************************************************************/	
		
		$foundAnyNewPosts = false;		
		
		$threadMap = [];
		$threadMapFile = $productionJSONFolder . $board . '_checked_threads.json';
		
		if (!file_exists($threadMapFile)) {
			echo $fgRed . "CREATE FILE:" . $colorEnd . " $threadMapFile did not exist. Creating empty JSON file.\n";
			$threadMap = array();			
			array_push($threadMap, array(
				'no' => 0,
				'last_modified' => 0
			));			
			file_put_contents($threadMapFile, json_encode($threadMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK), LOCK_EX);
		}			
		echo $fgGreen . "READ:" . $colorEnd . " $threadMapFile.\n";		
		$threadMapContent = @file_get_contents($threadMapFile);			
				
		if (!$threadMapContent) {		
			displayError("!threadMapContent.");
		} else {
			$threadMap = json_decode($threadMapContent, true);
			if ($threadMap == FALSE) {
				displayError("threadMap JSON parse error.");
				return true;
			} else {
		
			}
		}

		if ($readFromLocal8KunFiles) {
			if (!file_exists($productionJSONFolder . $board)) {
				echo $fgGreen . "CREATE FOLDER:" . $colorEnd . " $productionJSONFolder$board.\n";
				mkdir($productionJSONFolder . $board, 0777, true);
			}
			if (file_exists($productionJSONFolder . $board . "/catalog.json")) {
				$boardCatalogUrl = $productionJSONFolder . $board . "/catalog.json";					
			} else {
				if (($useLoki) || ($useTor)) {
					if ($useLoki) {
						$boardCatalogUrl = "$lokiKun/$board/catalog.json";
					}
					if ($useTor) {
						$boardCatalogUrl = "$torKun/$board/catalog.json";
					}
				} else {
					$boardCatalogUrl = "https://$domain8Kun/$board/catalog.json";		
				}				
			}				
		} else {
			if (($useLoki) || ($useTor)) {
				if ($useLoki) {
					$boardCatalogUrl = "$lokiKun/$board/catalog.json";
				}
				if ($useTor) {
					$boardCatalogUrl = "$torKun/$board/catalog.json";
				}
			} else {
				$boardCatalogUrl = "https://$domain8Kun/$board/catalog.json";		
			}
		}
		
		$currentDownloadAttempt = 1;			
		do {
			if ($currentDownloadAttempt > 1) {
				echo $fgYellow . "DOWNLOAD:" . $colorEnd . " $boardCatalogUrl?sqraper_nocache=" . $noCache . ". Attempt $currentDownloadAttempt of $maxDownloadAttempts\n";
			} else {
				echo $fgGreen . "DOWNLOAD:" . $colorEnd . " $boardCatalogUrl?sqraper_nocache=" . $noCache . ".\n";
			}				
			$boardCatalogContents = @file_get_contents($boardCatalogUrl . "?sqraper_nocache=" . $noCache);
			if (!$boardCatalogContents) {
				sleep($pauseBetweenDownloadAttempts);
			}
			$currentDownloadAttempt++;
		}
		while ( ($boardCatalogContents === false) && ($currentDownloadAttempt <= $maxDownloadAttempts) );

		if (!$boardCatalogContents) {
			
			displayError("Could not get catalog for board \"$board\", URL \"$boardCatalogUrl");
			
		} else {

			if ($saveRemoteFilesToLocal) {
				if (!file_exists($productionJSONFolder . $board)) {
					echo $fgRed . "CREATE FOLDER:" . $colorEnd . " $productionJSONFolder$board.\n";
					mkdir($productionJSONFolder . $board, 0777, true);
				}
				echo $fgGreen . "WRITE:" . $colorEnd . " " . $productionJSONFolder . $board . '/' . basename($boardCatalogUrl) . ".\n";
				file_put_contents($productionJSONFolder . $board . '/' . basename($boardCatalogUrl), $boardCatalogContents, LOCK_EX);
			}
			
			echo "--- " . $fgGreen . "JSON DECODE." . $colorEnd . "\n";
			$jsonBoardCatalog = json_decode($boardCatalogContents, true);

			if ($jsonBoardCatalog == FALSE) {

				displayError("jsonBoardCatalog parse error.");
				break;

			} else {

				foreach($jsonBoardCatalog as $pages) { // Loop through all of the pages in the catalog.

					$page = $pages['page'];
					
					echo "--- " . $fgGreen . "PARSE:" . $colorEnd . " Page $page.\n";
					
					if (!empty($pages['threads'])) {
						
						$threads = $pages['threads'];

						foreach($threads as $thread) { // Loop through all of the threads in the current page of the catalog.
							
							$threadNo = $thread['no'];
							
							if (isset($thread['last_modified'])) {
								$threadLastModified = $thread['last_modified'];	
							} else {
								$threadLastModified = 0;
							}
							
							// This is where we check the thread dates and then conditionally continue.	
							if (hasThreadUpdated($threadNo, $threadLastModified)) {

								echo "------ " . $fgGreen . "Thread No" . $colorEnd . " $threadNo, " . $fgGreen . "Last Modified" . $colorEnd . " " . date("M d, Y g:i:s A", $threadLastModified) . " ($threadLastModified).\n";						
								echo "--------- " . $fgGreen . "Thread HAS Changed." . $colorEnd . "\n";

								$threadUrl = "https://$domain8Kun/$board/res/$threadNo.json";

								$threadNo = $thread['no'];

								if ($readFromLocal8KunFiles) {
									if (!file_exists($productionJSONFolder . $board)) {
										echo $fgRed . "CREATE FOLDER:" . $colorEnd . " $productionJSONFolder$board.\n";
										mkdir($productionJSONFolder . $board, 0777, true);
									}
									if (file_exists($productionJSONFolder . $board . "/" . $threadNo . ".json")) {
										$threadUrl = $productionJSONFolder . $board . "/" . $threadNo . ".json";					
									} else {
										if (($useLoki) || ($useTor)) {
											if ($useLoki) {
												$threadUrl = "$lokiKun/$board/res/$threadNo.json";							
											}
											if ($useTor) {
												$threadUrl = "$torKun/$board/res/$threadNo.json";							
											}
										} else {
											$threadUrl = "https://$domain8Kun/$board/res/$threadNo.json";							
										}							
									}				
								} else {
									if (($useLoki) || ($useTor)) {
										if ($useLoki) {
											$threadUrl = "$lokiKun/$board/res/$threadNo.json";							
										}
										if ($useTor) {
											$threadUrl = "$torKun/$board/res/$threadNo.json";							
										}
									} else {
										$threadUrl = "https://$domain8Kun/$board/res/$threadNo.json";							
									}							
								}

								$currentDownloadAttempt = 1;			
								do {
									if ($currentDownloadAttempt > 1) {
										echo "--------- " . $fgYellow . "DOWNLOAD:" . $colorEnd . " $threadUrl?sqraper_nocache=" . $noCache . ". Attempt $currentDownloadAttempt of $maxDownloadAttempts\n";
									} else {
										echo "--------- " . $fgGreen . "DOWNLOAD:" . $colorEnd . " $threadUrl?sqraper_nocache=" . $noCache . ".\n";									
									}				
									
									$threadContents = @file_get_contents($threadUrl . "?sqraper_nocache=" . $noCache);
									if (!$threadContents) {
										sleep($pauseBetweenDownloadAttempts);
									}
									$currentDownloadAttempt++;
								}
								while ( ($threadContents === false) && ($currentDownloadAttempt <= $maxDownloadAttempts) );
															
								if ($threadContents == FALSE) {

									displayError('Could not get thread "' . $threadNo . '" for board "' . $board . '", URL ' . $threadUrl . '. WILL RETRY on next loop.');

								} else {

									if ($saveRemoteFilesToLocal) {
										if (!file_exists($productionJSONFolder . $board)) {
											echo $fgRed . "CREATE FOLDER:" . $colorEnd . " $productionJSONFolder$board.\n";
											mkdir($productionJSONFolder . $board, 0777, true);
										}
										file_put_contents($productionJSONFolder . $board . '/' . basename($threadUrl), $threadContents, LOCK_EX);	
									}

									echo "--------- " . $fgGreen . "DECODE." . $colorEnd . "\n";
									$jsonThreads = json_decode($threadContents, true);

									if ($jsonThreads == FALSE) {

										displayError("jsonThreads parse error.");

									} else {

										echo "--------- " . $fgGreen . "PARSE." . $colorEnd . "\n";
										foreach($jsonThreads['posts'] as $post) { // Loop through all of the posts in the current thread of the current catalog.

											/* ========================================= */
											/* ======= Do the real heavy lifting ======= */
											/* ========================================= */
											
											$postNo = $post['no'];
											$resto = basename($threadUrl, ".json");											

											if (isset($post['name'])) {
												$post_name = trim($post['name']);	
											} else {
												$post_name = null;
											}

											if (isset($post['trip'])) {
												$trip = $post['trip'];	
											} else {
												$trip = null;
											}

											/* ============================= */
											/* This is for when Q changes trips, at least we can detect it and manually eval it. */
											/* If it is valid, manually stop the sqraper, delete the EACH_BOARD_checked_threads.json files, */
											/* manually update the sqraper_config.json file to add the new trip, then relaunch the sqraper. */
											/* ============================= */
											if (trim($post_name) === "Q") {
												$foundThisTrip = false;
												foreach($qTrips as $qTrip) {	
													if ($qTrip === $trip) {
														$foundThisTrip = true;
													}
												}												
												
												if (!$foundThisTrip) {
													
													$isBogusTrip = false;
													foreach($bogusTrips as $bogusTrip) {	
														if ($bogusTrip === $trip) {
															$isBogusTrip = true;
														}
													}
													
													if (!$foundThisTrip) {
														if (isset($post['com'])) {
															$post_text_temp = cleanHtmlText(trim($post['com']));	
														} else {
															$post_text_temp = "---";
														}
														file_put_contents("new_trip_eval.txt", "Trip:$trip\nPost:" . $post_text_temp . "\n\n", FILE_APPEND | LOCK_EX);
														echo "------------ " . $fgYellow . "Found potentially new trip: " . $trip . $colorEnd . "\n";
														echo "------------ " . $fgGrey . $post_text_temp . $colorEnd . "\n";
														echo "------------ " . $fgYellow . "Wrote the potentially valid new trip to new_trip_eval.txt" . $colorEnd . "\n";
														echo "------------ " . $fgYellow . "Waiting 1 seconds for you to review and possibly press CTRL-C." . $colorEnd . "\n";
														echo "------------ " . $fgYellow . "If valid, delete " . $board . "_checked_threads.json, update the" . $colorEnd . "\n";
														echo "------------ " . $fgYellow . "sqraper_config.json file and restart sqraper." . $colorEnd . "\n";
														sleep(1);
													}
												}
											}
											/* ============================= */
											/* ==== End new trip check. ==== */
											/* ============================= */

											$foundTrip = false;
											foreach($qTrips as $qTrip) {	
												if ($trip === $qTrip) {
													$foundTrip = true;
													$currentTrip = $qTrip;
													break;
												}
											}
											
											if (($includeNoTripPosts == true) && ($foundTrip == false)) {
												$foundTrip = true;
												$currentTrip = '[Private Board, Anonymous, No Trip]';											
											}
											
											if ($foundTrip == true) {
												if (isInPostsJSON($resto, $postNo)) { // If already exists in posts.json then ignore.
													echo "------------ " . $fgYellow . "TRIP $currentTrip FOUND (Thread No: $resto, Post No: $postNo): OLD Q. Already Published." . $colorEnd . "\n";
												} else {
													$foundAnyNewPosts = true;
													echo "------------ " . $fgGreyBGGreen . "TRIP $currentTrip FOUND (Thread No: $resto, Post No: $postNo): NEW Q! Publishing." . $colorEnd . "\n";
													echo "\n" . $fgGrey . $post['com'] . $colorEnd . "\n\n";

													if (isset($post['email'])) {
														$post_email = $post['email'];	
													} else {
														$post_email = null;
													}

													if (isset($post['subject'])) {
														$post_subject = $post['subject'];	
													} else {
														$post_subject = null;
													}

													if (isset($post['no'])) {
														$post_id = $post['no'];	
													} else {
														$post_id = 0;
													}
													
													$post_threadId = basename($threadUrl, ".json");													
													
													$post_link = "https://$domain8KunForLinks/$board/res/$post_threadId.html#$post_id";
													$post_source = explode(".", $domain8KunForLinks)[0] . "_$board";

													if (isset($post['com'])) {
														$post_text = cleanHtmlText(trim($post['com']));	
													} else {
														$post_text = null;
													}
													if (isset($post['time'])) {
														$post_timestamp = $post['time'];	
													} else {
														$post_timestamp = 0;
													}
													if (isset($post['last_modified'])) {
														$post_lastModified = $post['last_modified'];	
													} else {
														$post_lastModified = 0;
													}												
													$post_trip = $trip;
													if (isset($post['id'])) {
														$post_userId = $post['id'];	
													} else {
														$post_userId = 0;
													}

													$thisPost = array(
														'email' => $post_email,
														'id' => $post_id,
														'link' => $post_link,
														'name' => $post_name,
														'source' => $post_source,
														'subject' => $post_subject,
														'text' => $post_text,
														'threadId' => $post_threadId,
														'timestamp' => $post_timestamp,
														'trip' => $post_trip,
														'userId' => $post_userId,
														'minedBy' => $scriptTitle . ' ' . $scriptVersion
													);
													
													$post_media = getMediaObject($post);
													if (!empty($post_media)) {
														$thisPost['media'] = $post_media;
													} else {
														$thisPost['media'] = [];
													}
													
													$post_References_Result = getReferencesObject($post_text, true);
													if (!empty($post_References_Result)) {
														$thisPost['references'] = $post_References_Result;
													}
													
													array_push($newlyAddedQPosts, $thisPost);

													$newQSinceStart ++;																								

													// If you want to put each post on your Desktop for debugging: file_put_contents("C:/Users/YOU/Desktop/" . $resto . "-" . $postNo . ".json", json_encode($newlyAddedQPosts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK), LOCK_EX);
													
												} // If already exists in posts.json then ignore.
											}
											
											/* ============================================= */
											/* ======= End of the real heavy lifting ======= */
											/* ============================================= */

										} // End of loop through all of the posts in the current thread of the current catalog.
										
										
										setThreadUpdated($threadNo, $threadLastModified);

										unset($jsonThreads);							
									
									} // if ($jsonThreads == FALSE) {

								} // if ($threadContents != FALSE) {
														
							} // This is where we check the thread dates and then conditionally continue.						

						} // End of loop through all of the threads in the current page of the catalog.			
						
					} else {
						echo "--- " . $fgYellow . "EMPTY:" . $colorEnd . " Threads object is empty on page $page. Probably no posts yet.\n";
					}
				
					unset($jsonBoardCatalog);
					
				} // End of loop through all of the pages in the catalog.

				/* ======= Write the ID's of what was checked to JSON file for future runs ======= */

				if ($foundAnyNewPosts) {
					echo "\n--- " . $fgGreen . "NEW POSTS WERE FOUND. MERGE, SORT AND WRITE THE NEW posts.json FILE." . $colorEnd. "\n\n";
					if (!empty($newlyAddedQPosts)) {
						echo "--- " . $fgGreen . "newlyAddedQPosts is NOT empty. Merging with existing content in $productionJSONFolder$productionPostsJSONFilename." . $colorEnd . "\n";
						$contents = @file_get_contents($productionJSONFolder . $productionPostsJSONFilename);	
						if (!$contents){		
							displayError("Read $productionJSONFolder$productionPostsJSONFilename for merging.");
						} else {
							$jsonContents = json_decode($contents, true);
							unset($jsonContents['email']);
							unset($jsonContents['title']);
							if (($jsonContents == FALSE) && (json_last_error() !== JSON_ERROR_NONE)) {
								displayError("Parse JSON $productionJSONFolder$productionPostsJSONFilename for merging. " . json_last_error());
							} else {
								$mergedArray = array_merge($newlyAddedQPosts, $jsonContents);						
								array_multisort(array_map(function($element) {
									  return $element['timestamp'];
								  }, $mergedArray), SORT_DESC, $mergedArray);
								echo "\n" . $fgGreen . "--- WRITE " . $productionJSONFolder . $productionPostsJSONFilename . "." . $colorEnd . "\n";
								file_put_contents($productionJSONFolder . $productionPostsJSONFilename, json_encode($mergedArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK), LOCK_EX);
								unset($mergedArray);
								unset($jsonContents);	
								uploadViaFTP($productionPostsJSONFilename, $productionPostsJSONFilename, false);
							}							
						}

						$GLOBALS['postsAddedThisLoop'] = sizeof($newlyAddedQPosts);
						unset($newlyAddedQPosts);
						$newlyAddedQPosts = [];
					} else {
						echo "--- " . $fgYellow . "newlyAddedQPosts is empty? Should not be? No need to merge." . $colorEnd . "\n";
					}
										
					if (file_exists('sqraperextra.php')) {
						if (function_exists('runExtraOnNewQ')) { 
							runExtraOnNewQ();
						}
					}
					
				}
				
				/* =============================================================================== */
					
			} // if ($jsonBoardCatalog == FALSE) {
			
		} // if (!$boardCatalogContents)
					
		file_put_contents($threadMapFile, json_encode($threadMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK), LOCK_EX);					

		unset($threadMap);		

	} // End of loop through all boards defined in the array in the configuration section at the top of the page.	

	if (file_exists('sqraperextra.php')) {
		if (function_exists('runExtraAlways')) { 
			runExtraAlways();
		}
	}

	echo "\n" . $fgGreen . "NEW Q DROPS:" . $colorEnd . " $newQSinceStart (since Sqraper v$scriptVersion started $sqraperStarted).\n";
	$timeFinished  = strtotime(date('m/d/Y h:i:s a', time()));
	$differenceInSeconds = $timeFinished - $timeStarted;
	echo $fgGreen . "FINISHED:" . $colorEnd . " " . date("m/d/Y h:i:sa") . ". Took $differenceInSeconds second(s) to complete.\n";
	
	/*
	This allows you to change the sqraper_config.json file to make config changes without stopping and restarting the script.
	The changes will be picked up and applied the next time the script loops. This could also be used to have an Admin acccessible 
	webpage write an updated sqraper_config.json, thus allowing an Admin to make important changes via a web page when the script
	runs on a server, without having to gain direct access to the server OS itself.
	*/
	getConfig(); 
	/************************************************************/	
	
	//uploadViaFTP($productionPostsJSONFilename, $productionPostsJSONFilename, false);
	
	/*
	It's good etiquette to poll less frequently whenever we think there is little to no chance of Q posting.
	You may want/need to adjust this for your time zone. Default is slow down after 2AM and ramp back up to
	whatever is in the config at 9AM.
	*/
	if ((date('H') >= 2) && (date('H') < 9)) {
		echo $fgGreen . "OFF PEAK TIME SLEEP:" . $colorEnd . " $offPeakSleepBetweenNewQPostChecks seconds.\n\n";		
		sleep($offPeakSleepBetweenNewQPostChecks);
	} else {
		echo $fgGreen . "PEAK TIME SLEEP:" . $colorEnd . " $sleepBetweenNewQPostChecks seconds.\n\n";	
		sleep($sleepBetweenNewQPostChecks);
	}		
	
} while ($continue == true);

/* ============================= */
?>
