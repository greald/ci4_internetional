<?php 
namespace Internetional\Models; 

use CodeIgniter\Model; 
use Internetional\Libraries;

defined("FORMIDSTABLE") || define("FORMIDSTABLE", "ci4_formids");
defined("PEDIGREECODE") 
	|| define("PEDIGREECODE", 
		str_replace( '/',"_",
		str_replace( [ base_url(), '/index', '.php/'], "", current_url() )
	));

helper('Internetional\Helpers\internetional_helpers');	// defines randomstring()
helper('Internetional\Helpers\formid_helpers'); 		// defines pedigree()

class Formid extends Model 
{
	const FORMIDLENGTH = 32;
	
	// standard Model matters =================================== 
 	// https://codeigniter.com/user_guide/models/model.html#configuring-your-model 
 
	protected $table = FORMIDSTABLE; 
    protected $primaryKey; // = 'formidsRec'; // defined in Formidstable 
    protected $allowedFields = []; // to be set in $this->__construct() 
    protected $createdField; //  = 'created_at'; // defined in Formidstable 
 
 
	// specific Formid matters ==================================== 
 
	// primentity = property with value where primaryKey is key 
	// 
	// private $primentity = ""; // redundant: doubling the 'real' property formidsRec
	// 
	public function setPrimentity( $value = NULL ) 
	{ 
		$key = $this->primaryKey; 
		$setKey = "set".ucfirst($key); 
		$this->$setKey( $value ); // $this->$setKey() WORKS !!!!	 
	} 
	public function getPrimentity() 
	{ 
		$key = $this->primaryKey; 
		$getKey = "get".ucfirst($key); 
		return $this->$getKey(); 
	} 
	public function getPrimaryCouple() 
	{ 
		return [ $this->primaryKey => $this->getPrimentity() ]; 
	} 

	// property name that $this->primaryKey resolves to in this model 
	protected $formidsRec = NULL;	 
	public function getFormidsRec() { return $this->formidsRec; } 
	public function setFormidsRec($value) { $this->formidsRec = $value; } 
 
	// csrf formid 
	protected $formid = NULL;	 
	public function getFormid() { return $this->formid; } 
	public function setFormid($value) { $this->formid = $value; } 
 
	// value of $this->createdfield 
	protected $firstFormed = NULL;	 
	public function getFirstFormed() { return $this->firstFormed; } 
	public function setFirstFormed($value) { $this->firstFormed = $value; } 
 

	public function generate( $pedigree = "" )
	{
		// firstly cleanup obsolete stuff
		$this->prune();
		//$this->formid = csrf_hash(); // 62^10 = 839 299 365 868 340 224 = 8,3930 * 10^17 options
		$this->formid = randomstring(32); // 62^32 = 2 272 657 884 496 751 345 355 241 563 627 544 170 162 852 933 518 655 225 856
		$this->insert([ 'formid'=>$this->formid, $this->createdField => time(), 'pedigree' => $pedigree ]);
		return $this->formid;
	}
	
	public function make( $pedigree = "" )
	// replacing and deprecating $this->generate()
	{
		// firstly cleanup obsolete stuff
		$this->prune();

		$timestamp = time();
		$this->setFormid( randomstring( self::FORMIDLENGTH ));

		$this->insert([ 'formid'=>$this->formid, $this->createdField => $timestamp, 'pedigree' => $pedigree ]);
		return $this->getFormid();
	}
	
	public function validFormid( $pedigree = NULL ) 
	{
	// @param string
	// returns [ "rejected" | "token taken" , int | NULL ]
//		echo "\n<br/>".__METHOD__.__LINE__." pedigree in ".$pedigree;
		
		$uit = [];
		$idFormed = time();
		helper('prevald_helper');	
		
		$getPostFormid = str_replace( $pedigree, "", (\Config\Services::request())->getPost('formid'));
        $whereArr = [
	        'formid' => $getPostFormid, 
	        'pedigree' => $pedigree
        ];        
//		echo "\n<br/>".__METHOD__.__LINE__." splitup "; var_dump( $whereArr );
		
        // $already = (db_connect())->table($this->table)->getWhere($whereArr)->getResultArray();
        // AND first validate form. But then first validate the right form ...
        $Q = "SELECT * 
	        FROM `" .$this->table."` 
	        WHERE `".array_keys($whereArr)[0]."` = '".array_values($whereArr)[0]."' 
	        AND `"  .array_keys($whereArr)[1]."` = '".array_values($whereArr)[1]."' " ;
//		echo "\n<br/>".__METHOD__.__LINE__." query ".$Q;
		
        $already = (db_connect())->query($Q)->getResultArray();
//		echo "\n<br/>".__METHOD__.__LINE__." already "; var_dump( $already );
		
        if(in_array( $already,[ null, false, 0, "0", "", [] ]))
        // if 'formid' was not found in $this->table  // assumed: has not been sent by form
        {
	        $uit[] = "rejected";
	        return $uit;
        }
        elseif( in_array( $already[0][$this->createdField] , [null, false,[], 0, "0", ""]) ) // any value like 0, "", false, null, []
        // if start time not set yet
        { 
	        $whereArr [$this->createdField] = $idFormed;
	        
	        // global function getPrimaryKey() from \Prevald\Helpers
		    $this->update( getPrimaryKey($this, [ 'formid' => $whereArr['formid']]), [$this->createdField => $idFormed ]);
		    //$this->update( $this->getPrimentity(), [ $this->createdField => $idFormed ]);
        }
        else
        {
	        // $uit[] = "token taken";
		}		
	    
	    // global function getPrimaryKey() from \Prevald\Helpers
	    $used = getPrimaryKey($this, $whereArr);
        $this->setFormid( $used );
        $this->populate( " `". array_keys($whereArr)[0] ."` = '". array_values($whereArr)[0] ."' 
	        		   AND `". array_keys($whereArr)[1] ."` = '". array_values($whereArr)[1] ."' " );
        $uit[]= $this->getFormid();
        
        // avoid doubling input
        $this->trashused($used);
        
		return $uit;
	}

	public function matching( $needle = NULL )
	{
	// retrieve first record matching either $needle or $this->formid with 'formid' field in FORMIDSTABLE
	// @param string
	// return array | NULL 
		if( $needle == null )
		{
			if( $this->getFormid() == null )
			{
				return null;
			}
			else
			{
				$needle = $this->getFormid();
			} 
		}		
		$formidrow = $this -> where('formid', substr($needle, -( self::FORMIDLENGTH ))) -> first();
		
		return $formidrow;
	}

	public function match( $needle = NULL )
	{
	// check whether either $needle or $this->formid is found as 'formid' field in FORMIDSTABLE
	// return bool
		$matching = $this->matching( $needle );
		return is_array( $matching ) && $matching != [];
	}

	public function prune( $expired = 1959 )
	{
	// delete records from $this->table older than $expired seconds
	// @param $expired: string expiring age in seconds
	// return BaseBuilder | false
		$apoptosis = $this->createdField." < ";
		$expiresat = time() - $expired;
		return $this->where( $apoptosis, $expiresat ) -> delete();
	}
	
	public function trashused($used)
	{
	// delete records from $this->table with primaryKey = $used
		$this->delete($used);
	}

	public function populate($wherestring = " TRUE ") 
	// @param $wherestring SQL WHERE string 
	// like " `memberID` = 'jan' OR `memberID` = 'piet' AND `memberID` = 'joris' " 
	// make sure for proper escaping! 
	// return $this | NULL  
	{ 
		$queryinstance = $this -> where($wherestring) -> first();  
		// $queryinstance type set in $resultType; default: Array  
		// then:  
		if($queryinstance == [] )  
		{  
			// echo "\n<br/>\n<br/>".__METHOD__.__LINE__." Member instance not set "; 
		}
		else
		{
			$parentsProperties 	= array_keys( get_class_vars( get_parent_class($this))); 
			$thisProperties 	= array_keys( get_class_vars( get_class( $this ))); 
			$eigenProperties 	= array_diff( $thisProperties, $parentsProperties ); 
			
			foreach($queryinstance as $field => $grass) 
			{ 
				if( in_array( $field, $eigenProperties )) 
				{ 
					$this->$field = $grass; // works! https://www.php.net/manual/en/language.variables.variable.php 
				} 
			}
			echo "\n<br/>".__METHOD__.__LINE__." Formid populated\n<br/>"; // var_dump( $this );
		}
		return $this; 
	} 
	
	// get it all started ================================	 
	public function __construct() 
	{ 
		// Note: Parent constructors are not called implicitly // https://www.php.net/manual/en/language.oop5.decon.php 
		parent::__construct(); 
		
		// create table if not exists 
		$classtable = new Formidstable(); 
		// define table variables 
		$this->primaryKey = $classtable->getPrimaryKey(); 
		$this->createdField = $classtable->getCreatedField(); 
 
		// set $this->allowedFields 
		// https://codeigniter.com/user_guide/models/model.html#protecting-fields 
		$restrictedFields = [ $this->primaryKey ];//, $this->createdField]; 
		 
		$fields = array_keys($classtable->getTableFields()); 
		foreach ($fields as $field) 
		{ 
			// allow all unless forbidden 
			if(! in_array($field, $restrictedFields)) 
			{ 
				$this->allowedFields[] = $field; 
			} 
		} 
		//return "\n<br/>".__METHOD__.__LINE__."\nallowedFields "; var_dump($this->allowedFields); 
	} 
} 
 
class Formidstable extends \Internetional\Libraries\Modeltable 
{ 
//	protected $table = "pci4_formids"; 
	const TABLE = FORMIDSTABLE; 
	public $table = self::TABLE; 

	//public function setTable($X){$this->table = $X;} 
	public function getTable() {return $this->table;} 
	 
	protected $tableFields;  
	public function setTableFields() 
	{ 
		$this->tableFields = 	 
		[ 
	        $this->getPrimaryKey()	=> [ 
	            'type'           	=> 'INT', 
	            'constraint'     	=> 8, 
	            'unsigned'       	=> true, 
	        ], 
	        $this->getCreatedField()=> [ 
	            'type'           	=> 'VARCHAR', //INT', // 'TIMESTAMP' 
	            'constraint'     	=> 10, 
//	            'unsigned'       	=> true, 
	            'index'       		=> true, 
	        ], 
	        'formid'				=> [ 
	            'type'           	=> 'VARCHAR', 
	            'constraint'     	=> 32, 
	            'index'       		=> true, 
	        ], 
	        'pedigree'				=> [ 
	            'type'           	=> 'VARCHAR', 
	            'constraint'     	=> 255, 
	            'index'       		=> false,
	        ], 
		]; 
	} 
	public function getTableFields() {return $this->tableFields;}	 
	 
	protected $primaryKey = 'formidsRec'; 
	//public function setPrimaryKey($X){$this->primaryKey = $X;} 
	public function getPrimaryKey() {return $this->primaryKey;} 
	 
	protected $createdField  = 'firstFormed';//created_at'; 
	//public function setPrimaryKey($X){$this->primaryKey = $X;} 
	public function getCreatedField() {return $this->createdField;} 
	 
	public function __construct() 
	{ 
		// echo "\n<br/>".__METHOD__.__LINE__." table name\n". $this->table;
		$this->setTableFields();
		// echo "\n<br/>".__METHOD__.__LINE__." tablefields\n"; var_dump( $this->getTableFields() );
		$setbefore = $this->tableSetUpOnce();
		
		if( FALSE ) // ! $setbefore[0] ) // timestamp werkt niet interactief
		{
			// set createdField to no updates /////////////////////////////////// 
			// https://dev.mysql.com/doc/refman/8.0/en/timestamp-initialization.html
			$QAts = "ALTER TABLE `".$this->table. 
			"` CHANGE `".$this->getCreatedField(). 
			"`  `".$this->getCreatedField(). 
			"` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP; "; 
			(db_connect())->query($QAts);
		}
	} 
}