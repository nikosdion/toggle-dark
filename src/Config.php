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
 * @property string $darkScheme       Dark KDE colour scheme identifier
 * @property string $lightScheme      Light KDE colour scheme theme identifier
 * @property bool   $useGeoIP         Should I use GeoIP?
 * @property int    $cacheLifetime    Cache lifetime for GeoIP, in hours
 * @property int    $lightOffset      Apply light theme offset, in minutes
 * @property int    $darkOffset       Apply dark theme offset, in minutes
 * @property float  $defaultLon       Default longitude
 * @property float  $defaultLat       Default latitude
 * @property bool   $useCivicTwilight Should I use the civic twilight instead of sunrise / sunset?
 *
 * @since    1.0.0
 */
class Config
{
	private const CONFIG_FILE = 'toggle-dark';

	private bool $useGeoIP = true;

	private float $defaultLon = 23.7353;

	private float $defaultLat = 37.9842;

	private bool $useCivicTwilight = true;

	private string $darkScheme = 'BreezeLight';

	private string $lightScheme = 'BreezeDark';

	private int $cacheLifetime = 6;

	private int $lightOffset = 0;

	private int $darkOffset = 0;

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
		$this->lightOffset      = intval($config['light_offset'] ?? $this->lightOffset);
		$this->darkOffset       = intval($config['dark_offset'] ?? $this->darkOffset);
		$this->darkScheme       = $config['dark_scheme'] ?? $this->darkScheme;
		$this->lightScheme      = $config['light_scheme'] ?? $this->lightScheme;
		$this->cacheLifetime    = $config['cache_lifetime'] ?? $this->cacheLifetime;
	}

	public function save(): void
	{
		$file          = $_SERVER['HOME'] . '/.config/' . self::CONFIG_FILE;
		$geoIP         = $this->useGeoIP ? '1' : '0';
		$civicTwilight = $this->useCivicTwilight ? '1' : '0';
		$document      = <<< INI
dark_scheme={$this->darkScheme}
light_scheme={$this->lightScheme}
geoip={$geoIP}
cache_lifetime={$this->cacheLifetime}
light_offset={$this->lightOffset}
dark_offset={$this->darkOffset}
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