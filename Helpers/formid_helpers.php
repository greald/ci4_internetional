<?php
if(! function_exists( "pedigree"))
{
	function pedigree()
	{
		return str_replace("\\Controllers\\", "_", __CLASS__);
	}
}
?>