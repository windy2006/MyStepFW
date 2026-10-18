<?php
/**
 * Abstract definition of a CSS token class.
 * 
 * Every token has to extend this class.
 *
 * @package		CssMin/Tokens
 * @link		https://github.com/PhrozenByte/cssmin
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class aCssToken
	{
	/**
	 * Returns the token as string.
	 * 
	 * @return string
	 */
	abstract public function __toString();
	}
?>