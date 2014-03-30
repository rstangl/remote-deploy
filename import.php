<?php

/**
 * Import function to load classes from packages (Java like).
 * @param	string	$className	Full-qualified class name
 * @exception	Exception
 */
function import($className)
{
	$fileName = dirname(__FILE__) . '/classes/' . str_replace('.', '/', $className) . '.php';
	require_once ($fileName);
}
 
?>
