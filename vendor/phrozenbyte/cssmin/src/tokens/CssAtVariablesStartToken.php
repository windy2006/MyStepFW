<?php
/**
 * This {@link aCssToken CSS token} represents the start of a @variables at-rule block.
 *
 * @package		CssMin/Tokens
 * @link		https://github.com/PhrozenByte/cssmin
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
class CssAtVariablesStartToken extends aCssAtBlockStartToken
	{
	/**
	 * Media types of the @variables at-rule block.
	 * 
	 * @var array
	 */
	public $MediaTypes = array();
	/**
	 * Set the properties of a @variables at-rule token.
	 * 
	 * @param array $mediaTypes Media types
	 * @return void
	 */
	public function __construct($mediaTypes = null)
		{
		$this->MediaTypes = $mediaTypes ? $mediaTypes : array("all");
		}
	/**
	 * Implements {@link aCssToken::__toString()}.
	 * 
	 * @return string
	 */
	public function __toString()
		{
		return "";
		}
	}
?>