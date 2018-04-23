<?php
require_once(dirname(__FILE__) . '/../AndroidNotification.php');

class AndroidBroadcast extends AndroidNotification {
	function  __construct($filter = []) {
		parent::__construct();
		if( !empty($filter) ){
			$this->data["type"] = "groupcast";
			$this->data["filter"]  = $filter;
		}else{
			$this->data["type"] = "broadcast";
		}
	}
}