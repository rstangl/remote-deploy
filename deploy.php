#!/usr/bin/env php
<?php

/**
 * Remote-Deploy main script.
 * @author	richard
 */

require_once ('import.php');

import('deploy.DeployMachine');
import('deploy.IOException');
import('deploy.ConsoleUtil');
import('remote.ftpclient.FTPClient');
import('remote.RemoteClientException');

// default values
$localPath = '.';
$remotePath = '.';
$checksumFile = 'DEPLOY_CHECKSUMS';
$excludeFile = '';

// ftp options
$ftpServer = '';
$ftpUser = '';
$ftpPass = '';

// get the command line options
$options = getopt("l:r:c:e:h:u:p:");

if (isset($options['h'])) {
	if (is_string($options['h'])) {
		$ftpServer = $options['h'];
	} else {
		ConsoleUtil::errorln("Multiple usage of -h option not allowed!");
		printUsage();
		die();
	}
} else {
	ConsoleUtil::errorln("Option -h missing!");
	printUsage();
	die();
}

if (isset($options['u'])) {
	if (is_string($options['u'])) {
		$ftpUser = $options['u'];
	} else {
		ConsoleUtil::errorln("Multiple usage of -u option not allowed!");
		printUsage();
		die();
	}
} else {
	ConsoleUtil::errorln("Option -u missing!");
	printUsage();
	die();
}

if (isset($options['p'])) {
	if (is_string($options['p'])) {
		$ftpPass = $options['p'];
	} else {
		ConsoleUtil::errorln("Multiple usage of -p option not allowed!");
		printUsage();
		die();
	}
}

if (isset($options['l'])) {
	if (is_string($options['l'])) {
		$localPath = $options['l'];
	} else {
		ConsoleUtil::errorln("Multiple usage of -l option not allowed!");
		printUsage();
		die();
	}
}

if (isset($options['r'])) {
	if (is_string($options['r'])) {
		$remotePath = $options['r'];
	} else {
		ConsoleUtil::errorln("Multiple usage of -r option not allowed!");
		printUsage();
		die();
	}
}

if (isset($options['c'])) {
	if (is_string($options['c'])) {
		$checksumFile = $options['c'];
	} else {
		ConsoleUtil::errorln("Multiple usage of -c option not allowed!");
		printUsage();
		die();
	}
}

if (isset($options['e'])) {
	if (is_string($options['e'])) {
		$excludeFile = $options['e'];
	} else {
		ConsoleUtil::errorln("Multiple usage of -e option not allowed!");
		printUsage();
		die();
	}
}

// if no FTP password given with -p option prompt for it
if ($ftpPass == '') {
	fprintf(STDOUT, "Password: ");
	$ftpPass = rtrim(fgets(STDIN), "\n");
}

// instantiate remote client
try {
	$ftp = new FTPClient($ftpServer, $ftpUser, $ftpPass);
} catch (RemoteClientException $e) {
	ConsoleUtil::errorln(sprintf("ERROR: %s", $e->getMessage()));
	die();
}

try {
	$deploy = new DeployMachine($ftp, $checksumFile, $localPath, $remotePath, $excludeFile);
	$deploy->deploy();
} catch (IOException $ioe) {
	ConsoleUtil::errorln(sprintf("ERROR: %s", $ioe->getMessage()));
	die();
}


function printUsage()
{
	$self = basename($_SERVER['argv'][0]);
	ConsoleUtil::println("Usage: {$self} -h ftp-host -u ftp-user [-p ftp-password]");
	ConsoleUtil::println("                   [-l local-path] [-r remote-path] [-c remote-checksum-file]");
	ConsoleUtil::println("                   [-e exclude-pattern-file]");
}

?>
