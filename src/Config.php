<?php
/**
 * @package   ToggleDark
 * @copyright Copyright (c) 2023 Nicholas K. Dionysopoulos
 * @license   GPLv3+
 *
 * Toggle Dark — Automatically toggle between a dark and light KDE Plasma global theme.
 * Copyright (C) 2023  Nicholas K. Dionysopoulos
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
namespace Dionysopoulos\ToggleDark;

/**
 * Configuration handling
 *
 * @property string $darkTheme        Dark global theme identifier
 * @property string $lightTheme       Light global theme identifier
 * @property bool   $resetLayout      Should the global theme reset the layout (also applies wallpaper)?
 * @property bool   $useGeoIP         Should I use GeoIP?
 * @property int    $cacheLifetime    Cache lifetime for GeoIP, in hours
 * @property float  $defaultLon       Default longitude
 * @property float  $defaultLat       Default latitude
 * @property bool   $useCivicTwilight Should I use the civic twilight instead of sunrise / sunset?
 *
 * @since    1.0.0
 */
class Config
{
	private bool $useGeoIP = true;

	private float $defaultLon = 23.7353;

	private float $defaultLat = 37.9842;

	private bool $useCivicTwilight = true;

	private string $darkTheme = 'org.kde.breezedark.desktop';

	private string $lightTheme = 'org.kde.breeze.desktop';

	private int $cacheLifetime = 6;

	private bool $resetLayout = false;

	private const CONFIG_FILE = 'toggle-dark';

	public function __construct()
	{
		$this->load();
	}

	public function load(): void
	{
		$file = $_SERVER['HOME'] . '/.config/' . self::CONFIG_FILE;

		if (!file_exists($file))
		{
			$this->save();

			return;
		}

		$config = @parse_ini_file($file);

		if (!is_array($config))
		{
			$config = [];
		}

		$this->useGeoIP         = boolval($config['geoip'] ?? $this->useGeoIP);
		$this->defaultLon       = floatval($config['longitude'] ?? $this->defaultLon);
		$this->defaultLat       = floatval($config['latitude'] ?? $this->defaultLat);
		$this->useCivicTwilight = boolval($config['civic_twilight'] ?? $this->useCivicTwilight);
		$this->darkTheme        = $config['dark_theme'] ?? $this->darkTheme;
		$this->lightTheme       = $config['light_theme'] ?? $this->lightTheme;
		$this->cacheLifetime    = $config['cache_lifetime'] ?? $this->cacheLifetime;
		$this->resetLayout      = $config['reset_layout'] ?? $this->resetLayout;
	}

	public function save(): void
	{
		$file          = $_SERVER['HOME'] . '/.config/' . self::CONFIG_FILE;
		$geoIP         = $this->useGeoIP ? '1' : '0';
		$civicTwilight = $this->useCivicTwilight ? '1' : '0';
		$resetLayout   = $this->resetLayout ? '1' : '0';
		$document      = <<< INI
dark_theme={$this->darkTheme}
light_theme={$this->lightTheme}
reset_layout={$resetLayout}
geoip={$geoIP}
cache_lifetime={$this->cacheLifetime}
longitude={$this->defaultLon}
latitude={$this->defaultLat}
civic_twilight={$civicTwilight}

INI;
		file_put_contents($file, $document);
	}

	public function __get(string $name)
	{
		if (!isset($this->{$name}))
		{
			throw new \InvalidArgumentException(
				sprintf(
					"Property %s does not exist in the configuration.",
					$name
				)
			);
		}

		return $this->{$name};
	}

	public function __set(string $name, $value): void
	{
		if (!isset($this->{$name}))
		{
			throw new \InvalidArgumentException(
				sprintf(
					"Property %s does not exist in the configuration.",
					$name
				)
			);
		}

		$this->{$name} = $value;
	}
}