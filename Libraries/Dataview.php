<?php
namespace Internetional\Libraries;

use Internetional\Models\Formid;

class Dataview
{
// prearranging dataset $viewdata to be sent to View file $viewname
// preparing csrf code 'formid' in html form

	private $context	= ""; // view files' location : https://codeigniter.com/user_guide/outgoing/views.html#namespaced-views
	private $viewname	= "";
	private $viewdata	= [];
	
	public function getView(){ return $this->viewname; }
	
	public function getViewData(){ return $this->viewdata; }
	
	public function display()
	{
		$view =  $this->context . $this->viewname; 
		return view($view, $this->getViewData() ); 
	}
	
	public function __construct( $context, $viewname, $dataAssArr, $defaultData = [] )
	{
	// @param $context: 	string e.g "Access\\Views\\"
	// @param $viewname 	string e.g "checkin"
	//
	// @param $dataAssArr 	array  e.g [ 'formid'=>'1234567890', "login"=>"log in", ]
	// @param $dataAssArr['formid'] dummy value to be overwritten
	//
	// @param $defaultData	array  e.g [ 'formid'=>'1234567890', "login"=>"log in", ]
	// @param $defaultData['formid'] dummy value to be overwritten
		
		if( $defaultData == [] ){ $defaultData = $dataAssArr; }
		
		$this->context = $context;
		$this->viewname = $viewname;
		
		// preparing csrf code in html form
		if( key_exists('formid', $defaultData) )
	    {
	    	$formidInstance = new Formid;
	    	$defaultData['formid'] = $formidInstance->make( PEDIGREECODE );
	    }

		foreach( $defaultData as $dataVariable => $dataValue)
		{
			$this->viewdata[$dataVariable] = 
				isset(	$dataAssArr[  $dataVariable ]) 
				?		$dataAssArr[  $dataVariable ] 
				: 	$defaultData[ $dataVariable ] ;
		}
	}
}