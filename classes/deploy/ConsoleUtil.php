<?php

/**
 * Utility class for accessing and controlling the terminal.
 * @author	richard
 * @package	deploy
 */
class ConsoleUtil
{
	/**
	 * Prints a line of text to stdout.
	 * @param	string	$str	text to print
	 */
	public static function println($str = '')
	{
		fprintf(STDOUT, "%s\n", $str);
	}
	
	/**
	 * Prints a line of text to stderr.
	 * @param	string	$err	text to print
	 */
	public static function errorln($err = '')
	{
		fprintf(STDERR, "%s\n", $err);
	}
	
	/**
	 * Prints some text with a fixed width of console width - $rspace to stdout.
	 * No line feed will be appended.
	 * @param	string	$str		text to print
	 * @param	int		$rspace		space to be left in the line (optional, default = 8)
	 */
	public static function printWide($str, $rspace = 8)
	{
		$width = 80 - $rspace;
		fprintf(STDOUT, "%-{$width}s", $str);
	}
	
	/**
	 * Prints the string "done" plus a line feed to stdout.
	 */
	public static function printDone()
	{
		fprintf(STDOUT, "done\n");
	}
	
	/**
	 * Prints the string "failed" plus a line feed to stdout.
	 */
	public static function printFailed()
	{
		fprintf(STDOUT, "failed\n");
	}
}

?>
