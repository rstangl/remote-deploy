<?php

import('remote.ftpclient.FTPException');
import('remote.RemoteClient');

/**
 * Manages an FTP session.
 * @author	richard
 * @package	remote.ftpclient
 */
class FTPClient implements RemoteClient
{
	/**
	 * Connects and logs in to the FTP server.
	 * @param	string	$server		The FTP server to connect to
	 * @param	string	$user		The username
	 * @param	string	$pass		The user's password
	 * @param	int		$port		The TCP port (optional)
	 * @exception	FTPException
	 */
	public function __construct($server, $user, $pass, $port = 21)
	{
		$this->server_ = $server;
		$this->user_   = $user;
		$this->pass_   = $pass;
		$this->port_   = $port;
		
		$this->connect_();
		$this->login_();
	}
	
	/**
	 * Closes the connection.
	 */
	public function __destruct()
	{
		ftp_close($this->conId_);
	}
	
	/**
	 * Gets a file from the server.
	 * If $localFile is not given a unique name is generated.
	 * @param	string	$remoteFile		Remote filename
	 * @param	string	$localFile		Local filename (optional)
	 * @return	string	Local filename
	 * @exception	FTPException
	 */
	public function get($remoteFile, $localFile = '')
	{
		if (trim($localFile) == '') {
			$localFile = tempnam('/tmp', 'ftpfile');
		}
		
		$ret = ftp_get($this->conId_, $localFile, $remoteFile, FTP_BINARY);
		if ($ret == false) {
			throw new FTPException(sprintf("Could not download file '%s'", $remoteFile));
		}
		
		return $localFile;
	}
	
	/**
	 * Uploads a file to the server.
	 * @param	string	$localFile		Local filename
	 * @param	string	$remoteFile		Remote filename
	 * @exception	FTPException
	 */
	public function put($localFile, $remoteFile)
	{
		$ret = ftp_put($this->conId_, $remoteFile, $localFile, FTP_BINARY);
		if ($ret == false) {
			throw new FTPException(sprintf("Could not upload file '%s'", $localFile));
		}
	}
	
	/**
	 * Removes a file from the server.
	 * @param	string	$remoteFile	Remote file to remove
	 * @exception	FTPException
	 */
	public function remove($remoteFile)
	{
		$ret = ftp_delete($this->conId_, $remoteFile);
		if ($ret == false) {
			throw new FTPException(sprintf("Could not remove file '%s'", $remoteFile));
		}
	}
	
	/**
	 * Creates a directory on the server.
	 * @param	string	$remoteDir	Remote dirname
	 * @exception	FTPException
	 */
	public function mkdir($remoteDir)
	{
		$ret = ftp_mkdir($this->conId_, $remoteDir);
		if ($ret == false) {
			throw new FTPException(sprintf("Could not create dir '%s'", $remoteDir));
		}
	}
	
	/**
	 * Removes a directory from the server.
	 * @param	string	$remoteDir	Remote dir to remove
	 * @exception	FTPException
	 */
	public function rmdir($remoteDir)
	{
		$ret = ftp_rmdir($this->conId_, $remoteDir);
		if ($ret == false) {
			throw new FTPException(sprintf("Could not remove dir '%s'", $remoteDir));
		}
	}
	
	/**
	 * Connect to the server.
	 * @param	int		$timeout	The connection timeout (optional)
	 * @exception	FTPException
	 */
	protected function connect_($timeout = 90)
	{
		$this->conId_ = ftp_connect($this->server_, $this->port_, $timeout);
		if ($this->conId_ == false) {
			throw new FTPException(
					sprintf("Could not connect to host '%s' port %d", $this->server_, $this->port_)
			);
		}
	}
	
	/**
	 * Logs in to the server.
	 * @exception	FTPException
	 */
	protected function login_()
	{
		if (! ftp_login($this->conId_, $this->user_, $this->pass_)) {
			throw new FTPException("Login failed");
		}
		if (! ftp_pasv($this->conId_, true)) {
			throw new FTPException("Unable to activate PASV mode");
		}
	}
	
	protected $conId_	= null;
	protected $server_	= '';
	protected $port_	= 0;
	protected $user_	= '';
	protected $pass_	= '';
}

?>
