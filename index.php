<?php
require_once( "common.php" );

require_once( RootPath."/classes/database/Account.class.php" );
require_once( RootPath."/classes/database/Transaction.class.php" );

use database\Account;
use database\Transaction;

$accounts = Account::get();
$oneMonthAgo = date( 'Y-m-d', mktime( 0, 0, 0, date('n') - 1, date('j'), date('Y') ) );

foreach( $accounts as $account )
{
	$accountBlock = new Block( "account" );
	$transactions = Transaction::get( array( "account_id" => $account->account_id, "transaction_date" => array( ">=", $oneMonthAgo ) ), "transaction_id DESC" );

	$accountBlock->addVariables( array(
		"id" => $account->account_id,
		"name" => htmlentities( $account->account_name ),
		"amount" => number_format( $account->amount, 2, ",", "&nbsp;" )."&nbsp;&euro;",
		"transctions_count" => count( $transactions )
	) );

	// Show the last 5 transactions
	$odd = true;
	for( $i = 0 ; $i < count( $transactions ) && $i < 5 ; $i++ )
	{
		$accountBlock->addBlock( new Block( "transaction", array(
			"label" => htmlentities( !is_null( $transactions[$i]->short_label ) ? $transactions[$i]->short_label : $transactions[$i]->label ),
			"date" => !is_null( $transactions[$i]->real_date ) ? $transactions[$i]->real_date : $transactions[$i]->transaction_date,
			"amount" => number_format( $transactions[$i]->amount, 2, ",", "&nbsp;" )."&nbsp;&euro;",
			"type" => $transactions[$i]->type,
			"odd" => $odd ? 1 : 0
		) ) );

		$odd = !$odd;
	}

	// Create graph data for the past month transactions
	$amounts = array();
	$amounts[date("Y-m-d")] = floatval( $account->amount );
	$tempAmount = floatval( $account->amount );

	foreach( $transactions as $transaction )
	{
		if( !array_key_exists( $transaction->transaction_date, $amounts ) )
			$amounts[$transaction->transaction_date] = $tempAmount;
		
		$tempAmount -= floatval( $transaction->amount );
	}

	$dates = array_keys( $amounts );
	sort( $dates );
	$amounts[date( "Y-m-d", strtotime( $dates[0] ) - 86400 )] = $tempAmount;

	ksort( $amounts );

	$i = 0;
	foreach( $amounts as $date => $amount )
	{
		$accountBlock->addBlock( new Block( "graphData", array(
			"date" => $date,
			"amount" => $amount,
			"notLast" => $i != count( $amounts ) - 1
		) ) );
		$i++;
	}
	
	$template->addBlock( $accountBlock );
}

$template->show( "accounts.html" );
?>
