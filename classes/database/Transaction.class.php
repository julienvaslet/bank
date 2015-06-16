<?php

namespace database;

require_once( dirname( __FILE__ )."/Object.class.php" );

final class Transaction extends Object
{
	protected static $schema = "money";
	protected static $table = "transaction";
	
	protected static $fields = array(
		"transaction_id" => array(
			"type" 			=> "integer",
			"bits"			=> 24,
			"unsigned" 		=> true,
			"autoIncrement"	=> true
		),
		"account_id" => array(
			"type" 			=> "integer",
			"bits"			=> 24,
			"unsigned" 		=> true
		),
		"transaction_date" => array(
			"type"		=> "date"
		),
		"value_date" => array(
			"type"		=> "date"
		),
		"label" => array(
			"type"		=> "string",
			"maxlength"	=> 128
		),
		"amount" => array(
			"type"				=> "decimal",
			"integerPart"		=> 9,
			"fractionalPart"	=> 2,
			"unsigned"			=> false,
			"null"				=> false,
			"default"			=> 0.0
		),
		"real_date" => array(
			"type"		=> "date",
			"null"		=> true
		),
		"type" => array(
			"type"		=> "string",
			"maxlength"	=> 24,
			"null"		=> true
		),
		"third_party" => array(
			"type"		=> "string",
			"maxlength"	=> 64,
			"null"		=> true
		),
		"short_label" => array(
			"type"		=> "string",
			"maxlength"	=> 128,
			"null"		=> true
		),
	);
	
	protected static $keys = array(
		"primary" => array( "transaction_id" ),
		"foreign" => array(
			array(
				"fields" => "account_id",
				"table" => "account",
				"references" => "account_id",
				"onDelete" => "cascade",
				"onUpdate" => "cascade"
			),
		)
	);

	public $transaction_id;
	public $account_id;
	public $transaction_date;
	public $value_date;
	public $label;
	public $amount;
	public $real_date;
	public $type;
	public $third_party;
	public $short_label;

	public static function getLastTransactionsAmount( $transaction_type, $days )
	{
		$amount = 0;

		$date = date( "Y-m-d", mktime( 0, 0, 0, date("n"), date("j") - intval( $days ), date("Y") ) );
		$db = Database::getInstance();
		$selectors = array( "type" => $transaction_type, "transaction_date" => array( ">=", $date ) );
			
		$query = "SELECT SUM(`amount`) AS `sum` FROM `".static::$schema."`.`".static::$table."` WHERE ".static::getSqlSelectors( $selectors );
			
		$result = $db->query( $query );

		if( $result )
		{
			$row = $result->fetch_assoc();
			$amount = -1 * floatval( $row['sum'] );
		}

		return $amount;
	}
}

?>
