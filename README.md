# Sqraper
PHP 8Kun Q post scraper.

View this README.md RAW for a more true reading experience :)

-----------------

IMPORTANT! This is a pre-release. It wasn't intended to be released this soon. It is being released in BETA early and to
limited persons due to lag in picking up new drops from some data sources.

This script is not for novices. Basic PHP understanding required.

I worked hard to write this in order to help the community. Therefore I ask, if you find bugs and correct them, make
improvements, etc., contribute your changes back to the community via this GitHub. Don't be "that guy". WWG1WGA is not
just a catchy statement, let's all try to live it.

-----------------

CONFIGURATION FILE: sqraper_config.json (needs to be in the same folder as sqraper.php)

{
  "qTrip": "!!mG7VJxZNCI",
  "boards": [
    "qresearch"
  ],
  "domain8Kun": "8kun.top",
  "domain8KunForLinks": "8kun.top",
  "lokiKun": "http://pijdty5otm38tdex6kkh51dkbkegf31dqgryryz3s3tys8wdegxo.loki",
  "useLoki": true,
  "saveRemoteFilesToLocal": true,
  "readFromLocal8KunFiles": false,
  "sleepBetweenNewQPostChecks": 120,
  "productionPostsJSONFilename": "posts.json",
  "productionJSONFolder": "json/",
  "productionMediaFolder": "media/",
  "ftpUploadJSON": true,
  "ftpUploadJSONFolder": "/data/json/",
  "ftpUploadMedia": true,
  "ftpUploadMediaFolder": "/media/",
  "ftpServer": "ftp.yourserver.com",
  "ftpLoginID": "somePatriot",
  "ftpPassword": "yourPassword",
  "productionMediaURL": "https://yourserver.com/media/"
}

-----------------

CONFIGURATION ITEM EXPLAINATIONS:

  qTrip:
    Type: String
    Default: "!!mG7VJxZNCI"
		Q's current trip code. When Q changes trips, be sure the sqraper has downloaded all posts with the previous
		trip code first. Then change the configuration file to Q's new trip code to start pulling posts from the
		new trip.
    
  boards:
    Type: Array
    Default: [
      "qresearch"
    ]
		Enter the one or more boards Q is dropping on. At time of publishing, Q is only dropping on "qresearch" 
		8kun.top/qresearch/. If Q were to start posting on, let's say "patriotsfight" also, you would enter
		"qresearch","patriotsfight".
    
  domain8Kun:
    Type: String
    Default: "8kun.top"
		This is only used if "useLoki" is false, which causes sqraping over the normal Internet (which at this time is
		not possible due to the DDoS protection 8kun has in place). If sqraping over Internet, you enter the domain to
		scrape (without any leading https:// or trailing /).
    
  domain8KunForLinks:
    Type: String
    Default: "8kun.top"
		All links within the posts.json file to 8kun will be generated with this domain. Do not enter any leading
		https:// or trailing /. Examples: "8kun.net", "8kun.top", etc. WARNING. Once the posts are generated in the
		posts.json file, they are generated. Therefore, this setting only is applied to newly added posts. You may want
		to consider just using "8kun.net" and then do a search and replace in the user facing interface to switch it
		to the current 8kun flavor of the week :).
    
  lokiKun:
    Type: String
    Default: "http://pijdty5otm38tdex6kkh51dkbkegf31dqgryryz3s3tys8wdegxo.loki"
		Simply the fully qualified URL to 8kun over LokiNet with no trailing /.

  useLoki:
    Type: Boolean
    Default: true
		If true, causes the sqraper to scrape for new Q over the Loki network (uses "lokiKun" URL setting"). If false,
		causes the sqraper to scrape new Q over the normal Internet (uses "domain8Kun" setting).
    
  saveRemoteFilesToLocal:
    Type: Boolean
    Default: true
		Saves all JSON files scraped from 8kun to your local drive. This can be helpful after the fact for debugging
		with "readFromLocal8KunFiles". It can also be useful if you ever need to reference older 8kun files since they
		seem to cease to be available after a day or so.
    
  readFromLocal8KunFiles:
    Type: Boolean
    Default: false
		Only used if "saveRemoteFilesToLocal" has been previously enabled and files exist. It is generally used (seldom)
		for in-depth debugging.
    
  sleepBetweenNewQPostChecks:
    Type: Integer
    Default: 150
		The time in seconds to wait between checking for new posts. I suggest you don't get too greedy and chew up 8kun
		resources any more than need be. Let's be respectful. 150 is every 2 and half minutes.
    
  productionPostsJSONFilename:
    Type: String
    Default: "posts.json"
		Description coming soon...
    
  productionJSONFolder:
    Type: String
    Default: "json/"
		Description coming soon...
    
  productionMediaFolder:
    Type: String
    Default: "media/"
		Description coming soon...
    
  ftpUploadJSON:
    Type: Boolean
    Default: false
		Description coming soon...
  
  ftpUploadJSONFolder:
    Type: String
    Default: "/data/json/"
		Description coming soon...
    
  ftpUploadMedia:
    Type: Boolean
    Default: false
		Description coming soon...
    
  ftpUploadMediaFolder:
    Type: String
    Default: "/media/"
		Description coming soon...
    
  ftpServer:
    Type: String
    Default: "ftp.yourserver.com"
		Description coming soon...
    
  ftpLoginID:
    Type: String
    Default: "your_user_name"
		Description coming soon...
    
  ftpPassword:
    Type: String
    Default: "your_password"
		Description coming soon...
    
  productionMediaURL:
    Type: String
    Default: "https://yourserver.com/media/"
		Description coming soon...

-----------------

PREREQUISITES:

You MUST have PHP installed. I have tested the script with PHP on both Windows 10 as well as Linux Ubuntu Server LTS.

In order to have previous posts, before starting the sqraper for the 1st time, get a posts.json file from one of the
following sources and place it in the folder you configure for your "productionJSONFolder" configuration variable.
If you have configured something other than "posts.json" for the "productionPostsJSONFilename" configuration variable
then rename the JSON file that name.

https://qalerts.app/data/json/posts.json
https://qanon.pub/data/json/posts.json
https://keybase.pub/qntmpkts/data/json/posts.json

It is imperative that your JSON file is up-to-date before the sqraper finds any new posts since it adds newly found
posts onto the existing posts.json file when they are found.

You will also more than likely need to grab all of the images contained in posts thus far from a site operator who
already has them all, or you can find them here:

https://keybase.pub/qntmpkts/data/media/

Special thanks to "qntmpkts" for all of his work compiling posts since the very beginning! He has been a pioneer in
the movement to say the least. 

As of the writing of this, you will have to install LOKINET from https://loki.network/ and run this script with "lokiKun"
set to true. This is because 8kun DDoS protection is blocking scripts.

The first time you run the script it will create the configuration file "sqraper_config.json" in the same folder as
sqraper.php lives in. You can then edit the file to set your configuration (or you can create it from scratch prior).
Once the sqraper is running you can change "sqraper_config.json" anytime to make config changes and have them applied
to the already running script since the config file is re-read at the end of each loop.

To have the script send files via FTP you will in most cases need to add (or uncomment) the FTP extension to your PHP.ini
file. This varies depending on your operating system and PHP version. If you run into issues or questions just google it.
Here are some basics:

WINDOWS 10:

  extension=php_openssl.dll
  extension=php_ftp.dll

LINUX:

  Depending on your Linux version these may already be enabled. For example: On the "Ubunti Server LTS" machine I am
  running the script on, all that was needed was enabled by default. For standard desktop Ubuntu versions it is my
  guess you will need to enable the extensions.
  
  extension=ftp
  
  You may also have to add sockets. For some older versions it may be ftp.so and sockets.so. The script should
  throw an error and let you know if something required has not been enabled. Just google it. I am far from fluent
  in Linux.

-----------------

RUNNING THE SCRIPT:

WARNING: Even though this script is a PHP script, it is NOT intended to be run as a web page. Due to the fact that the
sqraper_config.json configuration file lives in the same folder as the script, if you enter your FTP credentials, not
to mention path info in the config file, anyone can access and view your config file via a browser. Bad times.

It is assume you have PHP installed and configured, and that you also have LOKINET from https://loki.network/ installed
and connected.

  WINDOWS: Open a command prompt (cmd) and type "PHP Sqraper.php" and press enter.
  
  LINUX: Open a terminal sessions and type "PHP Sqraper.php" and press enter.

To exit the script just press CTRL + C.

