<?php

namespace database;

require_once( dirname( __FILE__ )."/Object.class.php" );

final class ThirdParty extends Object
{
	protected static $schema = "money";
	protected static $table = "third_party";
	
	protected static $fields = array(
		"third_party_id" => array(
			"type" 			=> "integer",
			"bits"			=> 24,
			"unsigned" 		=> true,
			"autoIncrement"	=> true
		),
		"third_party_name" => array(
			"type"		=> "string",
			"maxlength"	=> 64
		),
		"expression" => array(
			"type"		=> "string",
			"maxlength"	=> 128
		)
	);
	
	protected static $keys = array(
		"primary" => array( "third_party_id" ),
		"foreign" => array()
	);

	public $third_party_id;
	public $third_party_name;
	public $expression;
}

?>
