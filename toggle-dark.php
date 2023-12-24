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
			$output->writeln('Toggled Plasma colour scheme.');
		}

		$output->writeln('No need to toggle the Plasma colour scheme.');
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