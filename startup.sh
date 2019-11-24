#~/bin/sh

# This is just a sample of the file startup.sh which I placed in my ~/ (home) folder on the Linux Server installation.
# Keep in mind Ubuntu Server LTS is terminal console only (no GUI desktop). It starts everything I need once I login.

# Start the Loki network.
mypassword | sudo -S systemctl start lokinet

# I set the terminal to blank the screen after 5 minutes.
setterm -blank 5

# Just so I have a chance to visually review anything that may pop up or CTRL-C to make any changes.
sleep 15

# Start the sqraper.
(cd ~/Sqraper && php ~/Sqraper/Sqraper.php)
