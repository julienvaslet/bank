<?php
require_once( "common.php" );

require_once( RootPath."/classes/database/Account.class.php" );
require_once( RootPath."/classes/database/Transaction.class.php" );

use database\Account;
use database\Transaction;

$accounts = Account::get();

foreach( $accounts as $account )
{
	$accountBlock = new Block( "account" );
	$accountBlock->addVariables( array(
		"name" => htmlentities( $account->account_name ),
		"amount" => number_format( $account->amount, 2, ",", "&nbsp;" )."&nbsp;&euro;"
	) );

	$transactions = Transaction::get( array( "account_id" => $account->account_id ), "transaction_id DESC", 1, 5 );

	$odd = true;
	foreach( $transactions as $transaction )
	{
		$accountBlock->addBlock( new Block( "transaction", array(
			"label" => htmlentities( !is_null( $transaction->short_label ) ? $transaction->short_label : $transaction->label ),
			"date" => !is_null( $transaction->real_date ) ? $transaction->real_date : $transaction->date,
			"amount" => number_format( $transaction->amount, 2, ",", "&nbsp;" )."&nbsp;&euro;",
			"odd" => $odd ? 1 : 0
		) ) );

		$odd = !$odd;
	}

	$template->addBlock( $accountBlock );
}

$template->show( "accounts.html" );
?>
