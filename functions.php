<?php

function execPHP ($file, $flush=true) {
    
    //$c = '';
    if ($flush) {
        ob_flush(); // NOT WISE AT ALL (nested calls to execPHP() will crash JSON decoding in the browser due to HTML inserted in AJAX response before the JSON data.
        $c = '';
    } else {
        $c = ob_get_contents(); 
        if ($c===false) $c = '';
    }
    ob_end_clean();
    ob_start();
    //echo 'ob_get_level='; var_dump (ob_get_level()); echo PHP_EOL.PHP_EOL;
    $p = strpos($file,'?');
    $qs = substr($file, $p+1, strlen($file)-$p-1);
    $f = (
        $p === false
        ? $file
        : substr($file, 0, $p)
    );
    //echo $qs; die();
    if ($p!==false) parse_str ($qs, $_GET); // may seem like a dirty hack, but isn't.    
    /*
    echo '$flush='; var_dump ($flush); echo PHP_EOL.PHP_EOL;
    echo '$f='; var_dump ($f); echo PHP_EOL.PHP_EOL;
    */
    require_once ($f);
    $c .= ob_get_contents();
    ob_end_clean();
    ob_start();
    return $c;
};

function require_return ($file) {
// used by .../domainConfigs/DOMAIN.EXT/mainmenu.php
    ob_start();
    require ($file);
    $r = ob_get_clean();
    return $r;
}


function getFilePathList ( 
//TODO: all features listed below $level are untested.
	
	//$pathStart, 
	$path,								// current path 
	$recursive = false,					// if true, we also process any subdirectory.
	$fileSpecRE = "/.*/",				// Regular Expression file specs - will be matched against any filename found.
	// ^-- this is NOT the same as normal "somefile-*.something.extension" type wildcards. see example above.
	$fileTypesFilter = array (),		// array (int=>string (filetype() result) ==== int=>"file"|"dir" )
	$depth = null,
	$level = 1,
	$returnRecursive = false,
	$ownerFilter = array (),			// array (int=>string (username) ); only return files owned by someone in $ownerFilter.
	$fileSizeMin = null,				// If >=0, any files returned must have a minimum size of $fileSizeMin bytes.
	$fileSizeMax = null,				// same as above, but maximum size

	/* all date parameters below must be provided in the mktime() format. */
	$aTimeMin = null,					// calls fileatime(). Read The Friendly Manual. http://www.php.net/manual/
	$aTimeMax = null,					//	^- access includes a program reading from this file.
	$mTimeMin = null,					// calls filemtime(). RTFM.
	$mTimeMax = null,
	$cTimeMin = null,					// calls filectime(). rtfm.
	$cTimeMax = null,
	/*	on windows XP, cTime = creation time; mTime = modified time; aTime = access time. 
		I also noted some BUGS in retrieving these dates from my system.
	*/
	$listCall = ""						// interesting feature; lets you include results from any informational file function(s).
/*	TODO : fix $*Date* parameter handling, 
	returns an array consisting of all files in a directory structure, filtered by the parameters given.
	results are returned in directory order. if ($recursive) then subdirectory content is listed before file content.
	OKAY, this one is monolithic :)   But very usefull, so an exception to the rule is granted here.
example: 
	htmlDump (getFilePathList("c:/dat/web", true, "/.*\.php$|.*\.php\d$|.*\.inc$/",
		array(), array(), null, null, null, null, null, null, null, null,
		"\"ctime=\".date (\"Y/m/d H:m:s\", filectime (\$filepath)).".
		"\" - atime=\".date (\"Y/m/d H:m:s\", fileatime (\$filepath)).".
		"\" - mtime=\".date (\"Y/m/d H:m:s\", filemtime (\$filepath)).".
		";"
		));
	-== this returns an array with complete filepaths of all files under c:/dat/web, that have an extension like
		*.php, *.php3, *.php4 or *.inc. 
		for my system, it returns:
			array(4) {
			  [0]=>
			  string(115) "c:/dat/web/index.php - [listCall=ctime=2003/05/11 18:05:26 - atime=2003/05/16 05:05:44 - mtime=2003/05/16 05:05:44]"
			  [1]=>
			  string(122) "c:/dat/web/preProcessor.php - [listCall=ctime=2003/05/15 16:05:55 - atime=2003/05/16 04:05:47 - mtime=2003/05/15 17:05:35]"
			  [2]=>
			  string(116) "c:/dat/web/source.php - [listCall=ctime=2003/05/11 18:05:26 - atime=2003/05/16 04:05:47 - mtime=2003/04/28 13:04:07]"
			  [3]=>
			  string(117) "c:/dat/web/sources.php - [listCall=ctime=2003/05/11 18:05:26 - atime=2003/05/16 04:05:50 - mtime=2003/05/12 00:05:22]"
}
		in this example, the $listCall is kinda complicated. but only to show it's power.
		if you're having trouble debugging your $listCall, turn on the relevant htmlDump() call in this function.
	
another example:
	htmlDump (getFilePathList("c:/dat/web", false, "/.*\.php$|.*\.php\d$|.*\.inc$/", 
		array(), array(), null, null, null, null, null, time()-mktime (0,0,0,0,1,0));
	-== this returns, for my system, all *.php,*.php3/4,*.inc files in c:/dat/web, that havent changed since 24 hours ago:
*/

) {
	//if (stripos($path, $pathStart)!==false) {
		//echo '<pre style="color:cyan;">'; var_dump ($path); echo '</pre>'; die();
		$path = realpath($path);
		$result = array();
		//if (!in_array("file",$fileTypesFilter)) $fileTypesFilter[count($fileTypesFilter)]="file";
		//htmlOut (" --== $path ==--");
		if ($path[strlen($path)-1]!="/") $path.="/";
		//echo $path.'<br/>'; 
		if ($handle = opendir($path)) {
			/* This is the correct way to loop over the directory. */
			while (false !== ($file = readdir($handle))) { 
			//if (!is_file($path.$file)) continue;
			if ($file != "." && $file != "..") { 
			
				$pass = true;
				//echo $path.$file.'<br/>'; 
				$ft = filetype($path.$file); 
				if (!in_array ($ft, $fileTypesFilter)) $pass = false;
				// htmlDump ($ft, "filesys");
				if ($ft=="dir") $filepath = $path.$file."/"; else $filepath = $path.$file;
				
				//echo '<pre>';
				//var_dump ($file); echo PHP_EOL;
				//var_dump ($fileSpecRE); echo PHP_EOL;
				if ($pass) $pass = preg_match ($fileSpecRE, strToLower($file))!==0;
				//var_dump ($pass); echo PHP_EOL;
				//echo '</pre>';
				if ($pass && count($ownerFilter)>0) {
					$fo = fileowner ($filepath);
					if ($fo!=false) {
						$fo = posix_getpwuid($fo);
						if (!in_array ($fo, $ownerFilter)) $pass=false;
					} else {
					//couldn't retrieve username. be strict & safe, fail.
						$pass = false;
					}
				}
				if ($pass && isset($fileSizeMin)) if (filesize ($filepath) < $fileSizeMin) $pass=false;
				if ($pass && isset($fileSizeMax)) if (filesize ($filepath) > $fileSizeMax) $pass=false;

				if ($pass && isset($aTimeMin)) 
					$pass=evalDate ("fileatime", $filepath, ">=", $aTimeMin, "aTimeMin");
				if ($pass==true && isset($aTimeMax)) 
				//	^- if ($stringValue) == always true!, 
				//		so explicitly check for boolean true result after calling 
				//		functions that may return an (error) string.
					$pass=evalDate ("fileatime", $filepath, "<=", $aTimeMax, "aTimeMax");
				if ($pass==true && isset($mTimeMin))
					$pass=evalDate ("filemtime", $filepath, ">=", $mTimeMin, "mTimeMin");
				if ($pass==true && isset($mTimeMax))
					$pass=evalDate ("filemtime", $filepath, "<=", $mTimeMax, "mTimeMax");
				if ($pass==true && isset($cTimeMin))
					$pass=evalDate ("filectime", $filepath, ">=", $cTimeMin, "cTimeMin");
				if ($pass==true && isset($cTimeMax))
					$pass=evalDate ("filectime", $filepath, "<=", $cTimeMax, "cTimeMax");

				if ($pass==true) {
					//htmlOut ("PASSED");
					$r = "";

					$ev = "\$r = $listCall";
					//htmlDump ($ev);
					if (!empty($listCall)) eval ($ev);
					$idx = count ($result);
					if (!empty($r)) $r = " - [listCall=$r]";
					if (!$returnRecursive) {
                        $result[$idx] = $filepath.$r;
                    } else {
                        $result[$idx] = basename($filepath.$r);
                    }
				}
				if (is_string($pass)) {
					//htmlOut ("PASSED - checks failed");
                    $result[count($result)] = "[$pass]".$filepath;
				}
				
				if ($recursive && $ft=="dir" && (is_null($depth) || $level<$depth)) {
					$subdir = getFilePathList ($filepath,$recursive, $fileSpecRE, 
						$fileTypesFilter, $depth, $level+1, $returnRecursive, $ownerFilter, $fileSizeMin, $fileSizeMax, 
						$aTimeMin, $aTimeMax, $mTimeMin, $mTimeMax,
						$cTimeMin, $cTimeMax, $listCall);
					if (!$returnRecursive) {
                        array_splice ($result, count($result)+1, 0, $subdir);
                    } else {
                        $result[basename($filepath.$r)] = $subdir;
                    }
				}
			}
			}
		//}
		//htmlDump ($result, "result");
		return $result;
	}
	$result = array();
	return $result;
}
?>
