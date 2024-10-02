<?php
/**
 * @package   ToggleDark
 * @copyright Copyright (c) 2023 Nicholas K. Dionysopoulos
 * @license   GPLv3+
 *
 * Toggle Dark — Automatically toggle between a dark and light KDE Plasma colour scheme.
 * Copyright (C) 2023-2024 Nicholas K. Dionysopoulos
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

use DateTime;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\ObjectShape;
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
	 * Toggle the Plasma colour scheme, if necessary
	 *
	 * @return  bool  True if switching colour schemes took place
	 * @since   1.0.0
	 */
	public function autoToggleScheme(): bool
	{
		$currentScheme = $this->getCurrentScheme();
		$bestScheme    = $this->getBestScheme();

		if ($currentScheme === $bestScheme)
		{
			return false;
		}

		$command = sprintf(
			'%s --platform offscreen %s', escapeshellcmd('/usr/bin/plasma-apply-colorscheme'),
			escapeshellarg($bestScheme)
		);

		exec($command);

		return true;
	}

	/**
	 * Toggle the Plasma colour scheme, always
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function toggleScheme(): void
	{
		$currentScheme    = $this->getCurrentScheme();
		$isCurrerntlyDark = $currentScheme === $this->config->darkScheme;

		if ($isCurrerntlyDark)
		{
			$this->forceLight();
		}
		else
		{
			$this->forceDark();
		}
	}


	/**
	 * Apply the dark colour scheme
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function forceDark(): void
	{
		$command = sprintf(
			'%s --platform offscreen %s', escapeshellcmd('/usr/bin/plasma-apply-colorscheme'),
			escapeshellarg($this->config->darkScheme)
		);

		exec($command);

		//exec('gsettings set org.gnome.desktop.interface color-scheme \'prefer-dark\'');
	}

	/**
	 * Apply the light colour scheme
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function forceLight(): void
	{
		$command = sprintf(
			'%s --platform offscreen %s', escapeshellcmd('/usr/bin/plasma-apply-colorscheme'),
			escapeshellarg($this->config->lightScheme)
		);

		exec($command);

		exec('gsettings set org.gnome.desktop.interface color-scheme \'prefer-light\'');
	}

	/**
	 * Returns the start and end times of the daytime, and whether it is currently daylight or not
	 *
	 * @return  object  An object with the following properties:
	 *                  - start: \DateTime object representing the start time of the daytime
	 *                  - end: \DateTime object representing the end time of the daytime
	 *                  - isDaylight: boolean indicating whether it is currently daylight or not
	 * @since   2.0.0
	 */
	#[ObjectShape([
		'start'      => DateTime::class,
		'end'        => DateTime::class,
		'isDaylight' => 'bool',
	])]
	public function getDaytimeLimits(): object
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

		if ($this->config->lightOffset)
		{
			$startTime += 60 * $this->config->lightOffset;
		}

		if ($this->config->darkOffset)
		{
			$endTime += 60 * $this->config->darkOffset;
		}

		$isDaylight = $currentTime >= $startTime && $currentTime <= $endTime;

		return (object) [
			'start'      => new DateTime('@' . $startTime),
			'end'        => new DateTime('@' . $endTime),
			'isDaylight' => $isDaylight,
		];
	}

	/**
	 * Returns the name of the current timezone based on the system's date settings
	 *
	 * @return  string
	 *
	 * @since   2.0.0
	 */
	public function getTimezone(): string
	{
		$cmd = escapeshellcmd('date +"%Z"');
		exec($cmd, $output, $code);

		if (empty($output) || !count($output) || $code !== 0)
		{
			return 'GMT';
		}

		$tzName = trim(reset($output));

		if (empty($tzName))
		{
			return 'GMT';
		}

		try
		{
			new \DateTimeZone($tzName);

			return $tzName;
		}
		catch (\Exception $e)
		{
			return 'GMT';
		}
	}

	/**
	 * Update the colour scheme auto-switch CRON job
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function updateCRON(): void
	{
		$dayTimeLimits = $this->getDaytimeLimits();
		$sunrise       = $dayTimeLimits->start;
		$sunset        = $dayTimeLimits->end;

		$commandProto = PHP_BINARY . ' ' . TOGGLE_DARK_SELF . ' %s';

		$cronJobSunrise = (new CrontabJob())->setMinutes($sunrise->format('i'))
			->setHours($sunrise->format('H'))
			->setDayOfMonth($sunrise->format('d'))
			->setMonths($sunrise->format('n'))
			->setDayOfWeek('*')
			->setTaskCommandLine(sprintf($commandProto, 'light'))
			->setComments('Toggle Dark -- Sunrise');

		$cronJobSunset = (new CrontabJob())->setMinutes($sunset->format('i'))
			->setHours($sunset->format('H'))
			->setDayOfMonth($sunset->format('d'))
			->setMonths($sunset->format('n'))
			->setDayOfWeek('*')
			->setTaskCommandLine(sprintf($commandProto, 'dark'))
			->setComments('Toggle Dark -- Sunset');

		$cronJobCronUpdate = (new CrontabJob())->setMinutes('0')
			->setHours('*/4')
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
	 * Uninstall the colour scheme auto-switch CRON job
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
	 * Returns the name of the currently active Plasma colour scheme
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function getCurrentScheme(): string
	{
		$cmd = escapeshellcmd('LC_ALL=C plasma-apply-colorscheme --platform offscreen -l') . '|' . escapeshellcmd(
				'grep current'
			);
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
		[$scheme,] = @explode(' (current', $output, 2);

		return $scheme ?: '';
	}

	/**
	 * Returns the identifier of the best applicable colour scheme (light or dark), based on sunrise/sunset info.
	 *
	 * @return  string
	 */
	private function getBestScheme(): string
	{
		$limits = $this->getDaytimeLimits();

		return $limits->isDaylight ? $this->config->lightScheme : $this->config->darkScheme;
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