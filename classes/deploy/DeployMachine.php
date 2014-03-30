<?php

import('remote.RemoteClient');
import('remote.RemoteClientException');
import('deploy.IOException');
import('deploy.ConsoleUtil');

/**
 * Remote deploy machine.
 * @author	richard
 * @package	deploy
 */
class DeployMachine
{
	public function __construct(RemoteClient $remoteClient, $checkSumFile, $localPath, $remotePath,
								$excludeFile = '')
	{
		$this->remoteClient_ = $remoteClient;
		$this->checkSumFile_ = $checkSumFile;
		$this->localPath_    = $localPath;
		$this->remotePath_   = $remotePath;
		
		if (trim($excludeFile) != '') {
			if (! file_exists($excludeFile) || ! is_readable($excludeFile)) {
				throw new IOException(sprintf("Could not read file '%s'", $excludeFile));
			}
			$this->excludedFiles_ = file($excludeFile,
										 FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		}
	}
	
	public function deploy()
	{
		$checksums = array();
		
		try {
			ConsoleUtil::println(
					sprintf("Downloading checksum file '%s'...", $this->checkSumFile_)
			);
			
			$localCheckSumFile = $this->getCheckSumFile_();
			$lines = file($localCheckSumFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			
			// now we have lines like "s54fsea5f4esffsfefs45f454 path/to/file"
			// let's parse them into an associative array in form of 'path/to/file' => checksum
			foreach ($lines as $line) {
				$matches = array();
				if (preg_match('/^(.{32})\s+(.+)$/', $line, $matches)) {
					$checksums[$matches[2]] = $matches[1];
				}
			}
		} catch (RemoteClientException $fe) {
			// if getting the checksum file from the server failed we assume that there
			// isn't any yet, so we will have to upload the whole project
			ConsoleUtil::errorln(
					"No checksum file yet on deploy target - will upload whole project"
			);
		}
		
		// get a flat list of all files in the local project
		$localFiles = $this->getLocalFileList_();
		
		/* walk through all files and directories in the list and check if there is a
		 * checksum for it.
		 * 
		 *  ->  if there is a checksum the file/dir is not new.
		 *      if it's a dir -> do nothing
		 *      if it's a file -> build checksum of local file and compare with remote checksum
		 *                        if checksums differ upload the file
		 * 
		 *  ->  if there is no checksum the file/dir is new.
		 *      if it's a dir -> do a mkdir on the server
		 *      if it's a file -> build checksum and upload file
		 * 
		 * if there is a remote checksum for a file/dir which does not exist locally it has
		 * been deleted -> so also remove it from the deploy target.
		 */
		
		$files2update = array();
		$dirs2create = array();
		$newCheckSums = array();	// filename => checksum
		
		foreach ($localFiles as $node) {
			$newsum = '00000000000000000000000000000000';
			try {
				if (is_file($this->localPath_ .'/'. $node)) {
					$newsum = $this->generateCheckSum_($this->localPath_ .'/'. $node);
				}
			} catch (IOException $ioe) {
				// if we can't read the file we skip it
				ConsoleUtil::errorln(
						sprintf("Cannot read file '%s/%s' - skipping", $this->localPath_, $node)
				);
			}
			
			if (isset($checksums[$node])) {
				// node is not new
				if (is_file($this->localPath_ .'/'. $node)) {
					// do we need to update it?
					if ($newsum != $checksums[$node]) {
						$files2update[] = $node;
					}
				}
				unset($checksums[$node]);
			} else {
				// node is new
				if (is_dir($this->localPath_ .'/'. $node)) {
					$dirs2create[] = $node;
				} else if (is_file($this->localPath_ .'/'. $node)) {
					$files2update[] = $node;
				}
			}
			
			$newCheckSums[$node] = $newsum;
		}
		
		$files2rem = array();
		$dirs2rem = array();
		
		// the nodes which are still in the $checksums array have been deleted locally
		foreach ($checksums as $node => $checksum) {
			if ($checksum == '00000000000000000000000000000000') {
				$dirs2rem[] = $node;
			} else {
				$files2rem[] = $node;
			}
		}
		
		ConsoleUtil::println();
		$remoteTargetModified = false;
		$msg = "";
		
		// create dirs
		foreach ($dirs2create as $dir) {
			ConsoleUtil::printWide(sprintf("Creating remote directory '%s'... ", $dir));
			try {
				$this->remoteClient_->mkdir($this->remotePath_ .'/'. $dir);
				ConsoleUtil::printDone();
				$remoteTargetModified = true;
			} catch (RemoteClientException $e) {
				ConsoleUtil::printFailed();
				ConsoleUtil::errorln(sprintf("ERROR: %s", $e->getMessage()));
			}
		}
		
		// upload files
		foreach ($files2update as $file) {
			ConsoleUtil::printWide(sprintf("Uploading file '%s'... ", $file));
			try {
				$this->remoteClient_->put($this->localPath_ .'/'. $file,
										  $this->remotePath_ .'/'. $file);
				ConsoleUtil::printDone();
				$remoteTargetModified = true;
			} catch (RemoteClientException $e) {
				ConsoleUtil::printFailed();
				ConsoleUtil::errorln(sprintf("ERROR: %s", $e->getMessage()));
			}
		}
		
		// remove files
		foreach ($files2rem as $file) {
			ConsoleUtil::printWide(sprintf("Removing remote file '%s'... ", $file));
			try {
				$this->remoteClient_->remove($this->remotePath_ .'/'. $file);
				ConsoleUtil::printDone();
				$remoteTargetModified = true;
			} catch (RemoteClientException $e) {
				ConsoleUtil::printFailed();
				ConsoleUtil::errorln(sprintf("ERROR: %s", $e->getMessage()));
			}
		}
		
		// remove dirs in reversed order because subdirs need to be removed first
		for ($i = count($dirs2rem)-1; $i >= 0; $i--) {
			if (! isset($dirs2rem[$i])) {
				continue;
			}
			
			ConsoleUtil::printWide(sprintf("Removing remote directory '%s'... ", $dirs2rem[$i]));
			try {
				$this->remoteClient_->rmdir($this->remotePath_ .'/'. $dirs2rem[$i]);
				ConsoleUtil::printDone();
				$remoteTargetModified = true;
			} catch (RemoteClientException $e) {
				ConsoleUtil::printFailed();
				ConsoleUtil::errorln(sprintf("ERROR: %s", $e->getMessage()));
			}
		}
		
		if ($remoteTargetModified == false) {
			ConsoleUtil::errorln("Deploy target has not been modified - nothing done");
			ConsoleUtil::errorln();
			return;
		}
		
		ConsoleUtil::println();
		
		// generate new checksum file from $newCheckSums array and upload it
		$newCheckSumFile = $this->generateCheckSumFile_($newCheckSums);
		ConsoleUtil::printWide(
				sprintf("Uploading new checksum file to '%s'... ", $this->checkSumFile_)
		);
		try {
			$this->remoteClient_->put($newCheckSumFile, $this->checkSumFile_);
			ConsoleUtil::printDone();
			ConsoleUtil::println();
		} catch (RemoteClientException $e) {
			ConsoleUtil::printFailed();
			ConsoleUtil::errorln(sprintf("ERROR: %s", $e->getMessage()));
		}
	}
	
	protected function getCheckSumFile_()
	{
		try {
			return $this->remoteClient_->get($this->checkSumFile_);
		} catch (RemoteClientException $fe) {
			ConsoleUtil::errorln(
					sprintf("Checksum file '%s' could not be downloaded", $this->checkSumFile_)
			);
			throw $fe;
		}
	}
	
	protected function getLocalFileList_()
	{
		$files = array();
		
		try {
			$dirIter = new RecursiveDirectoryIterator(
					$this->localPath_,
					RecursiveDirectoryIterator::KEY_AS_PATHNAME
						| RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
						| RecursiveDirectoryIterator::SKIP_DOTS
			);
			$iterIter = new RecursiveIteratorIterator(
					$dirIter,
					RecursiveIteratorIterator::CHILD_FIRST
			);
			
			$remove = rtrim($this->localPath_, '/') . '/';
			
			foreach ($iterIter as $file => $info) {
				$file = substr_replace($file, '', 0, strlen($remove));
				
				$excluded = false;
				foreach ($this->excludedFiles_ as $exf) {
					if (false != strstr($file, $exf)) {
						$excluded = true;
					}
				}
				
				if ($excluded == false) {
					$files[] = $file;
				}
			}
		} catch (UnexpectedValueException $ve) {
			ConsoleUtil::errorln(sprintf("Local path '%s' cannot be opened", $this->localPath_));
			throw new IOException($ve->getMessage());
		}
		
		// Sort the list because directories must be created before files can be inserted
		natcasesort($files);
		
		return $files;
	}
	
	protected function generateCheckSum_($file)
	{
		$fp = fopen($file, 'r');
		if ($fp == false) {
			throw new IOException();
		}
		
		$size = filesize($file);
		if ($size <= 0) {
			$size = 1;
		}
		
		$buf = fread($fp, $size);
		fclose($fp);
		
		return md5($buf);
	}
	
	protected function generateCheckSumFile_(array $checksums)
	{
		$file = tempnam('/tmp', 'chksum');
		$fp = fopen($file, 'w');
		if ($fp == false) {
			throw new IOException();
		}
		
		foreach ($checksums as $node => $checksum) {
			fprintf($fp, "%s %s\n", $checksum, $node);
		}
		
		fclose($fp);
		return $file;
	}
	
	/** RemoteClient */
	protected $remoteClient_	= null;
	
	/** Remote path to checksum file (absolute - not relative to $remotePath_) */
	protected $checkSumFile_	= '';
	
	/** Local project deploy basepath */
	protected $localPath_		= '';
	
	/** Remote deploy path */
	protected $remotePath_		= '';
	
	/** Files not to be deployed (relative to $localPath_) */
	protected $excludedFiles_	= array();
}

?>
