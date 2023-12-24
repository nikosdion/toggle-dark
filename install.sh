#!/usr/bin/env bash
function yes_or_no {
  while true; do
    read -p "$* [y/n]: " yn
    case $yn in
    [Yy]*) return 0 ;;
    [Nn]*)
      echo "Aborted"
      return 1
      ;;
    esac
  done
}

echo "Installing Composer dependencies..."

which composer 1> /dev/null

if [ $? -ne 0 ]; then
  echo "You need to install Composer to compile this software. Please consult the README.md file."
  exit
fi

composer install

echo "Compiling into PHAR..."

which box 1>/dev/null

if [ $? -ne 0 ]; then
  echo "You need to install Box to compile this software. Please consult the README.md file."
  exit
fi

rm -rf release
box compile

if [ $? -ne 0 ]; then
  echo "Compilation failed. Aborting."
  exit
fi

chmod +x release/toggle-dark.phar

echo "Toggle Dark will be copied to /usr/local/bin/toggle-dark. Your root password is required for this."
sudo cp release/toggle-dark.phar /usr/local/bin/toggle-dark

yes_or_no "Would you like to run the auto-toggle now?" && toggle-dark