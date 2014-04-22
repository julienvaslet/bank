<?php
require_once( "common.php" );

require_once( RootPath."/classes/database/Account.class.php" );
require_once( RootPath."/classes/database/Transaction.class.php" );

use database\Account;
use database\Transaction;

$accounts = Account::get();
$twoMonthesAgo = date( 'Y-m-d', mktime( 0, 0, 0, date('n') - 1, 1, date('Y') ) );

foreach( $accounts as $account )
{
	$accountBlock = new Block( "account" );
	$transactions = Transaction::get( array( "account_id" => $account->account_id, "transaction_date" => array( ">=", $twoMonthesAgo ) ), "transaction_id DESC" );

	if( count( $transactions ) < 5 )
		$lastTransactions = Transaction::get( array( "account_id" => $account->account_id ), "transaction_id DESC", 1, 5 );
	else
		$lastTransactions = $transactions;

	$accountBlock->addVariables( array(
		"id" => $account->account_id,
		"name" => htmlentities( $account->account_name ),
		"amount" => number_format( $account->amount, 2, ",", "&nbsp;" ),
		"transactions_count" => count( $lastTransactions )
	) );

	// Show the last 5 transactions
	$odd = true;
	for( $i = 0 ; $i < count( $lastTransactions ) && $i < 5 ; $i++ )
	{
		$tDate = "";
		$tTime = strtotime( !is_null( $lastTransactions[$i]->real_date ) ? $lastTransactions[$i]->real_date : $lastTransactions[$i]->transaction_date );
		$tDate = date( "d", $tTime )." ".$language["short_monthes"][date( "n", $tTime )];

		$accountBlock->addBlock( new Block( "transaction", array(
			"label" => htmlentities( !is_null( $lastTransactions[$i]->short_label ) ? $lastTransactions[$i]->short_label : $lastTransactions[$i]->label ),
			"date" => $tDate,
			"amount" => number_format( $lastTransactions[$i]->amount, 2, ",", "&nbsp;" ),
			"type" => $lastTransactions[$i]->type,
			"value" => floatval( $lastTransactions[$i]->amount ) >= 0 ? "positive" : "negative",
			"odd" => $odd ? 1 : 0
		) ) );

		$odd = !$odd;
	}

	// Create graph data for the past month transactions
	$amounts = array();
	$currentMonth = date( "Y-m" );
	$amounts[$currentMonth] = array();
	$amounts[$currentMonth][date( "j" )] = floatval( $account->amount );
	$tempAmount = floatval( $account->amount );

	$time = 0;

	foreach( $transactions as $transaction )
	{
		$time = strtotime( $transaction->transaction_date );

		if( !array_key_exists( date( "Y-m", $time ), $amounts ) )
		{
			$currentMonth = date( "Y-m", $time );
			$amounts[$currentMonth] = array();
		}

		if( !array_key_exists( date( "j", $time ), $amounts[$currentMonth] ) )
			$amounts[$currentMonth][date( "j", $time )] = $tempAmount;
		
		$tempAmount -= floatval( $transaction->amount );
	}

	$amounts[date( "Y-m", $time - 86400 )][date( "j", $time - 86400 )] = $tempAmount;

	$i = 0;
	foreach( $amounts as $month => $days )
	{
		ksort( $days );

		if( !array_key_exists( "1", $days ) )
		{
			$days["1"] = $days[array_keys($days)[0]];
			ksort( $days );
		}

		if( $month != date( "Y-m" ) )
		{
			//$mTime = strtotime( $month."-01" );
			$lastDay = "31";
			if( !array_key_exists( $lastDay, $days ) )
			{
				$days[$lastDay] = $days[array_keys($days)[count($days)-1]];
				ksort( $days );
			}
		}

		$monthBlock = new Block( "graphMonth", array(
			"month" => $month,
			"notLast" => $i != count( $amounts ) - 1
		) );

		$j = 0;
		foreach( $days as $day => $amount )
		{
			$monthBlock->addBlock( new Block( "graphDay", array(
				"day" => $day,
				"amount" => $amount,
				"notLast" => $j != count( $days ) - 1
			) ) );
			$j++;
		}

		$accountBlock->addBlock( $monthBlock );
		$i++;
	}
	
	$template->addBlock( $accountBlock );
}

$template->show( "accounts.html" );
?>
