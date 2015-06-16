<?php
require_once( "common.php" );

require_once( RootPath."/classes/database/Account.class.php" );
require_once( RootPath."/classes/database/Transaction.class.php" );

use database\Account;
use database\Transaction;

$accounts = Account::get( array( "account_id" => $_GET["account_id"] ) );

if( count( $accounts ) == 0 )
{
	header( "Location: /", 302 );
	exit;
}

if( !empty( $_GET["month"] ) && preg_match( '/^[0-9]{4}-[0-9]{2}$/', $_GET["month"] ) == 1 )
	$currentMonth = $_GET["month"];
else
	$currentMonth = date( "Y-m" );

// Monthes' navigation
$tCurrentMonth = strtotime( $currentMonth."-01" );
$tNextMonth = mktime( 0, 0, 0, date( "n", $tCurrentMonth ) + 1, date( "j", $tCurrentMonth ), date( "Y", $tCurrentMonth ) );
$tPreviousMonth = mktime( 0, 0, 0, date( "n", $tCurrentMonth ) - 1, date( "j", $tCurrentMonth ), date( "Y", $tCurrentMonth ) );

$template->addVariables( array(
	"previousMonthDate" => date( "Y-m", $tPreviousMonth ),
	"previousMonth" => ucfirst( $language["monthes"][date( "n", $tPreviousMonth ) - 1] )." ".date( "Y", $tPreviousMonth ),
	"currentMonth" => ucfirst( $language["monthes"][date( "n", $tCurrentMonth ) - 1] )." ".date( "Y", $tCurrentMonth ),
	"nextMonthDate" => date( "Y-m", $tNextMonth ),
	"nextMonth" => ucfirst( $language["monthes"][date( "n", $tNextMonth ) - 1] )." ".date( "Y", $tNextMonth )
) );

$account = $accounts[0];
$currentDateValues = explode( '-', $currentMonth );

$month = intval( $currentDateValues[1] );
$year = $currentDateValues[0];
$oneMonthAfter = date( 'Y-m-d', mktime( 0, 0, 0, $month + 1, 1, $year ) );
$oneMonthBefore = date( 'Y-m-d', mktime( 0, 0, 0, $month - 1, 1, $year ) );

$transactions = Transaction::get( array( "account_id" => $account->account_id, "transaction_date" => array( array( ">=", $oneMonthBefore ), array( "<", $oneMonthAfter ) ) ), "transaction_id DESC" );

$template->addVariables( array(
		"accountId" => $account->account_id,
		"accountName" => htmlentities( $account->getAccountName() )
	) );


$odd = true;
for( $i = 0 ; $i < count( $transactions ) ; $i++ )
{
	// Exit if the date is previous to the current month
	if( (!is_null( $transactions[$i]->real_date ) && $transactions[$i]->real_date < $currentMonth."-01") || (is_null( $transactions[$i]->real_date ) && $transactions[$i]->transaction_date < $currentMonth."-01") )
		continue;
	
	$tDate = "";
	$tTime = strtotime( $transactions[$i]->transaction_date );
	$tDate = date( "d", $tTime )." ".$language["short_monthes"][date( "n", $tTime ) - 1];

	$template->addBlock( new Block( "transaction", array(
		"label" => htmlentities( !is_null( $transactions[$i]->short_label ) ? $transactions[$i]->short_label : $transactions[$i]->label ),
		"date" => $tDate,
		"amount" => number_format( $transactions[$i]->amount, 2, ",", "&nbsp;" ),
		"type" => $transactions[$i]->type,
		"value" => floatval( $transactions[$i]->amount ) >= 0 ? "positive" : "negative",
		"odd" => $odd ? 1 : 0
	) ) );

	$odd = !$odd;
}

// Create graph data for the past month transactions
$amounts = array();
$amounts[$currentMonth] = array();

if( $currentMonth == date( 'Y-m' ) )
{
	$amounts[$currentMonth][date( "j" )] = floatval( $account->amount );
	$lastAmount = floatval( $account->amount );
}
else
{
	$lastAmount = $account->getAccountAmount( $currentMonth."-31" );
	$amounts[$currentMonth][31] = $lastAmount;
}

$template->addVariable( "accountAmount", number_format( $lastAmount, 2, ",", "&nbsp;" ) );

$tempAmount = $lastAmount;
$time = 0;

foreach( $transactions as $transaction )
{
	$time = strtotime( $transaction->transaction_date );

	if( !array_key_exists( date( "Y-m", $time ), $amounts ) )
	{
		// If there is no entry for the first of passed month, setting it
		if( !array_key_exists( "1", $amounts[$currentMonth] ) )
			$amounts[$currentMonth][1] = $tempAmount;
		
		// Creating new months data
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

	$template->addBlock( $monthBlock );
	$i++;
}

$template->show( "account.html" );
?>
