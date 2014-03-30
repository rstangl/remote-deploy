<?php

/**
 * RemoteClient interface.
 * @author	richard
 * @package	remote
 */
interface RemoteClient
{
	/**
	 * Gets a file from the server.
	 * If $localFile is not given a unique name is generated.
	 * @param	string	$remoteFile		Remote filename
	 * @param	string	$localFile		Local filename (optional)
	 * @return	string	Local filename
	 * @exception	RemoteClientException
	 */
	public function get($remoteFile, $localFile = '');
	
	/**
	 * Uploads a file to the server.
	 * @param	string	$localFile		Local filename
	 * @param	string	$remoteFile		Remote filename
	 * @exception	RemoteClientException
	 */
	public function put($localFile, $remoteFile);
	
	/**
	 * Removes a file from the server.
	 * @param	string	$remoteFile	Remote file to remove
	 * @exception	RemoteClientException
	 */
	public function remove($remoteFile);
	
	/**
	 * Creates a directory on the server.
	 * @param	string	$remoteDir	Remote dirname
	 * @exception	RemoteClientException
	 */
	public function mkdir($remoteDir);
	
	/**
	 * Removes a directory from the server.
	 * @param	string	$remoteDir	Remote dir to remove
	 * @exception	RemoteClientException
	 */
	public function rmdir($remoteDir);
}

?>
