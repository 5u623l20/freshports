<?php
	#
	# $Id: cache-port.php,v 1.6 2007-06-04 02:16:33 dan Exp $
	#
	# Copyright (c) 2006-2007 DVL Software Limited
	#

	require_once('cache.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/constants.php');

// base class for caching
// Supplies methods for adding, removing, and retrieving.
//

define('CACHE_PORT_COMMITS', 'Commits');
define('CACHE_PORT_DETAIL',  'Detail');

class CachePort extends Cache {

	const CacheCategory = 'ports';

	var $PageSize = 100;

	function __construct() {
		return parent::__construct();
	}
	
	function PageSizeSet($PageSize) {
		$this->PageSize = $PageSize;
	}

	function RetrievePort($Category, $Port, $CacheType = CACHE_PORT_COMMITS, $PageNum = 1, $Branch = BRANCH_HEAD) {
		$this->_Log("CachePort: Retrieving for $Category/$Port");
		$Key = $this->_PortKey($Category, $Port, $CacheType, $PageNum, $Branch);
		$result = parent::Retrieve($Key);

		return $result;
	}

	function AddPort($Category, $Port, $CacheType = CACHE_PORT_COMMITS, $PageNum = 1, $Branch = BRANCH_HEAD) {
		$this->_Log("CachePort: Adding for $Category/$Port");

		$CacheDir = $this->CacheDir . '/' . CachePort::CacheCategory . '/' . $Category . '/' . $Port;
		$Key = $this->_PortKey($Category, $Port, $CacheType, $PageNum, $Branch);
		 
		if (!file_exists($CacheDir)) {
			$this->_Log("CachePort: creating directory $CacheDir");
			$old_mask = umask(0000);
			if (!mkdir($CacheDir, 0774, true)) {
				$this->_Log("CachePort: unable to create directory $CacheDir");
			}
			umask($old_mask);
		}

		$result = 0;
		$result = parent::Add($Key);

		return $result;
	}

	function RemovePort($Category, $Port) {
		$this->_Log("CachePort: Removing for $Category/$Port");

		#
		# the wild card allows us to remove all cache entries for this port
		# regardless of the CacheType or page number
		#
		$Key = $this->_PortKey($Category, $Port, '*', '*', '*');
		$result = parent::Remove($Key, $data);

		return $result;
	}

	function _PortKey($Category, $Port, $CacheType, $PageNum = 1, $Branch = BRANCH_HEAD) {
		// might want some parameter checking here
		$Key = CachePort::CacheCategory . "/$Category/$Port/$CacheType.$Branch.PageSize$this->PageSize.PageNum$PageNum.html";

		return $Key;
	}

	function _CacheFileName($key) {
		return $this->CacheDir . '/'. $key;
	}
	

}
