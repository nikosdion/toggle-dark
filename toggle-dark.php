#!/usr/bin/env /usr/bin/php
<?php

use Dionysopoulos\ToggleDark\ToggleDark;
use Silly\Application;
use Symfony\Component\Console\Output\OutputInterface;

require_once __DIR__ . "/vendor/autoload.php";

$app = new Application();

$app->command(
	'autotoggle',
	function (OutputInterface $output) {
		$toggled = (new ToggleDark())->autoToggleTheme();

		if ($toggled)
		{
			$output->writeln('Toggled Plasma theme.');
		}

		$output->writeln('No need to toggle the Plasma theme.');
	}
)->descriptions('Toggle dark/light global theme based on sunrise/sunset');

$app->command(
	'toggle',
	function (OutputInterface $output) {
		(new ToggleDark())->toggleTheme();
	}
)->descriptions('Toggle between the dark and light global theme');

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

$app->command(
	'current',
	function (OutputInterface $output) {
		$current = (new ToggleDark())->getCurrentTheme();

		$output->writeln($current);
	}
)->descriptions('Print the current global theme identifier');

$app->command(
	'update',
	function (OutputInterface $output) {
		$toggleDark = new ToggleDark();
		$toggleDark->updateCRON();
		$toggleDark->autoToggleTheme();
		$output->writeln('Updated CRON jobs.');
	}
)->descriptions('Install or update the CRON jobs to auto-switch the global theme');

$app->command(
	'uninstall',
	function (OutputInterface $output) {
		(new ToggleDark())->uninstallCRON();
		$output->writeln('Removed CRON jobs.');
	}
)->descriptions('Remove the CRON jobs to auto-switch the global theme');

call_user_func(function (){
	$self = __FILE__;

	if (str_starts_with($self, 'phar://'))
	{
		$self = substr($self, 7);
		$self = dirname($self);
	}

	define('TOGGLE_DARK_SELF', $self);
});

$app->setDefaultCommand('autotoggle');
$app->run();