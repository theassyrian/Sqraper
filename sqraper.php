<?php

/*

Sqraper
Version: 1.2.3
Last Updated: November 21, 2019
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
$scriptVersion = "1.2.3";
$scriptUpdated = "Last Updated: November 22, 2019";
$scriptAuthor = "DevAnon from QAlerts.app";
$scriptAuthorEmail = "qalertsapp@gmail.com";

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
	
	echo "\n\e[1;31m>>>\e[0m ========================================\n";
	echo "\e[1;31m>>>\e[0m ERROR: $errDescription\n";			
	echo "\e[1;31m>>>\e[0m SLEEP: 5 seconds.\n";			
	echo "\e[1;31m>>>\e[0m ========================================\n\n";		
	sleep(5);
	
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
	return "\e[0;37;44m" . $text . str_repeat(' ', $a) . "\e[0m";
}

function uploadViaFTP($localFile, $remoteFile, $isMedia) {

	if ($isMedia) {
		$dataType = FTP_BINARY;
		$localFilePath = $GLOBALS['productionMediaFolder'] . $localFile;
		$remoteFilePath = $GLOBALS['ftpUploadMediaFolder'] . $remoteFile;
	} else {
		$dataType = FTP_ASCII;
		$localFilePath = $GLOBALS['productionJSONFolder'] . $localFile;
		$remoteFilePath = $GLOBALS['ftpUploadJSONFolder'] . $remoteFile;
	}
	
	echo "\e[1;32m--- FTP CONNECT.\e[0m\n";	
	$ftpConnection = ftp_connect($GLOBALS['ftpServer']);
	$login_result = ftp_login($ftpConnection, $GLOBALS['ftpLoginID'], $GLOBALS['ftpPassword']);

	echo "\e[1;32m--- FTP PUT: " . $localFilePath . ' > ' . $remoteFilePath . ".\e[0m\n";

	if (ftp_put($ftpConnection, $remoteFilePath, $localFilePath, $dataType)) {
		echo "\e[1;32m--- FTP PUT SUCCESS: " . $localFilePath . ' > ' . $remoteFilePath . ".\e[0m\n";
	} else {
		echo "\e[1;31m--- FTP PUT FAILED: " . $localFilePath . ' > ' . $remoteFilePath . " " . error_get_last() . ".\e[0m\n";
	}

	echo "\e[1;32m--- FTP CLOSE.\e[0m\n";	
	ftp_close($ftpConnection);	
	
}

function getConfig() {

	if (!file_exists('sqraper_config.json')) {

		echo "\e[1;31mCREATE FILE:\e[0m sqraper_config.json did not exist. Creating and reading default configuration JSON file.\n";

		$defaultConfig = array(
			'qTrips' => ['!!mG7VJxZNCI'],
			'boards' => ['qresearch'],
			'domain8Kun' => '8kun.top',
			'domain8KunForLinks' => '8kun.net',
			'lokiKun' => 'http://pijdty5otm38tdex6kkh51dkbkegf31dqgryryz3s3tys8wdegxo.loki',
			'useLoki' => true,
			'saveRemoteFilesToLocal' => true,
			'readFromLocal8KunFiles' => false,
			'sleepBetweenNewQPostChecks' => 150,
			'productionPostsJSONFilename' => 'posts.json',
			'productionJSONFolder' => 'json/',
			'productionMediaFolder' => 'media/',
			'productionMediaURL' => 'https://yourserver.com/media/', // If not blank, the media URL in the file will be build with this domain and path.
			'ftpUploadJSON' => false,
			'ftpUploadJSONFolder' => '/data/posts/', // Folder must already exist on the remote server.
			'ftpUploadMedia' => false,
			'ftpUploadMediaFolder' => '/data/media/', // Folder must already exist on the remote server.
			'ftpServer' => 'ftp.yourserver.com',
			'ftpLoginID' => 'your_user_name',
			'ftpPassword' => 'your_password' // BEWARE placing this script in an Internet acccessible folder. Someone could easily view your sqraper.json file and access your FTP password!
		);		
		$GLOBALS['qTrips'] = $defaultConfig['qTrips'];
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
				$GLOBALS['qTrips'] = $currentConfigJSON['qTrips'];
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

function cleanHtmlText($htmlText) {
		
	$htmlText = preg_replace('#<a onclick=\"highlightReply.*?>(.*?)</a>#i', '${1}', $htmlText);
	
	$linkPattern = '~<a [^>]+>(.+?)<\\\/a>~';
	$htmlText = preg_replace($linkPattern, '${1}1', $htmlText);	
	
	$htmlText = str_replace('<p class="body-line empty">', '', $htmlText);
	$htmlText = str_replace('<p class="body-line empty ">', '', $htmlText);

	$htmlText = str_replace('<p class="body-line ltr quote">', '', $htmlText);
	$htmlText = str_replace('<p class="body-line ltr quote ">', '', $htmlText);

	$htmlText = str_replace('<p class="body-line ltr ">', '', $htmlText);
	$htmlText = str_replace('</p>', "\n", $htmlText);	
	
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

			echo "\e[1;33m--- SKIP DOWNLOAD MEDIA (ALREADY EXISTS): \n    " . $thisUrl . " > " . $GLOBALS['productionMediaFolder'] . $thisStorageFilename . "\e[0m\n";	
			
		} else {

			echo "\e[1;32m--- DOWNLOAD MEDIA: " . $thisUrl . " > " . $GLOBALS['productionMediaFolder'] . $thisStorageFilename . "\e[0m\n";	
			
			$thisMedia = @file_get_contents($thisUrl);
			if (!$thisMedia) {				
				displayError("Could not get media from URL \"$thisUrl");				
			} else {
				if (!file_exists($GLOBALS['productionMediaFolder'])) {
					echo "\e[1;31mCREATE FOLDER:\e[0m " . $GLOBALS['productionMediaFolder'] . "\n";
					mkdir($GLOBALS['productionMediaFolder'], 0777, true);
				}
				file_put_contents($GLOBALS['productionMediaFolder'] . $thisStorageFilename, $thisMedia, LOCK_EX);
				unset($thisMedia);
				if ($GLOBALS['ftpUploadMedia']) {
					uploadViaFTP($thisStorageFilename, $thisStorageFilename, true);
				}
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
		
		if ($GLOBALS['useLoki']) {
			$thisDownload = "http://media." . str_replace("http://", "", $GLOBALS['lokiKun']) . "/file_store/" . $inArray['tim'] . $inArray['ext'];
			downloadMediaFile($thisDownload, $thisStorageFilename);
		} else {
			$thisDownload = "https://media." . $GLOBALS['domain8KunForLinks'] . "/file_store/" . $inArray['tim'] . $inArray['ext'];
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
					//$thisUrl = "https://media." . $GLOBALS['domain8KunForLinks'] . "/file_store/" . $extraFile['tim'] . $extraFile['ext'];
					$thisFilename = $extraFile['filename'] . $extraFile['ext'];
					$thisStorageFilename = $extraFile['tim'] . $extraFile['ext'];
					$thisMedia = array(
						'filename' => $thisFilename,
						'url' => $thisUrl
					);
					array_push($returnArray, $thisMedia);					

					if ($GLOBALS['useLoki']) {
						$thisDownload = "http://media." . str_replace("http://", "", $GLOBALS['lokiKun']) . "/file_store/" . $extraFile['tim'] . $extraFile['ext'];
						downloadMediaFile($thisDownload, $thisStorageFilename);
					} else {
						$thisDownload = "https://media." . $GLOBALS['domain8KunForLinks'] . "/file_store/" . $extraFile['tim'] . $extraFile['ext'];
						downloadMediaFile($thisDownload, $thisStorageFilename);
					}					
				}
			}
		}
	}
	return $returnArray;
}

function getReferencesObject($searchStr) {

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

						echo "--------- \e[1;32mREFERENCE POST FOUND: $match\e[0m\n";

						if (isset($postReference['email'])) {
							$postReference_email = $postReference['email'];	
						} else {
							$postReference_email = null;
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
						$post_subject = null;
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
							'threadId' => $postReference_threadId,
							'id' => $postReference_id,
							'timestamp' => $postReference_timestamp,
							'lastModified' => $postReference_lastModified,
							'source' => $postReference_source,
							'link' => $postReference_link,
							'name' => $postReference_name,
							'trip' => $postReference_trip,
							'userId' => $postReference_userId,
							'text' => $postReference_text
						);															
						
						$post_referencesMedia = [];															
						if ((isset($postReference['filename'])) && (isset($postReference['ext'])) && (isset($postReference['tim']))){			
							$thisPostReferenceUrl = "https://media." . $GLOBALS['domain8KunForLinks'] . "/file_store/" . $postReference['tim'] . $postReference['ext'];
							$thisPostReferenceFilename = $postReference['filename'] . $postReference['ext'];
							$thisPostReferenceMedia = array(
								'filename' => $thisPostReferenceFilename,
								'url' => $thisPostReferenceUrl
							);
							array_push($post_referencesMedia, $thisPostReferenceMedia);															
							if (isset($postReference['extra_files'])) {
								foreach($postReference['extra_files'] as $extraPostReferenceFile) {															
									if ((isset($extraPostReferenceFile['filename'])) && (isset($extraPostReferenceFile['ext'])) && (isset($extraPostReferenceFile['tim']))){
										$thisPostReferenceUrl = "https://media." . $GLOBALS['domain8KunForLinks'] . "/file_store/" . $extraPostReferenceFile['tim'] . $extraPostReferenceFile['ext'];
										$thisPostReferenceFilename = $extraPostReferenceFile['filename'] . $extraPostReferenceFile['ext'];
										$thisPostReferenceMedia = array(
											'filename' => $thisPostReferenceFilename,
											'url' => $thisPostReferenceUrl
										);										
										array_push($post_referencesMedia, $thisPostReferenceMedia);
									}
								}
							}																
							$thisReferencesPost['media'] = $post_referencesMedia;
						}
						
						$post_referencesReferences = [];
						$subSub_References_Result = getReferencesObject($postReference_text);
						foreach($subSub_References_Result as $subSubReference) {
							array_push($post_referencesReferences, $subSubReference);
						}
						if (!empty($subSub_References_Result)) {
							$thisReferencesPost['references'] = $subSub_References_Result;
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
					$GLOBALS['threadMap'][$key]['last_modified'] = $varLastModified;
					$entryUpdated = true;
				}						
				break;					
			}					
		}					
	}

	if (($GLOBALS['debugWithAPost']) && ($varNo == $GLOBALS['debugThreadNo']))    {
		return true;
	} else {
		if (!$foundEntry) {
			array_push($GLOBALS['threadMap'], array(
				'no' => $varNo,
				'last_modified' => $varLastModified
			));	
		}
		return $entryUpdated;		
	}

}	

/* ============================= */


/* ============================= */
/* ======== Main Routine ======= */
/* ============================= */

/* Color codes: https://joshtronic.com/2013/09/02/how-to-use-colors-in-command-line-output/ */

echo "\n\e[0;37;41m************************************************************\e[0m\n";
echo createBlueBGText($scriptTitle) . "\n";
echo createBlueBGText("Version: " . $scriptVersion) . "\n";
echo createBlueBGText("Updated: " . $scriptUpdated) . "\n";
echo createBlueBGText("Author: " . $scriptAuthor) . "\n";
echo createBlueBGText("Email: " . $scriptAuthorEmail) . "\n";
echo "\e[0;37;41m************************************************************\e[0m\n";
echo "\n";

do {
	
	$timeStarted  = strtotime(date('m/d/Y h:i:s a', time()));
	
	echo "============================================================\n";

	$strBoards = "";
	foreach($boards as $board) {	
		$strBoards = $strBoards . $board . " ";
	}

	$strQTrips = "";
	foreach($qTrips as $qTrip) {	
		$strQTrips = $strQTrips . $qTrip . " ";
	}

	echo "\e[1;34mSqraper Started:\e[0m $sqraperStarted\n";
	echo "\e[1;34mNew Q Drops Since Start:\e[0m $newQSinceStart\n";
	echo "\e[1;34mConfiguration:\e[0m\n";
	echo "   \e[1;34mTrips:\e[0m $strQTrips\n";
	echo "   \e[1;34mBoards:\e[0m " . trim($strBoards) . "\n";
	echo "   \e[1;34mInternet Domain:\e[0m $domain8Kun\n";
	echo "   \e[1;34mInternet Domain for Links in JSON:\e[0m $domain8KunForLinks\n";
	echo "   \e[1;34mUse Loki.Network:\e[0m $useLoki\n";
	echo "   \e[1;34mLoki.Network Address:\e[0m $lokiKun\n";
	echo "   \e[1;34mProduction JSON Filename:\e[0m $productionPostsJSONFilename\n";
	echo "   \e[1;34mProduction JSON (Local) Folder:\e[0m $productionJSONFolder\n";
	echo "   \e[1;34mProduction Media (Local) Folder:\e[0m $productionMediaFolder (if blank photos/videos will not be downloaded)\n";
	echo "   \e[1;34mProduction Media (Remote) URL:\e[0m $productionMediaURL\n";
	echo "   \e[1;34mSave Downloaded 8kun JSON Files To Local:\e[0m $saveRemoteFilesToLocal\n";
	echo "   \e[1;34mRead From Local 8Kun Files (for debugging/testing):\e[0m $readFromLocal8KunFiles\n";
	echo "   \e[1;34mFTP Server:\e[0m " . mask($ftpServer) . "\n";
	echo "   \e[1;34mFTP Login ID:\e[0m " . mask($ftpLoginID) . "\n";
	echo "   \e[1;34mFTP Password:\e[0m " . mask($ftpPassword) . "\n";
	echo "   \e[1;34mFTP Upload JSON Posts:\e[0m $ftpUploadJSON\n";
	echo "   \e[1;34mFTP Upload JSON Posts Folder:\e[0m $ftpUploadJSONFolder\n";
	echo "   \e[1;34mFTP Upload Media:\e[0m $ftpUploadMedia\n";
	echo "   \e[1;34mFTP Upload Media Folder:\e[0m $ftpUploadMediaFolder\n";
	echo "   \e[1;34mSleep Between Loops:\e[0m $sleepBetweenNewQPostChecks\n";
	echo "\e[1;34mLoop Started:\e[0m " . date("m/d/Y h:i:s a") . "\n";
	echo "============================================================\n\n";

	if ((isset($productionJSONFolder)) && ($productionJSONFolder !== '')) {
		if (!file_exists($productionJSONFolder)) {
			echo "\e[1;31mCREATE FOLDER:\e[0m $productionJSONFolder.\n";
			mkdir($productionJSONFolder, 0777, true);
		}
	}
	
	if ((isset($productionMediaFolder)) && ($GLOBALS['productionMediaFolder'] != '')) {
		if (!file_exists($productionMediaFolder)) {
			echo "\e[1;31mCREATE FOLDER:\e[0m $productionMediaFolder.\n";
			mkdir($productionMediaFolder, 0777, true);
		}
	}
	
	if (!file_exists($productionJSONFolder . $productionPostsJSONFilename)) {
		echo "\e[1;31mCREATE FILE:\e[0m $productionJSONFolder$productionPostsJSONFilename did not exist. Creating empty JSON file.\n";
		$blankPosts = array();
		file_put_contents($productionJSONFolder . $productionPostsJSONFilename, json_encode($blankPosts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK), LOCK_EX);
	}			

	foreach($boards as $board) { // Loop through all boards defined in the array in the configuration section at the top of the page.

		$foundAnyNewPosts = false;
		
		$threadMap = [];
		$threadMapFile = $productionJSONFolder . $board . '_checked_threads.json';
		if (!file_exists($threadMapFile)) {
			echo "\e[1;31mCREATE FILE:\e[0m $threadMapFile did not exist. Creating empty JSON file.\n";
			$threadMap = array();			
			array_push($threadMap, array(
				'no' => 0,
				'last_modified' => 0
			));			
			file_put_contents($threadMapFile, json_encode($threadMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK), LOCK_EX);
		}			
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
				echo "\e[1;32mCREATE FOLDER:\e[0m $productionJSONFolder$board.\n";
				mkdir($productionJSONFolder . $board, 0777, true);
			}
			if (file_exists($productionJSONFolder . $board . "/catalog.json")) {
				$boardCatalogUrl = $productionJSONFolder . $board . "/catalog.json";					
			} else {
				if ($useLoki) {
					$boardCatalogUrl = "$lokiKun/$board/catalog.json";
				} else {
					$boardCatalogUrl = "https://$domain8Kun/$board/catalog.json";		
				}				
			}				
		} else {
			if ($useLoki) {
				$boardCatalogUrl = "$lokiKun/$board/catalog.json";
			} else {
				$boardCatalogUrl = "https://$domain8Kun/$board/catalog.json";		
			}
		}
		
		echo "\e[1;32mDOWNLOAD:\e[0m $boardCatalogUrl.\n";
		$boardCatalogContents = @file_get_contents($boardCatalogUrl);

		if (!$boardCatalogContents) {
			
			displayError("Could not get catalog for board \"$board\", URL \"$boardCatalogUrl");
			
		} else {

			if ($saveRemoteFilesToLocal) {
				if (!file_exists($productionJSONFolder . $board)) {
					echo "\e[1;31mCREATE FOLDER:\e[0m $productionJSONFolder$board.\n";
					mkdir($productionJSONFolder . $board, 0777, true);
				}
				file_put_contents($productionJSONFolder . $board . '/' . basename($boardCatalogUrl), $boardCatalogContents, LOCK_EX);
			}
			
			echo "--- \e[1;32mJSON DECODE.\e[0m\n";
			$jsonBoardCatalog = json_decode($boardCatalogContents, true);

			if ($jsonBoardCatalog == FALSE) {

				displayError("jsonBoardCatalog parse error.");
				break;

			} else {

				foreach($jsonBoardCatalog as $pages) { // Loop through all of the pages in the catalog.

					$page = $pages['page'];
					
					//$threads = $pages['threads'];
					echo "--- \e[1;32mPARSE:\e[0m Page $page.\n";

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

								echo "------ \e[1;32mThread No\e[0m $threadNo, \e[1;32mLast Modified\e[0m " . date("M d, Y g:i:s A", $threadLastModified) . ".\n";						
								echo "--------- \e[1;32mThread HAS Changed.\e[0m\n";

								$threadUrl = "https://$domain8Kun/$board/res/$threadNo.json";

								$threadNo = $thread['no'];

								if ($readFromLocal8KunFiles) {
									if (!file_exists($productionJSONFolder . $board)) {
										echo "\e[1;31mCREATE FOLDER:\e[0m $productionJSONFolder$board.\n";
										mkdir($productionJSONFolder . $board, 0777, true);
									}
									if (file_exists($productionJSONFolder . $board . "/" . $threadNo . ".json")) {
										$threadUrl = $productionJSONFolder . $board . "/" . $threadNo . ".json";					
									} else {
										if ($useLoki) {
											$threadUrl = "$lokiKun/$board/res/$threadNo.json";							
										} else {
											$threadUrl = "https://$domain8Kun/$board/res/$threadNo.json";							
										}							
									}				
								} else {
									if ($useLoki) {
										$threadUrl = "$lokiKun/$board/res/$threadNo.json";							
									} else {
										$threadUrl = "https://$domain8Kun/$board/res/$threadNo.json";							
									}							
								}

								echo "--------- \e[1;32mDOWNLOAD:\e[0m $threadUrl.\n";
								$threadContents = @file_get_contents($threadUrl);
								
								if ($threadContents == FALSE) {

									displayError('Could not get thread \"$threadNo\" for board \"$board\", URL \"$threadUrl\".');

								} else {

									if ($saveRemoteFilesToLocal) {
										if (!file_exists($productionJSONFolder . $board)) {
											echo "\e[1;31mCREATE FOLDER:\e[0m $productionJSONFolder$board.\n";
											mkdir($productionJSONFolder . $board, 0777, true);
										}
										file_put_contents($productionJSONFolder . $board . '/' . basename($threadUrl), $threadContents, LOCK_EX);	
									}

									echo "--------- \e[1;32mDECODE.\e[0m\n";
									$jsonThreads = json_decode($threadContents, true);

									if ($jsonThreads == FALSE) {

										displayError("jsonThreads parse error.");

									} else {

										echo "--------- \e[1;32mPARSE.\e[0m\n";
										foreach($jsonThreads['posts'] as $post) { // Loop through all of the posts in the current thread of the current catalog.

											/* ========================================= */
											/* ======= Do the real heavy lifting ======= */
											/* ========================================= */
											
											$postNo = $post['no'];
											$resto = $post['resto'];

											if (isset($post['trip'])) {
												$trip = $post['trip'];	
											} else {
												$trip = null;
											}

											$foundTrip = false;
											foreach($qTrips as $qTrip) {	
												if ($trip === $qTrip) {
													$foundTrip = true;
													$currentTrip = $qTrip;
													break;
												}
											}
											
											if ($foundTrip == true) {
												if (isInPostsJSON($resto, $postNo)) { // If already exists in posts.json then ignore.
													echo "------------ \e[1;33mTRIP $currentTrip FOUND (Thread No: $resto, Post No: $postNo): OLD Q. Already Published.\e[0m\n";
												} else {
													$foundAnyNewPosts = true;
													echo "------------ \e[0;37;42mTRIP $currentTrip FOUND (Thread No: $resto, Post No: $postNo): NEW Q! Publishing.\e[0m\n";												
													echo "\n\e[1;30m" . $post['com'] . "\e[0m\n\n";

													if (isset($post['email'])) {
														$post_email = $post['email'];	
													} else {
														$post_email = null;
													}
													if (isset($post['no'])) {
														$post_id = $post['no'];	
													} else {
														$post_id = 0;
													}
													if (isset($post['resto'])) {
														$post_threadId = $post['resto'];	
													} else {
														$post_threadId = 0;
													}
													$post_link = "https://$domain8KunForLinks/$board/res/$post_threadId.html#$post_id";
													if (isset($post['name'])) {
														$post_name = trim($post['name']);	
													} else {
														$post_name = null;
													}
													$post_source = explode(".", $domain8KunForLinks)[0] . "_$board";
													$post_subject = null;
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
														'threadId' => $post_threadId,
														'id' => $post_id,
														'timestamp' => $post_timestamp,
														'lastModified' => $post_lastModified,
														'source' => $post_source,
														'link' => $post_link,
														'name' => $post_name,
														'trip' => $post_trip,
														'userId' => $post_userId,
														'text' => $post_text
													);
													
													$post_media = getMediaObject($post);
													if (!empty($post_media)) {
														$thisPost['media'] = $post_media;
													}
													
													$post_References_Result = getReferencesObject($post_text);
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

										unset($jsonThreads);							
									
									} // if ($jsonThreads == FALSE) {

								} // if ($threadContents != FALSE) {
														
							} // This is where we check the thread dates and then conditionally continue.						

						} // End of loop through all of the threads in the current page of the catalog.			
						
					} else {
						echo "--- \e[1;33mEMPTY:\e[0m Threads object is empty on page $page. Probably no posts yet.\n";
					}
				
					unset($jsonBoardCatalog);
					
				} // End of loop through all of the pages in the catalog.

				/* ======= Write the ID's of what was checked to JSON file for future runs ======= */

				if ($foundAnyNewPosts) {
					echo "\n--- \e[1;32mNEW POSTS WERE FOUND. MERGE, SORT AND WRITE THE NEW posts.json FILE.\e[0m\n\n";
					if (!empty($newlyAddedQPosts)) {
						echo "--- \e[1;32mnewlyAddedQPosts is NOT empty. Merging with existing content in $productionJSONFolder$productionPostsJSONFilename.\e[0m\n";						
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
								echo "\n\e[1;32m--- WRITE " . $productionJSONFolder . $productionPostsJSONFilename . ".\e[0m\n";
								file_put_contents($productionJSONFolder . $productionPostsJSONFilename, json_encode($mergedArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK), LOCK_EX);
								unset($mergedArray);
								unset($jsonContents);	
								if ($GLOBALS['ftpUploadJSON']) {
									uploadViaFTP($productionPostsJSONFilename, $productionPostsJSONFilename, false);
								}
							}							
						}
						unset($newlyAddedQPosts);
						$newlyAddedQPosts = [];
					} else {
						echo "--- \e[1;33mnewlyAddedQPosts is empty? Should not be? No need to merge.\e[0m\n";
					}
				}
				
				/* =============================================================================== */
					
			} // if ($jsonBoardCatalog == FALSE) {
			
		} // if (!$boardCatalogContents)
					
		file_put_contents($threadMapFile, json_encode($threadMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK), LOCK_EX);					

		unset($threadMap);		

	} // End of loop through all boards defined in the array in the configuration section at the top of the page.	

	echo "\n\e[1;32mNEW Q DROPS:\e[0m $newQSinceStart (since Sqraper started $sqraperStarted).\n";
	$timeFinished  = strtotime(date('m/d/Y h:i:s a', time()));
	$differenceInSeconds = $timeFinished - $timeStarted;
	echo "\e[1;32mFINISHED:\e[0m " . date("m/d/Y h:i:sa") . ". Took $differenceInSeconds second(s) to complete.\n";
	
	/*
	This allows you to change the sqraper_config.json file to make config changes without stopping and restarting the script.
	The changes will be picked up and applied the next time the script loops. This could also be used to have an Admin acccessible 
	webpage write an updated sqraper_config.json, thus allowing an Admin to make important changes via a web page when the script
	runs on a server, without having to gain direct access to the server OS itself.
	*/
	getConfig(); 
	/************************************************************/	
	
	//uploadViaFTP($productionPostsJSONFilename, $productionPostsJSONFilename, false);
	
	echo "\e[1;32mSLEEP:\e[0m $sleepBetweenNewQPostChecks seconds.\n\n";	
	sleep($sleepBetweenNewQPostChecks);
	
} while ($continue == true);

/* ============================= */
?>
