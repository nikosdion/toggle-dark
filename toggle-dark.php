#!/usr/bin/env /usr/bin/php
<?php

use Dionysopoulos\ToggleDark\ToggleDark;
use Silly\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

require_once __DIR__ . "/vendor/autoload.php";

$app = new Application();

$app->command(
	'autotoggle',
	function (OutputInterface $output, InputInterface $input) {
		$toggleDark = new ToggleDark();
		$ioStyle    = new Symfony\Component\Console\Style\SymfonyStyle($input, $output);
		$limits     = $toggleDark->getDaytimeLimits();
		$tz         = new DateTimeZone($toggleDark->getTimezone());

		$ioStyle->info(
			sprintf(
				'Daylight today is from %s to %s.',
				$limits->start->setTimezone($tz)->format('H:i:s T'),
				$limits->end->setTimezone($tz)->format('H:i:s T')
			)
		);

		$toggled = $toggleDark->autoToggleTheme();

		if ($toggled)
		{
			$ioStyle->success('Toggled Plasma colour scheme.');

			return;
		}

		$ioStyle->warning('No need to toggle the Plasma colour scheme.');
	}
)->descriptions('Toggle dark/light global theme based on sunrise/sunset');

$app->command(
	'dark',
	function (OutputInterface $output) {
		(new ToggleDark())->forceDark();
	}
)->descriptions('Applies the dark theme');

$app->command(
	'light',
	function (OutputInterface $output) {
		(new ToggleDark())->forceLight();
	}
)->descriptions('Applies the light theme');

call_user_func(
	function () {
		$self = __FILE__;

		if (str_starts_with($self, 'phar://'))
		{
			$self = substr($self, 7);
			$self = dirname($self);
		}

		define('TOGGLE_DARK_SELF', $self);
	}
);

$app->setDefaultCommand('autotoggle');
$app->run();