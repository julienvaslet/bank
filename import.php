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
	
	if( count( $accounts ) == 0 )
	{
		$account = new Account();
		$account->account_name = $_POST["account"];
		$account->amount = NULL;
	}
	else
		$account = $accounts[0];
	

	if( array_key_exists( "content", $_POST ) && !empty( $_POST["content"] ) )
	{
		// transaction_date,value_date,amount,label,account_amount
		$csvTransactions = preg_split( "/[\r\n]+/", $_POST["content"] );
		
		foreach( $csvTransactions as $csvTransaction )
		{
			$values = str_getcsv( $csvTransaction );
			var_dump( $values );
			
			if( count( $values ) == 5 )
			{
				//...
			}
			else
			{
				// handle error
			}
		}
	}

/* Parse CSV:
date inverted... 
Date d'opération,Date de valeur,Montant,Libellé,Solde
01/27/2014,01/27/2014,-14.53,PAIEMENT CB 2501 TOULOUSE CARREFOUR CITY CARTE 05973845,837.55
01/28/2014,01/28/2014,-45.00,CHEQUE 3910275,748.74
01/28/2014,01/28/2014,2026.86,VIR ATOS INFOGERANCE ATOS INFOGERANCE,2775.60
02/04/2014,02/04/2014,-950.00,VIR SEPA LOYER ESTEBE 2014-02,1756.29
02/08/2014,02/08/2014,-20.00,RETRAIT DAB 0802 REF02220A00 CARTE **3845,705.49
02/10/2014,02/10/2014,-40.34,PRLV SEPA FREE TELECOM FREE HAUTDEBIT 466540192 FHD 466540192,665.15
02/12/2014,02/12/2014,237.50,VIR PETIT REMI LOYER FEVRIER,856.09
02/12/2014,02/01/2014,-4.16,F COTIS EUROCOMPTE JEUNE,1098.09
 */
}

?>
