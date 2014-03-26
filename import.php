<?php
require_once( "common.php" );
require_once( RootPath."/classes/database/Account.class.php" );
require_once( RootPath."/classes/database/Transaction.class.php" );

use database\Account;
use database\Transaction;

// Test data
$_POST["account"] = "ACCOUNT0001 0001";
$_POST["content"] = <<<EOF
04/02/2014,04/02/2014,-950.00,VIR SEPA LOYER,1756.29
05/02/2014,15/02/2014,1200.00,VIR TOTO CIE,2956.29
07/02/2014,08/02/2014,-100.00,PAIEMENT CB 0602 KUALA LUMPUR CAFE CARTE 0003456,2856.29
07/02/2014,08/02/2014,-50.00,PAIEMENT CB 0602 KUALA LUMPUR TABAC CARTE 0003456,2806.29
07/02/2014,08/02/2014,-50.00,PAIEMENT CB 0602 KUALA LUMPUR TABAC CARTE 0003456,2756.29
EOF;

if( array_key_exists( "account", $_POST ) && !empty( $_POST["account"] ) )
{
	$account = NULL;
	$accounts = Account::get( array( "account_name" => $_POST["account"] ) );
	
	if( count( $accounts ) > 0 )
		$account = $accounts[0];

	if( array_key_exists( "content", $_POST ) && !empty( $_POST["content"] ) )
	{
		$importTransactions = false;
		$lastTransactions = array();
		
		if( $account != NULL )
		{
			$lastTransactionSet = Transaction::get( array( "account_id" => $account->account_id ), "transaction_id DESC", 1, 1 );
			
			if( count( $lastTransactionSet ) > 0 )
				$lastTransactions = Transaction::get( array( "account_id" => $account->account_id, "transaction_date" => $lastTransactionSet[0]->transaction_date ), "transaction_id DESC" );
			else
				$importTransactions = true;
		}
		else
			$importTransactions = true;
		
		$csvTransactions = preg_split( "/[\r\n]+/", $_POST["content"] );
		
		foreach( $csvTransactions as $csvTransaction )
		{
			// transaction_date,value_date,amount,label,account_amount
			$values = str_getcsv( $csvTransaction );
			
			if( count( $values ) == 5 )
			{
				if( $importTransactions === false )
				{
					if( convertBankDate( $values[0] ) > $lastTransactions[0]->transaction_date )
						$importTransactions = true;
					else if( convertBankDate( $values[0] ) == $lastTransactions[0]->transaction_date )
					{
						$amount = floatval( $account->amount );

						$importTransactions = true;
						for( $i = 0 ; $i < count( $lastTransactions ) ; $i++ )
						{
							if( convertBankDate( $values[1] ) == $lastTransactions[$i]->value_date
							 && floatval( $values[2] ) == floatval( $lastTransactions[$i]->amount )
							 && $values[3] == $lastTransactions[$i]->label
							 && floatval( $values[4] ) == floatval( $amount ) )
							{
								$importTransactions = false;
								break;
							}

							$amount -= floatval( $lastTransactions[$i]->amount );
						}
					}

					if( $importTransactions === false )
						continue;
				}

				if( $account == NULL )
				{
					$accountId = Account::create( array(
						"account_name" => $_POST["account"],
						"amount" => floatval( $values[4] ) - floatval( $values[2] )
					) );

					if( $accountId !== false )
						$account = Account::getInstance( $accountId );
				}

				// Process CSV line
				$parsedLabel = parseTransactionLabel( $values[3], convertBankDate( $values[0] ) );
				
				$transactionId = Transaction::create( array(
					"account_id" => $account->account_id,
					"transaction_date" => convertBankDate( $values[0] ),
					"value_date" => convertBankDate( $values[1] ),
					"amount" => floatval( $values[2] ),
					"label" => $values[3],
					"real_date" => $parsedLabel["real_date"],
					"type" => $parsedLabel["type"],
					"third_party" => $parsedLabel["third_party"],
					"short_label" => $parsedLabel["short_label"]
				) );

				if( $transactionId !== false )
				{
					$account->amount = floatval( $values[4] );
					$account->save();
				}
			}
			else
			{
				// handle error ? 
			}
		}
	}
}

function convertBankDate( $bank_date )
{
	$ns = explode( "/", $bank_date );
	return $ns[2]."-".$ns[1]."-".$ns[0];
}

?>
