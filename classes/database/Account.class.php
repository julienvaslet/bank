<?php

namespace database;

require_once( dirname( __FILE__ )."/Object.class.php" );

final class Account extends Object
{
	protected static $schema = "bank";
	protected static $table = "account";
	
	protected static $fields = array(
		"account_id" => array(
			"type" 			=> "integer",
			"bits"			=> 24,
			"unsigned" 		=> true,
			"autoIncrement"	=> true
		),
		"account_name" => array(
			"type"		=> "string",
			"maxlength"	=> 64
		),
		"amount" => array(
			"type"				=> "decimal",
			"integerPart"		=> 5,
			"fractionalPart"	=> 2,
			"unsigned"			=> true,
			"null"				=> false,
			"default"			=> 0.0
		)
	);
	
	protected static $keys = array(
		"primary" => array( "account_id" ),
		"foreign" => array()
	);

	public $account_id;
	public $account_name;
	public $amount;
}

?>

