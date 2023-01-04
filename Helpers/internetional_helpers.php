<?php
use Config\App;
use Config\Services;

if(! function_exists("randomstring"))
{
	function randomstring($L)
	{
	    $strarr = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
	    $str ="";
	    while(strlen($str)<$L)
	    {
	        $str .= $strarr[rand(0,61)];
	    }
	    return $str;
	}
}
?>