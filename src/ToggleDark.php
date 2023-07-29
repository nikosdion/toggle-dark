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

use DateTimeZone;
use JsonException;
use TiBeN\CrontabManager\CrontabAdapter;
use TiBeN\CrontabManager\CrontabJob;
use TiBeN\CrontabManager\CrontabRepository;

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
	 * Returns the identifier of the currently active Plasma theme
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function getCurrentTheme(): string
	{
		[$theme,] = explode(
			"\n",
			@file_get_contents($_SERVER['HOME'] . '/.config/kdedefaults/package') ?: '',
			2
		);

		return $theme;
	}

	/**
	 * Toggle the Plasma theme, if necessary
	 *
	 * @return  bool  True if switching themes took place
	 * @since   1.0.0
	 */
	public function autoToggleTheme(): bool
	{
		$currentTheme = $this->getCurrentTheme();
		$bestTheme    = $this->getBestTheme();

		if ($currentTheme === $bestTheme)
		{
			return false;
		}

		$resetLayout = $this->config->resetLayout ? '--resetLayout' : '';
		$command     = sprintf('/usr/bin/lookandfeeltool -a %s %s', $resetLayout, $bestTheme);

		exec($command);

		return true;
	}

	/**
	 * Toggle the Plasma theme, always
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function toggleTheme(): void
	{
		$currentTheme = $this->getCurrentTheme();

		$bestTheme = ($currentTheme === $this->config->darkTheme)
			? $this->config->lightTheme
			: $this->config->darkTheme;

		$resetLayout = $this->config->resetLayout ? '--resetLayout' : '';
		$command     = sprintf('/usr/bin/lookandfeeltool -a %s %s', $resetLayout, $bestTheme);

		exec($command);
	}

	/**
	 * Apply the dark theme
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function forceDark(): void
	{
		$resetLayout = $this->config->resetLayout ? '--resetLayout' : '';
		$command     = sprintf('/usr/bin/lookandfeeltool -a %s %s', $resetLayout, $this->config->darkTheme);

		exec($command);
	}

	/**
	 * Apply the light theme
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function forceLight(): void
	{
		$resetLayout = $this->config->resetLayout ? '--resetLayout' : '';
		$command     = sprintf('/usr/bin/lookandfeeltool -a %s %s', $resetLayout, $this->config->lightTheme);

		exec($command);
	}

	/**
	 * Update the theme auto-switch CRON job
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function updateCRON(): void
	{
		[$sunrise, $sunset] = $this->getSunriseSunsetTime();

		$commandProto = PHP_BINARY . ' ' . TOGGLE_DARK_SELF . ' %s';

		$cronJobSunrise = (new CrontabJob())
			->setMinutes($sunrise->format('i'))
			->setHours($sunrise->format('H'))
			->setDayOfMonth($sunrise->format('d'))
			->setMonths($sunrise->format('n'))
			->setDayOfWeek('*')
			->setTaskCommandLine(sprintf($commandProto, 'light'))
			->setComments('Toggle Dark -- Sunrise');

		$cronJobSunset = (new CrontabJob())
			->setMinutes($sunset->format('i'))
			->setHours($sunset->format('H'))
			->setDayOfMonth($sunset->format('d'))
			->setMonths($sunset->format('n'))
			->setDayOfWeek('*')
			->setTaskCommandLine(sprintf($commandProto, 'dark'))
			->setComments('Toggle Dark -- Sunset');

		$cronJobCronUpdate = (new CrontabJob())
			->setMinutes('*/15')
			->setHours('*')
			->setDayOfMonth('*')
			->setMonths('*')
			->setDayOfWeek('*')
			->setTaskCommandLine(sprintf($commandProto, 'update'))
			->setComments('Toggle Dark -- Update CRON jobs');

		$this->uninstallCRON();

		$crontabRepository = new CrontabRepository(new CrontabAdapter());
		$crontabRepository->addJob($cronJobSunrise);
		$crontabRepository->addJob($cronJobSunset);
		$crontabRepository->addJob($cronJobCronUpdate);
		$crontabRepository->persist();
	}

	/**
	 * Uninstall the theme auto-switch CRON job
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function uninstallCRON(): void
	{
		$crontabRepository = new CrontabRepository(new CrontabAdapter());

		foreach ($crontabRepository->findJobByRegex('/Toggle Dark -- /') as $job)
		{
			$crontabRepository->removeJob($job);
		}

		$crontabRepository->persist();
	}

	/**
	 * Returns the identifier of the best applicable theme (light or dark), based on sunrise/sunset info.
	 *
	 * @return  string
	 */
	private function getBestTheme(): string
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

		if ($this->config->useCivicTwilight)
		{
			return $currentTime < $info['civil_twilight_begin'] || $currentTime > $info['civil_twilight_end']
				? $this->config->darkTheme
				: $this->config->lightTheme;
		}

		return $currentTime < $info['sunrise'] || $currentTime > $info['sunset']
			? $this->config->darkTheme
			: $this->config->lightTheme;
	}

	/**
	 * Get the latitude and longitude of the current location (or the forced configured location, if GeoIP is disabled).
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	#[\JetBrains\PhpStorm\ArrayShape([
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

	/**
	 * Get a DateTimeZone object of the system's timezone.
	 *
	 * @return  DateTimeZone
	 * @since   1.0.0
	 */
	private function getSystemTimezone(): DateTimeZone
	{
		// ATTEMPT #1 — The /etc/timezone file in Debian derivative distros
		$file = '/etc/timezone';

		if (file_exists($file) && is_file($file) && is_readable($file))
		{
			[$tz,] = explode("\n", file_get_contents($file), 2);

			try
			{
				return new DateTimeZone($tz);
			}
			catch (\Exception $e)
			{
				// Don't do anything; fall through.
			}
		}

		// ATTEMPT #2 — The date command
		exec('date +"%z"', $out, $code);

		if ($code === 0 && count($out))
		{
			try
			{
				return new DateTimeZone($out[0]);
			}
			catch (\Exception $e)
			{
				// Don't do anything; fall through.
			}
		}

		// ATTEMPT #3 — PHP's timezone, or fallback to GMT
		$tz = (function_exists('ini_get') ? ini_get('date.timezone') : '') ?: 'GMT';

		try
		{
			return new DateTimeZone($tz);
		}
		catch (\Exception $e)
		{
			return new DateTimeZone('GMT');
		}
	}

	/**
	 * Get the sunrise and sunset time of a given day.
	 *
	 * @param   int|null  $targetTimestamp  The timestamp of the day we're interested in. NULL = today.
	 *
	 * @return  array
	 */
	private function getSunriseSunsetTime(?int $targetTimestamp = null): array
	{
		$coordinates = $this->getCoordinates();

		/**
		 * Extracted variables
		 *
		 * @var ?int $latitude  The latitude of the current location
		 * @var ?int $longitude The longitude of the current location
		 */
		extract($coordinates);

		if ($latitude === null || $longitude === null)
		{
			$latitude  = ini_get('date.default_latitude') ?: $this->config->defaultLat;
			$longitude = ini_get('date.default_longitude') ?: $this->config->defaultLon;
		}

		// TODO Add/remove a fixed amount of time

		$targetTimestamp  ??= time();
		$info             = date_sun_info($targetTimestamp, $latitude, $longitude);
		$sunriseTimestamp = $this->config->useCivicTwilight
			? $info['civil_twilight_begin']
			: $info['sunrise'];
		$sunsetTimestamp  = $this->config->useCivicTwilight
			? $info['civil_twilight_end']
			: $info['sunset'];

		// Creating DateTime objects from timestamps always uses the GMT timezone, regardless of the third argument.
		$gmt     = new DateTimeZone('GMT');
		$sunrise = \DateTime::createFromFormat('U', $sunriseTimestamp, $gmt);
		$sunset  = \DateTime::createFromFormat('U', $sunsetTimestamp, $gmt);

		// Force the DateTime objects to be expressed in the host's timezone
		$sunrise->setTimezone($this->getSystemTimezone());
		$sunset->setTimezone($this->getSystemTimezone());

		return [$sunrise, $sunset];
	}
}
