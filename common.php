<?php
define( "RootPath", dirname( __FILE__ ) );
define( 'TemplateName', 'basic' );

require_once( RootPath. "/classes/database/Database.class.php" );
require_once( RootPath. "/classes/database/Object.class.php" );

use database\Database;
use database\Object;

new Database( "127.0.0.1", 3306, "money", "money", "money" );


/*
 * Global functions
 */
 
function parseTransactionLabel( $label, $transactionDate )
{
	$parsedLabel = array(
		"real_date" => NULL,
		"type" => NULL,
		"third_party" => NULL,
		"short_label" => NULL
	);
	
	$transactionDateElements = explode( "-", $transactionDate );
	
	// Credit card
	if( preg_match( "/^PAIEMENT CB ([0-9]{2})([0-9]{2}) /", $label, $matches ) )
	{
		$parsedLabel["type"] = "credit-card";
		$parsedLabel["real_date"] = $transactionDateElements[0]."-".$matches[2]."-".$matches[1];
		$parsedLabel["short_label"] = preg_replace( "/ CARTE [\*0-9]+$/", "", substr( $label, strlen( $matches[0] ) ) );
	}
	
	// Check
	else if( preg_match( "/^CHEQUE /", $label, $matches ) )
	{
		$parsedLabel["type"] = "check";
		$parsedLabel["short_label"] = substr( $label, strlen( $matches[0] ) );
	}
	
	// Transfer
	else if( preg_match( "/^VIR( SEPA)? /", $label, $matches ) )
	{
		$parsedLabel["type"] = "transfer";
		$parsedLabel["short_label"] = substr( $label, strlen( $matches[0] ) );
	}
	
	// Contribution
	else if( preg_match( "/^F COTIS /", $label, $matches ) )
	{
		$parsedLabel["type"] = "contribution";
		$parsedLabel["short_label"] = substr( $label, strlen( $matches[0] ) );
	}
	
	// Debit
	else if( preg_match( "/^PRLV( SEPA)? /", $label, $matches ) )
	{
		$parsedLabel["type"] = "debit";
		$parsedLabel["short_label"] = substr( $label, strlen( $matches[0] ) );
	}
	
	// Withdrawal
	else if( preg_match( "/^RETRAIT DAB ([0-9]{2})([0-9]{2}) /", $label, $matches ) )
	{
		$parsedLabel["type"] = "withdrawal";
		$parsedLabel["real_date"] = $transactionDateElements[0]."-".$matches[2]."-".$matches[1];
		$parsedLabel["short_label"] = preg_replace( "/ CARTE [\*0-9]+$/", "", substr( $label, strlen( $matches[0] ) ) );
	}
	
	return $parsedLabel;
}

require_once( RootPath.'/classes/internet/Template.class.php' );
$template = new Template( RootPath. '/templates/'. TemplateName );
$template->addVariable( 'TemplateUri', '/templates/'. TemplateName );

$language = array(
	"days" => array( "dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi" ),
	"short_days" => array( "dim", "lun", "mar", "mer", "jeu", "ven", "sam" ),
	"monthes" => array( "janvier", "f&eacute;vrier", "mars", "avril", "mai", "juin", "juillet", "ao&ucirc;t", "septembre", "octobre", "novembre", "d&eacute;cembre" ),
	"short_monthes" => array( "jan", "f&eacute;v", "mar", "avr", "mai", "juin", "juil", "ao&ucirc;", "sep", "oct", "nov", "d&eacute;c" ),
	"date_format" => "d/m/Y",
	"currency" => "&euro;"
);

function addLanguageVariables( $variables, $basename )
{
	global $template;
	
	foreach( $variables as $name => $value )
	{
		if( is_array( $value ) )
			addLanguageVariables( $value, $basename.".".$name );
		else
			$template->addVariable( $basename.".".$name, $value );
	}
}

addLanguageVariables( $language, "language" );

?>
