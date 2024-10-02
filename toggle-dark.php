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

		$toggled = $toggleDark->autoToggleScheme();

		if ($toggled)
		{
			$ioStyle->success('Toggled Plasma colour scheme.');

			return;
		}

		$ioStyle->warning('No need to toggle the Plasma colour scheme.');
	}
)->descriptions('Toggle dark/light colour scheme based on sunrise/sunset');

$app->command(
	'toggle',
	function (OutputInterface $output) {
		(new ToggleDark())->toggleScheme();
	}
)->descriptions('Toggle between the dark and light colour scheme');

$app->command(
	'dark',
	function (OutputInterface $output) {
		(new ToggleDark())->forceDark();
	}
)->descriptions('Applies the dark colour scheme');

$app->command(
	'light',
	function (OutputInterface $output) {
		(new ToggleDark())->forceLight();
	}
)->descriptions('Applies the light colour scheme');

$app->command(
	'current',
	function (OutputInterface $output) {
		$current = (new ToggleDark())->getCurrentScheme();

		$output->writeln($current);
	}
)->descriptions('Print the current colour scheme identifier');

$app->command(
	'update',
	function (OutputInterface $output) {
		$toggleDark = new ToggleDark();
		$toggleDark->updateCRON();
		$toggleDark->autoToggleScheme();
		$output->writeln('Updated CRON jobs.');
	}
)->descriptions('Install or update the CRON jobs to auto-switch the colour scheme');

$app->command(
	'uninstall',
	function (OutputInterface $output) {
		(new ToggleDark())->uninstallCRON();
		$output->writeln('Removed CRON jobs.');
	}
)->descriptions('Remove the CRON jobs to auto-switch the colour scheme');


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