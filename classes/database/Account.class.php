<?php

namespace database;

require_once( dirname( __FILE__ )."/Object.class.php" );

final class Account extends Object
{
	protected static $schema = "money";
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
		"account_short_name" => array(
			"type"		=> "string",
			"maxlength"	=> 32
		),
		"amount" => array(
			"type"				=> "decimal",
			"integerPart"		=> 9,
			"fractionalPart"	=> 2,
			"unsigned"			=> false,
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
	public $account_short_name;
	public $amount;
	
	public function getAccountName()
	{
		return !empty( $this->account_short_name ) ? $this->account_short_name : $this->account_name;
	}
	
	public function getAccountAmount( $date = null )
	{
		$amount = floatval( $this->amount );
		
		if( !is_null( $date ) && preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date ) == 1 )
		{
			$db = Database::getInstance();
			$selectors = array( "account_id" => $this->account_id, "transaction_date" => array( ">=", $date ) );
			
			$query = "SELECT SUM(`amount`) AS `sum` FROM `".static::$schema."`.`transaction` WHERE ".static::getSqlSelectors( $selectors );
			
			$result = $db->query( $query );

			if( $result )
			{
				$row = $result->fetch_assoc();
				$amount -= floatval( $row['sum'] );
			}
		}
		
		return $amount;
	}
}

?>
