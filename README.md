# Toggle Dark

Automatically toggle between a dark and light KDE Plasma global theme.

[Download](https://github.com/nikosdion/toggle-dark/releases/latest) • [Issues](https://github.com/nikosdion/toggle-dark/issues)

## Installation

You can [download](https://github.com/nikosdion/toggle-dark/releases/latest) or [build](#building-the-phar) the `toggle-dark.phar` executable PHP Archive (PHAR) file of this software. Install it system-wide with:
```bash
chown +x toggle-dark.phar 
sudo cp toggle-dark.phar /usr/local/bin/toggle-dark
```

## Usage

This tool can be used to toggle between a Light and Dark global Plasma theme based on the sunrise and sunset.

By default, it uses the Breeze and Breeze Dark themes shipped with KDE Plasma. The sunrise and sunset is calculated based on your approximate position, as determined by your public IP address. Your theme switches to Light mode after the civil dawn (which is a few minutes before the sun's disk appears over the horizon) and to Dark Mode after the civil dusk (which is a few minutes after the sun's disk disappears over the horizon). You can change this behaviour through the [configuration file `~/.config/toggle-dark`](#configuration).

The following instructions assume that the software is installed system-wide [as explained above](#installation).

### Manual switching

Use the manual switching to set the Light or Dark mode, or toggle between them, regardless of the sunrise and sunset time. This is useful, for example, if you are entering a dark room during the day, or a bright room during the night.

To switch between the Light and Dark global theme:
```bash
toggle-dark toggle
```
If the currently apply theme is neither the configured Light nor Dark theme this software will apply the Dark theme.

To forcibly apply the Light global theme:
```bash
toggle-dark light
```

To forcibly apply the Dark global theme:
```bash
toggle-dark dark
```

### Semi-automatic switching

To switch to the Light or Dark theme according to the sunrise and sunset times just run:

```bash
toggle-dark autotoggle
```

This is called _semi_-automatic mode because you need to run this command youself; it does not run automatically.

### Automatic switching

Having to run a command manually is a pain, and only marginally better than doing it manually through System Settings, or with a simple Bash script.

Toggle Dark supports a fully automated switching mode using CRON jobs. Running
```bash
toggle-dark update
```
will install three CRON jobs for the current user:
* Once an hour `toggle-dark update` to update the CRON jobs with the correct sunrise/sunset times.
* Once a day at sunrise `toggle-dark light` to switch to the Light global theme at dawn, or any time after sunrise you log into your computer.
* Once a day at sunset `toggle-dark dark` to switch to the Dark global theme at dusk, or any time after sunset you log into your computer.

You can remove these CRON jobs either manually (using `crontab -e`), or automatically with
```bash
toggle-dark uninstall
```

## Configuration

Toggle Dark uses an INI-format configuration file, `~/.config/toggle-dark`. The file is created if it does not exist.

### Theme configuration

```ini
dark_theme=org.kde.breezedark.desktop
light_theme=org.kde.breeze.desktop
reset_layout=0
```

The first two configuration options need the identifier of an installed global theme. You can list these identifiers with `lookandfeeltool -l`.

When the `reset_layout` option is set to 1, Toggle Dark will apply the desktop and window layout of the applicable (light or dark) global theme. Among other changes, this also applies the wallpaper defined in the theme. By default, this option is disabled so that your wallpaper doesn't change when switching between light and dark mode.

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

When `civic_twilight` is set to 1, Toggle Dark will use the [civil dawn and civil dusk](https://www.timeanddate.com/astronomy/civil-twilight.html) to determine when to switch between the Light and Dark themes. The Light global theme is applied before the Sun's disk appears over the horizon and Dark global theme is applied after the Sun's disk disappears below the horizon. This is on purpose, as in most location's there's enough light during these two civic twilight phases for people to still call it “daytime”. The amount of time spent in these two twilight phases depends on the time of year and your latitude.

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

### Can I just change the color scheme / application style?

No. You can already do that with [YingYang](https://github.com/oskarsh/Yin-Yang). This tool covers the missing use case of needing to change the entire global theme, not just the color scheme and application style.

### Why does the wallpaper not change?

By default, we apply the global theme with `lookandfeeltool -a` without any additional parameters. This does not apply the wallpaper.

If you'd like the wallpaper defined in the global theme to be applied use [the `reset_layout` configuration parameter](#theme-configuration).

### What if I want to change the wallpaper to something other than what the theme provides?

KDE Plasma supports wallpapers which have separate light and dark mode resources. See, for example, the Flow wallpaper (installed by default in `/usr/share/wallpapers/Flow/contents/images` on KDE Neon).

Alternatively, you could use [YingYang](https://github.com/oskarsh/Yin-Yang) _together with_ Toggle Dark. Just make sure that the only thing you are changing with YingYang is the background image, not the color theme or the application style (the latter two are part of the global theme).

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
