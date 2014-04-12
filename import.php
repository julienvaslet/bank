<?php
require_once( "common.php" );
require_once( RootPath."/classes/database/Account.class.php" );
require_once( RootPath."/classes/database/Transaction.class.php" );

use database\Account;
use database\Transaction;

if( array_key_exists( "account", $_POST ) && !empty( $_POST["account"] ) )
{
	$account = NULL;
	$accounts = Account::get( array( "account_name" => $_POST["account"] ) );

	if( count( $accounts ) > 0 )
	{
		$account = $accounts[0];
		echo "Account found: ".$account->account_name." (".$account->account_id.")\n";
	}

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
			{
				echo "The account is empty. Every transaction will be imported.\n";
				$importTransactions = true;
			}
		}
		else
			$importTransactions = true;

			
		$csvTransactions = preg_split( "/[\r\n]+/", trim( $_POST["content"] ) );
		echo "There is ".count( $csvTransactions )." posted lines.\n";
		
		foreach( $csvTransactions as $csvTransaction )
		{
			// transaction_date,value_date,amount,label,account_amount
			$values = str_getcsv( $csvTransaction );
			
			if( count( $values ) == 5 )
			{
				if( $importTransactions === false )
				{
					if( convertBankDate( $values[0] ) > $lastTransactions[0]->transaction_date )
					{
						echo "Transaction date is more recent than the last saved transaction. Next transactions will be imported.\n";
						$importTransactions = true;
					}
					else if( convertBankDate( $values[0] ) == $lastTransactions[0]->transaction_date )
					{
						$amount = floatval( $account->amount );

						$importTransactions = true;
						for( $i = 0 ; $i < count( $lastTransactions ) ; $i++ )
						{
							//echo "----vs----\n";
							//echo convertBankDate( $values[1] )." vs ".$lastTransactions[$i]->value_date."\n";
							//echo floatval( $values[2] )." vs ".floatval( $lastTransactions[$i]->amount )."\n";
							//echo $values[3]." vs ".$lastTransactions[$i]->label."\n";
							//echo floatval( $values[4] )." vs ".floatval( $amount )."\n";

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

						if( $importTransactions == true )
						{
							echo "New daily transaction detected. Next transactions will be imported.\n";
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
					{
						$account = Account::getInstance( array( "account_id" => $accountId ) );
						echo "Account created: ".$account->account_name." (".$account->account_id.")\n";
					}
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
					echo "Imported transaction #".$transactionId." of amount: ".floatval( $values[2] )."\n";
					$account->amount = floatval( $values[4] );
					$account->save();
				}
			}
			else
			{
				echo "Warning: CSV file has a wrong number of columns (".count( $values )." instead of 5).\n";
				echo "Debug line:\n----".$csvTransaction."\n----\n";
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
