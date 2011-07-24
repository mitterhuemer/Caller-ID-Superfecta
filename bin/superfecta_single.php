<?php

class superfecta_single extends superfecta_base {
	function __construct($db,$amp_conf,$debug,$thenumber_orig,$scheme_name,$scheme_param) {
		$this->debug = $debug;
		$sn = explode("_", $scheme_name);
		$this->scheme_name = $sn[1];
		$this->scheme = $scheme_name;
		$this->db = $db;
		$this->amp_conf = $amp_conf;
		$this->thenumber_orig = $thenumber_orig;
		$this->scheme_param = $scheme_param;
	}
	
	function get_results() {
		$sources = explode(",",$this->scheme_param['sources']);
		foreach($sources as $data) {
			$superfecta->caller_id = '';
			$start_time = $this->mctime_float();
			
			$sql = "SELECT field,value FROM superfectaconfig WHERE source = '".$this->scheme_name."_".$data."'";
			$run_param = $this->db->getAssoc($sql);
	        			
			if(file_exists("source-".$data.".module")) {
				require_once("source-".$data.".module");
				$source_class = NEW $data;
				//Gotta be a better way to do this
				$source_class->debug = $this->debug;
				$source_class->amp_conf = $this->amp_conf;
				$source_class->db = $this->db;
				if(method_exists($source_class, 'get_caller_id')) {
					$caller_id = $source_class->get_caller_id($this->thenumber,$run_param);
					$this->spam = $source_class->spam;
					unset($source_class);
					$caller_id = $this->_utf8_decode($caller_id);


					if(($this->first_caller_id == '') && ($caller_id != '')) {
						$this->first_caller_id = $caller_id;
						$winning_source = $data;
						if($this->debug)
						{
							$end_time_whole = $this->mctime_float();
						}
					}
				} elseif($this->debug) {
					print "Function 'get_caller_id' does not exist!<br>\n";
				}
			} elseif($this->debug) {
				print "Unable to find source '".$source_name."' skipping..<br\>\n";
			}
			
			if($this->debug)
			{
				if($caller_id != '')
				{
					print "'" . utf8_encode($caller_id)."'<br>\nresult <img src='images/scrollup.gif'> took ".number_format(($this->mctime_float()-$start_time),4)." seconds.<br>\n<br>\n";
				}
				else
				{
					print "result <img src='images/scrollup.gif'> took ".number_format(($this->mctime_float()-$start_time),4)." seconds.<br>\n<br>\n";
				}
			}
			else if($caller_id != '')
			{
				break;
			}
		}
		return($this->first_caller_id);
	}
	
	function send_results($caller_id) {
		$sources = explode(",",$this->scheme_param['sources']);
		
		if($this->debug)
		{
			$this->outn("Post CID retrieval processing.");
		}	
		foreach($sources as $source_name)
		{
			// Run the source
			$sql = "SELECT field,value FROM superfectaconfig WHERE source = '".$this->scheme_name."_".$source_name."'";
			$run_param = $this->db->getAssoc($sql);
			
			if(file_exists("source-".$source_name.".module")) {
				require_once("source-".$source_name.".module");
				$source_class = NEW $source_name;
				$source_class->db = $this->db;
				$source_class->debug = $this->debug;
				if(method_exists($source_class, 'post_processing')) {					
					$caller_id = $source_class->post_processing(FALSE,NULL,$caller_id,$run_param,$this->thenumber_orig);
				} else {
					print "Method 'post_processing' doesn't exist<br\>\n"; 
				}
			}
		}
	}
	
	//Run this when web debug is initiated
	function web_debug() {
		return($this->get_results());
	}
}