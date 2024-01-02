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

use JetBrains\PhpStorm\ArrayShape;
use JsonException;

/**
 * KDE Plasma Light / Dark Mode Toggle
 *
 * @since 1.0.0
 */
class ToggleDark
{
	private const CACHE_FILE = 'toggle-dark.cache';

	private Config $config;

	public function __construct(Config $config = null)
	{
		$this->config = $config ?? new Config();
	}

	/**
	 * Toggle the Plasma theme, if necessary
	 *
	 * @return  bool  True if switching themes took place
	 * @since   1.0.0
	 */
	public function autoToggleTheme(): bool
	{
		$currentScheme = $this->getCurrentScheme();
		$bestScheme    = $this->getBestScheme();

		if ($currentScheme === $bestScheme)
		{
			return false;
		}

		$command = sprintf(
			'%s %s',
			escapeshellcmd('/usr/bin/plasma-apply-colorscheme'),
			escapeshellarg($bestScheme)
		);

		exec($command);

		return true;
	}

	/**
	 * Apply the dark theme
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function forceDark(): void
	{
		$command = sprintf(
			'%s %s',
			escapeshellcmd('/usr/bin/plasma-apply-colorscheme'),
			escapeshellarg($this->config->darkScheme)
		);

		exec($command);

		//exec('gsettings set org.gnome.desktop.interface color-scheme \'prefer-dark\'');
	}

	/**
	 * Apply the light theme
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function forceLight(): void
	{
		$command = sprintf(
			'%s %s',
			escapeshellcmd('/usr/bin/plasma-apply-colorscheme'),
			escapeshellarg($this->config->lightScheme)
		);

		exec($command);

		exec('gsettings set org.gnome.desktop.interface color-scheme \'prefer-light\'');
	}

	/**
	 * Returns the name of the currently active Plasma colour scheme
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	private function getCurrentScheme(): string
	{
		$cmd = escapeshellcmd('LC_ALL=C plasma-apply-colorscheme -l') . '|' . escapeshellcmd('grep current');
		exec($cmd, $output);

		if (empty($output))
		{
			return '';
		}

		$output = reset($output);

		if (!str_starts_with($output, ' * '))
		{
			return '';
		}

		$output = ltrim($output, ' *');
		[$scheme, ] = @explode('(current', $output, 2);

		return $scheme ?: '';
	}

	/**
	 * Returns the identifier of the best applicable theme (light or dark), based on sunrise/sunset info.
	 *
	 * @return  string
	 */
	private function getBestScheme(): string
	{
		$coordinates = $this->getCoordinates();

		extract($coordinates);

		if ($latitude === null || $longitude === null)
		{
			$latitude  = ini_get('date.default_latitude') ?: $this->config->defaultLat;
			$longitude = ini_get('date.default_longitude') ?: $this->config->defaultLon;
		}

		$currentTime = time();
		$info        = date_sun_info($currentTime, $latitude, $longitude);
		$startTime   = $this->config->useCivicTwilight ? $info['civil_twilight_begin'] : $info['sunrise'];
		$endTime     = $this->config->useCivicTwilight ? $info['civil_twilight_end'] : $info['sunset'];
		$isDaylight  = $currentTime >= $startTime && $currentTime <= $endTime;

		return $isDaylight ? $this->config->lightScheme : $this->config->darkScheme;
	}

	/**
	 * Get the latitude and longitude of the current location (or the forced configured location, if GeoIP is disabled).
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	#[ArrayShape([
		"latitude"  => "float",
		"longitude" => "float",
	])]
	private function getCoordinates(): array
	{
		if (!$this->config->useGeoIP)
		{
			return [
				'latitude'  => $this->config->defaultLat,
				'longitude' => $this->config->defaultLon,
			];
		}

		$ret = $this->getCoordinatesFromCache();

		if ($ret !== null)
		{
			return $ret;
		}

		$ipInfo = @file_get_contents('http://ip-api.com/json/');

		if ($ipInfo === false)
		{
			return [
				'latitude'  => null,
				'longitude' => null,
			];
		}

		try
		{
			$ipInfo = @json_decode($ipInfo, true, flags: JSON_THROW_ON_ERROR);
		}
		catch (JsonException $e)
		{
			return $this->getCoordinatesFromCache(true) ?? [
				'latitude'  => null,
				'longitude' => null,
			];
		}

		$cacheFile = $_SERVER['HOME'] . '/.config/' . self::CACHE_FILE;
		$ret       = [
			'latitude'  => $ipInfo['lat'],
			'longitude' => $ipInfo['lon'],
		];

		$document = <<< INI
latitude={$ipInfo['lat']}
longitude={$ipInfo['lon']}
ip={$ipInfo['query']}

INI;

		file_put_contents($cacheFile, $document);

		return $ret;
	}

	/**
	 * Return the cached latitude and longitude.
	 *
	 * @param   bool  $ignoreLifetime  True to ignore the cache lifetime
	 *
	 * @return  float[]|null
	 * @since   1.0.0
	 */
	private function getCoordinatesFromCache(bool $ignoreLifetime = false): array|null
	{
		$cacheFile = $_SERVER['HOME'] . '/.config/' . self::CACHE_FILE;
		$lastCheck = file_exists($cacheFile) ? filemtime($cacheFile) : 0;
		$checkMyIp = false;

		if (!$ignoreLifetime && time() - $lastCheck > ($this->config->cacheLifetime * 3600))
		{
			$checkMyIp = true;
		}

		$config = file_exists($cacheFile) ? @parse_ini_file($cacheFile) : false;

		if ($config === false)
		{
			return null;
		}

		$latitude  = $config['latitude'] ?? null;
		$longitude = $config['longitude'] ?? null;

		if (!is_numeric($latitude) || !is_numeric($longitude))
		{
			return null;
		}

		if ($checkMyIp)
		{
			$lastIp = $config['ip'] ?? null;

			if ($lastIp === null)
			{
				return null;
			}

			$externalIP = $this->getExternalIp();

			if ($externalIP === null || $externalIP !== $lastIp)
			{
				return null;
			}
		}

		return [
			'latitude'  => (float) $latitude,
			'longitude' => (float) $longitude,
		];
	}

	/**
	 * Get the external IP address. Returns NULL if we cannot retrieve the external IP address.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	private function getExternalIp(): ?string
	{
		static $myIp = null;

		if (is_string($myIp))
		{
			return $myIp;
		}

		$myIp = @file_get_contents('https://checkip.amazonaws.com/');

		if (filter_var($myIp, FILTER_VALIDATE_IP) === false)
		{
			$myIp = null;
		}

		return $myIp;
	}
}