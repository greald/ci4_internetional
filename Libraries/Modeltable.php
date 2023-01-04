<?php namespace Internetional\Libraries;

 // Name:    		Modeltable
 //
 // Created:  		2021-08-31
 //
 // Description:  	Generic abstract class to generate a table in database. To be extended by specific Model classes.
 // 
 //
 // Requirements: 	Codeigniter 4, PHP7.2 or above
 //
 // @package    	
 // @author     	Greald Henstra <greator@ghvernuft.nl>
 // @license    	use at will, and at your own risk
 // @link       	https://github.com/greald/ci4libraries/blob/main/Modeltable.php
 // @filesource

abstract class Modeltable
{
    // Force Extending class to define this method
    abstract public function getTable();
    abstract public function getTableFields();
    abstract public function getPrimaryKey();

    // Common properties

    // Common methods
    public function tableSetUpOnce() 
    {        
		$table 			= $this->getTable();
		$tableFields 	= $this->getTableFields();
        $primaryKey 	= $this->getPrimaryKey(); 
        		
		$db = db_connect();
		
		$alreadyset = ( $db->query(" SHOW TABLES LIKE '".$table."' ")->resultID->num_rows >0 );
		if( $alreadyset )
		{
		    // echo "\n<br/>".__METHOD__.__LINE__."\nTable '". $table . "' already there.";
		    return [ $alreadyset ];		    
		}
		else
		{
			$forge = \Config\Database::forge();
			//echo "\n<br/>".__METHOD__.__LINE__.""; var_dump($forge);
			
			$createtable = $forge->addField($tableFields)
				->addPrimaryKey($primaryKey)
				->createTable($table, TRUE); // TRUE implies CREATE TABLE IF NOT EXISTS table_name	
			// echo "\n<br/>".__METHOD__.__LINE__." create table\n "; var_dump($createtable);
			
			// primary key //////////////////////////////////////////////////////////////////////////		
			$Qaincr = "ALTER TABLE `".$table.
				"` CHANGE `".$primaryKey.
				"` `".$primaryKey.
				"` ".$tableFields[$primaryKey]['type'].
				"(".
				$tableFields[$primaryKey]['constraint'].
				") UNSIGNED NOT NULL AUTO_INCREMENT; ";
			// echo "\n<br/>".__METHOD__.__LINE__."\n". $Qaincr;	
			$primary = $db->query($Qaincr);
			
			// indexeren //////////////////////////////////////////////////////////////////////////
		    $indexed = null;
		    
	    	$Qindex = "ALTER TABLE `".$table.
				"` ADD INDEX `".$table."tail` (";
			$lastcomma = FALSE;
	    	foreach ($tableFields as $field=>$feats)
	    	{
				if (isset($feats['index']) && $feats['index'])
				{
					$Qindex .= "`". $field."`,";
					$lastcomma = $lastcomma || TRUE;
				}
			}
				
			if($lastcomma) // equiv: if index query is required
			{
				$Qindex = substr($Qindex, 0, -1) . ")"; // remove last comma
				// echo "\n<br/>".__METHOD__.__LINE__."\n\index query\n". $Qindex . " ;\n";			
				$indexed = $db->query($Qindex);
			}
				
			return [ $alreadyset, $primary, $indexed ];
		}
    }
}
