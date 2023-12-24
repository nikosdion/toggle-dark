# Toggle Dark

Automatically toggle between a dark and light KDE Plasma global theme.

[Download](https://github.com/nikosdion/toggle-dark/releases/latest) • [Issues](https://github.com/nikosdion/toggle-dark/issues)

## Installation

You can [download](https://github.com/nikosdion/toggle-dark/releases/latest) or [build](#building-the-phar) the `toggle-dark.phar` executable PHP Archive (PHAR) file of this software. Install it system-wide with:
```bash
chown +x toggle-dark.phar 
sudo cp toggle-dark.phar /usr/local/bin/toggle-dark
```

This does not take into account two use cases where a toggle between the dark and light theme is necessary:

* Suspending your computer after sunset and resuming it after dawn (or suspending after dawn and resuming after sunset).
* Suspending your computer between noon and midnight and resuming it after dawn.

To address these problems you need to have the Toggle Dark update to run on Plasma login and screen unlocking.

* Go to System Settings, Notifications and click on Configure next to Applications.
* Click on Plasma Workspace
* Click on Configure Events
* Select Login
* Check the Run Command checkbox and enter `/usr/local/bin/toggle-dark` in the text box next to it.
* Click on OK
* Click on Screen Saver
* Click on Configure Events
* Select Screen Unlocked
* Check the Run Command checkbox and enter `/usr/local/bin/toggle-dark` in the text box next to it.
* Click on OK
* Click on Apply

This will allow the dark/light theme to toggle even in the two use cases explained above.

## Usage

This tool can be used to toggle between a Light and Dark KDE Plasma colour theme based on the sunrise and sunset.

By default, it uses the Breeze Light and Breeze Dark colour schemes shipped with KDE Plasma. The sunrise and sunset is calculated based on your approximate position, as determined by your public IP address. Your colour scheme switches to Light mode after the civil dawn (which is a few minutes before the sun's disk appears over the horizon) and to Dark Mode after the civil dusk (which is a few minutes after the sun's disk disappears over the horizon). You can change this behaviour through the [configuration file `~/.config/toggle-dark`](#configuration).

The following instructions assume that the software is installed system-wide [as explained above](#installation).

### Manual switching

Use the manual switching to set the Light or Dark mode, or toggle between them, regardless of the sunrise and sunset time. This is useful, for example, if you are entering a dark room during the day, or a bright room during the night.

To forcibly apply the Light colour scheme:
```bash
toggle-dark light
```

To forcibly apply the Dark colour scheme:
```bash
toggle-dark dark
```

### Semi-automatic switching

To switch to the Light or Dark theme according to the sunrise and sunset times just run:

```bash
toggle-dark autotoggle
```

or, simply,

```bash
toggle-dark
```

This is called _semi_-automatic mode because you need to run this command yourself; it does not run automatically.

### Automatic switching

Having to run a command manually is a pain, and only marginally better than doing it manually through System Settings, or with a simple Bash script.

Toggle Dark supports a fully automated switching mode using CRON jobs.

Set up a CRON job which runs every minute using `crontab -e` like so:
```cronexp
* * * * * /usr/bin/php8.2 /usr/local/bin/toggle-dark 1>/dev/null
```

## Configuration

Toggle Dark uses an INI-format configuration file, `~/.config/toggle-dark`. The file is created if it does not exist.

### Theme configuration

```ini
dark_scheme=BreezeDark
light_scheme=BreezeLight
```

The first two configuration options need the identifier of an installed colour theme. You can list these identifiers with `plasma-apply-colorscheme -l`.

### Geography

```ini
geoip=1
cache_lifetime=6
longitude=23.7353
latitude=37.9842
```

Toggle Dark needs to know where you physically are to calculate the correct sunrise and sunset time.

When `geoip=1` it will use the third party GeoIP service ip-api.com. Moreover, it will cache your location in the file `~/.config/toggle-dark.cache` along with your external IP address for the number of hours specified in the `cache_lifetime` setting. After this many hours, it will check your external IP address using the third party checkip.amazonaws.com service. If your IP has changed, it will retrieve your location again from the GeoIP service.

If you'd rather not use any third party service you can set `geoip=0` and set up your location's longitude and latitude as decimal degrees. Longitude is positive in the Eastern hemisphere and negative in the Western hemisphere. Latitude is positive in the North hemisphere and negative in the South hemisphere.

### Sunset and sunrise calculation

```ini
civic_twilight=1
```

When `civic_twilight` is set to 1, Toggle Dark will use the [civil dawn and civil dusk](https://www.timeanddate.com/astronomy/civil-twilight.html) to determine when to switch between the Light and Dark colour schemes. The Light colour scheme is applied before the Sun's disk appears over the horizon and Dark colour scheme is applied after the Sun's disk disappears below the horizon. This is on purpose, as in most location's there's enough light during these two civic twilight phases for people to still call it “daytime”. The amount of time spent in these two twilight phases depends on the time of year and your latitude.

When `civic_twilight` is set to 0, Toggle Dark will use the actual sunrise and sunset time, i.e. when the geometric centre of the Sun's disk crosses the horizon. This may be desirable if you live close to big hills, mountains, or tall buildings which block much of the twilight.

## Requirements

* Linux, *BSD, or compatible Operating System
* KDE Plasma desktop, version 5
* PHP 8.0 or later

On Debian derivatives (e.g. Kubuntu, KDE Neon, Tuxedo OS, …) you can install the required PHP packages with:

```bash
sudo apt install php-cli php-json plasma-workspace
```

## Building the PHAR

### Prerequisites

* PHP 8.0 or later
* Composer
* Box

On Debian derivatives (e.g. Kubuntu, KDE Neon, Tuxedo OS, …) you can install the prerequisites with:
```bash
sudo apt install php-cli php-json composer
composer global require humbug/box "^4.2"
```

Make sure that `$HOME/.config/composer` is in your path, e.g.
```bash
echo "PATH=\$PATH:\$HOME/.config/composer" >> ~/.bashrc
source ~/.bashrc
```

### Building the PHAR file

Build the PHAR file by running
```bash
composer install
box compile
```
in the repository's root. This creates the file `release/toggle-dark.phar`.

### Installing the PHAR

You can install the PHAR file system-wide after building it with the following command:
```bash
sudo cp release/toggle-dark.phar /usr/local/bin/toggle-dark
```

## Preemptively Answered Questions

### Can I change the global theme?

No. This is what version 1 was doing, but sometimes the changeover was finicky, resulting in some Gtk application not switching over correctly.

### Why does the wallpaper not change?

Because we are only changing the colour theme.

KDE Plasma supports wallpapers which change automatically between a light and dark theme, e.g. the built-in Flow wallpaper. This is the best way to have the wallpaper change automatically.

## Copyright and license statement
Toggle Dark — Automatically toggle between a dark and light KDE Plasma global theme.

Copyright (C) 2023  Nicholas K. Dionysopoulos

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received [a copy of the GNU General Public License](LICENSE.md)
along with this program.  If not, see <https://www.gnu.org/licenses/>.
