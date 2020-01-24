Tor Setup and TorSock on Windows:

I have not yet done this on Windows. Assuming it is able to be done, the Sqraper will have to be
launched via TorSocks, much like the Linux notes. Example: "torsocks php sqraper.php"
One thing is for sure, if uploading JSON and media via FTP, when using Tor, you will have to also
install CURL and make sure curl.exe is in your environmental variables path. https://curl.haxx.se/
This is because we have to spawn a shell outside of the TorSock and use cURL (or something else via a shell),
otherwise, FTP will send ONE file and will then error out beyond that. Something to do with Tor.

Tor Setup on Linux via Terminal:

https://wildcardcorp.com/blogs/tor-torify-torsocks

sudo apt-get install tor

sudo vi /etc/tor/torrc or sudo nano /etc/tor/torrc

Uncomment the ControlPort 9051 and CookieAuthentication 1 lines, then change CookieAuthentication 1 to CookieAuthentication 0 to disable authentication. DO NOT do this on a shared computer. Then restart tor:

sudo /etc/init.d/tor restart 

Make a New Session:
  echo -e 'AUTHENTICATE ""\r\nsignal NEWNYM\r\nQUIT' | nc 127.0.0.1 9051

Test torify:

This should show you your public IP:

  curl ifconfig.me 

This should show a completely different IP (and outputs torify error messages to /dev/null):

  torify curl ifconfig.me 2> /dev/null 

https://www.linuxuprising.com/2018/10/how-to-install-and-use-tor-as-proxy-in.html

sudo apt install apt-transport-https curl

sudo -i

echo "deb https://deb.torproject.org/torproject.org/ $(lsb_release -cs) main" > /etc/apt/sources.list.d/tor.list

curl https://deb.torproject.org/torproject.org/A3C4F0F979CAA22CDBA8F512EE8CBC9E886DDD89.asc | gpg --import

gpg --export A3C4F0F979CAA22CDBA8F512EE8CBC9E886DDD89 | apt-key add -

apt update

exit

sudo apt install tor tor-geoipdb torsocks deb.torproject.org-keyring

TEST:
Get Real IP: curl ipv4.icanhazip.com
Get Tor IP: torsocks curl ipv4.icanhazip.com

If you get an error, the Tor service may not be running. It should be automatically started when it's installed, but in case it's not, you can start it using this command:

sudo systemctl start tor

LAUNCH Sqraper Via Tor (obviously change the sqraper_config.json so that Sqraper uses Tor as well)

torsocks php sqraper.php
