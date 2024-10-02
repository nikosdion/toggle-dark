# Toggle Dark

Automatically toggle between a dark and light KDE Plasma 5 or 6 color scheme.

[Download](https://github.com/nikosdion/toggle-dark/releases/latest) • [Issues](https://github.com/nikosdion/toggle-dark/issues)

## Installation

You can [download](https://github.com/nikosdion/toggle-dark/releases/latest) or [build](#building-the-phar) the `toggle-dark.phar` executable PHP Archive (PHAR) file of this software. Install it system-wide with:
```bash
chown +x toggle-dark.phar 
sudo cp toggle-dark.phar /usr/local/bin/toggle-dark
```

This does not take into account two use cases where a toggle between the dark and light colour scheme is necessary:

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

This will allow the dark/light colour scheme to toggle even in the two use cases explained above.

## Usage

This tool can be used to toggle between a Light and Dark KDE Plasma colour scheme based on the sunrise and sunset.

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

If you want to toggle from Light to Dark, or vice versa:
```bash
toggle-dark toggle
```

> ℹ️ **Tip**: You can create a shortcut to toggle the colour scheme manually. Go to System Settings, Keyboard, Shortcuts, Add New, Command or Script. Enter `/usr/local/bin/toggle-dark` as the command. I recommend using an easy to remember shortcut, e.g. CTRL-SHIFT-Monitor Brightness Down.

### Semi-automatic switching

To switch to the Light or Dark colour scheme according to the sunrise and sunset times just run:

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

> ‼️ **WARNING**: Most distributions with newer versions of systemd (e.g. Ubuntu and derivatives) come with the CRON daemon _disabled_ by default. You will need to run `sudo apt install cron; sudo systemctl enable --now cron.service` to enable the CRON daemon.

Set up a CRON job which runs every minute using `crontab -e` like so:
```cronexp
* * * * * /usr/bin/php8.2 /usr/local/bin/toggle-dark 1>/dev/null
```

Please note that having this CRON job enabled will cause the colour scheme to toggle automatically every minute. If you are trying to force the colour scheme to stay Light or Dark it will appear to not work. The CRON job would be why.

Alternatively, use `toggle-dark update` to install three CRON jobs which toggle the theme on sunrise and sundown, and one which updates these CRON jobs every 3 hours (necessary because the sunrise and sundown time changes each day of the year).

## Configuration

Toggle Dark uses an INI-format configuration file, `~/.config/toggle-dark`. The file is created if it does not exist.

### Colour scheme configuration

```ini
dark_scheme=BreezeDark
light_scheme=BreezeLight
```

The first two configuration options need the identifier of an installed colour scheme. You can list these identifiers with `plasma-apply-colorscheme -l`.

### Geography

```ini
geoip=1
cache_lifetime=6
longitude=23.7353
latitude=37.9842
```

Toggle Dark needs to know where you physically are to calculate the correct sunrise and sunset time.

When `geoip=1` Toggle Dark will be doing two network requests. One is to `checkip.amazonaws.com` to get your external IP address, and one to `ip-api.com` to determine your longitude and latitude (geographic coordinates). The geographic coordinates are used to determine the sunrise and sunset time. After `cache_lifetime` hours it will contact `checkip.amazonaws.com` again to check your IP address. If it has changed it will contact `ip-api.com` again.

If you'd rather not use any third party service –and completely prevent network access– you can set `geoip=0` and set up your location's longitude and latitude as decimal degrees. Longitude is positive in the Eastern hemisphere and negative in the Western hemisphere. Latitude is positive in the North hemisphere and negative in the South hemisphere.

> ℹ️ **Tip**: You can find these coordinates by searching “<city name> longitude latitude” in any search engine, or by consulting a GPS device. Remember to use decimal degress, NOT degrees-minutes-seconds.

### Sunset and sunrise calculation

```ini
civic_twilight=1
light_offset=0
dark_offset=0
```

When `civic_twilight` is set to 1, Toggle Dark will use the [civil dawn and civil dusk](https://www.timeanddate.com/astronomy/civil-twilight.html) to determine when to switch between the Light and Dark colour schemes. The Light colour scheme is applied before the Sun's disk appears over the horizon and Dark colour scheme is applied after the Sun's disk disappears below the horizon. This is on purpose, as in most location's there's enough light during these two civic twilight phases for people to still call it “daytime”. The amount of time spent in these two twilight phases depends on the time of year and your latitude.

When `civic_twilight` is set to 0, Toggle Dark will use the actual sunrise and sunset time, i.e. when the geometric centre of the Sun's disk crosses the horizon. This may not be desirable if you live close to big hills, mountains, or tall buildings which block much of the twilight.

When `light_offset` is greater than zero, the light colour scheme will be applied this many minutes _after_ the sunrise / civil dawn time. When it's less than zero, the light colour scheme will be applied this many minutes _before_ the sunrise / civil dawn time. Generally, you need to use positive values to take into account the shading from tall structures, nearby hills etc.

When `dark_offset` is greater than zero, the dark colour scheme will be applied this many minutes _after_ the sunset / civil dusk time. When it's less than zero, the dark colour scheme will be applied this many minutes _after_ the sunrise / civil dawn time. Generally, you need to use negative values to take into account the shading from tall structures, nearby hills etc.

Rule of thumb: I have found that living in a city surrounded by low hills and 6-storey buildings I have best results using `civic_twilight=1`, `light_offset=15`, and `dark_offset=-20`. 

## Requirements

* Linux, *BSD, or compatible Operating System
* KDE Plasma desktop, version 5 or 6
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

### Why do you change the colour scheme, instead of the global theme?

It's very simple for any user to create a new colour scheme in KDE Plasma 5 and 6. It is a far more difficult task creating a new global theme. As a result, changing global themes instead of colour schemes would be a pain for most users, as it would reset their desktop backgrounds, icons, window decorations etc. 

The power of KDE Plasma is that you can use all those extra personalisation items to skin it so thoroughly it might look near darned identical to a different OS (e.g. Windows, macOS, ...), or even give it a completely new look (there are so many cyberpunk, goth, etc examples).

Asking users to learn how to create global themes to maintain this kind of deep customisation while toggling between light and dark mode was an overkill, which is why we are not switching global themes, and decided to stick with colour schemes instead. Not to mention that changing the global theme was oftentimes buggy, especially with regard to switching the Gtk theme, which is what most Linux desktop applications (e.g. browsers) look for when determining if they're running on Dark Mode.

### Can I change the global theme?

Nope. I tried that, but sometimes the changeover was finicky, resulting in some Gtk application not switching over correctly.

### Why does the wallpaper not change?

Because we are only changing the colour theme.

KDE Plasma supports wallpapers which change automatically between a light and dark theme, e.g. the built-in Flow wallpaper. This is the best way to have the wallpaper change automatically.

## Copyright and license statement

Toggle Dark — Automatically toggle between a dark and light KDE Plasma global theme.

Copyright (C) 2023-2024 Nicholas K. Dionysopoulos

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
