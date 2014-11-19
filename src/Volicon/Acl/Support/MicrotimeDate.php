<?php namespace Volicon\Acl\Support;

/**
 * Description of MicrotimeDate
 *
 * @author nadav.v
 */
class MicrotimeDate {
	public $millisec;
	public $sec;
	
	public function __construct($microtime = null) {
		if(!$microtime) {
			$microtime = microtime();
		}
		$parts = explode(' ', $microtime);
		$this->millisec = (float)$parts[0];
		$this->sec = (double)$parts[1];
	}
	
	/**
	 * 
	 * @param \Volicon\Acl\Support\MicrotimeDate $md
	 * @return boolean return true if parameter is newer then $this
	 */
	public function compare(MicrotimeDate $md) {
		return $md->sec > $this->sec || ($md->sec == $this->sec && $md->millisec > $this->millisec);
	}
}
