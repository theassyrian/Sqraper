#!/bin/bash

# This is just a sample of the file startup.sh which I placed in my ~/ (home) folder on the Linux Server installation.
# sudo nano ~/startup.sh

# After you create the file you will need to give it execute permissions.
# chmod +x ~/startup.sh

# I then edited ~/.profile and added the line ~/startup.sh
# sudo nano ~/profile

# Keep in mind Ubuntu Server LTS is terminal console only (no GUI desktop). It starts everything I need once I login.

# I set the terminal to blank the screen after 5 minutes.
setterm -blank 30

number=0
read -t 15 -p "Choose Network to Start and press ENTER: 0 for Clearnet, 1 for Loki or 2 for Tor > " number
case $((number % 3)) in

        0)
                # Start the Clear network.
                echo "Clearnet..."
                echo
                echo "Waiting 15 seconds to start Sqraper (Clear)..."
                sleep 15
                # Start the sqraper.
                (cd ~/Sqraper && php ~/Sqraper/sqraper.php)
                ;;

        1)
                # Start the Loki network.
                echo "Starting Loki..."
                echo YOURPASSWORD | sudo -S systemctl start lokinet
                echo
                echo
                echo "Waiting 15 seconds to start Sqraper (Loki)..."
                sleep 15
                # Start the sqraper.
                (cd ~/Sqraper && php ~/Sqraper/sqraper.php)
                ;;

        2)
                # Start the Tor network.
                echo "Starting Tor..."
                echo YOURPASSWORD | sudo -S systemctl start tor
                echo
                echo
                echo "Waiting 15 seconds to start Sqraper (Tor)..."
                sleep 15
                # Start the sqraper.
                (cd ~/Sqraper && torsocks php ~/Sqraper/sqraper.php --passive-ftp)
                ;;

        *)
                # Start the Clear network (default).
                echo "Clearnet (defaulted)..."
                echo
                echo "Waiting 15 seconds to start Sqraper (Clear)..."
                sleep 15
                # Start the sqraper.
                (cd ~/Sqraper && php ~/Sqraper/sqraper.php)
                ;;

esac
