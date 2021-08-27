<?php 
require_once(dirname(__FILE__).'/3rd-party/vendor/autoload.php');
use Birke\Rememberme\Authenticator;
use Birke\Rememberme\Storage\FileStorage;

$phpScript_startupTime = microtime(true); global $phpScript_startupTime;
function mainErrorHandler ($errno, $errstr, $errfile, $errline, $errcontext) {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting, so let it fall
        // through to the standard PHP error handler
        return false;
    }
    
    switch ($errno) {
        case E_ERROR : $errtype = 'E_ERROR'; break;
        case E_WARNING : $errtype = 'E_WARNING'; break;
        case E_PARSE : $errtype = 'E_PARSE'; break;
        case E_NOTICE : $errtype = 'E_NOTICE'; break;
        case E_CORE_ERROR : $errtype = 'E_CORE_ERROR'; break;
        case E_CORE_WARNING : $errtype = 'E_CORE_WARNING'; break;
        case E_COMPILE_ERROR : $errtype = 'E_COMPILE_ERROR'; break;
        case E_COMPILE_WARNING : $errtype = 'E_COMPILE_WARNING'; break;
        case E_USER_ERROR : $errtype = 'E_USER_ERROR'; break;
        case E_USER_WARNING : $errtype = 'E_USER_WARNING'; break;
        case E_USER_NOTICE : $errtype = 'E_USER_NOTICE'; break;
        case E_STRICT : $errtype = 'E_STRICT'; break;
        case E_RECOVERABLE_ERROR : $errtype = 'E_RECOVERABLE_ERROR'; break;
        case E_DEPRECATED : $errtype = 'E_DEPRECATED'; break;
        case E_USER_DEPRECATED : $errtype = 'E_USER_DEPRECATED'; break;
        default : $errtype = 'E-UNKNOWN-ERRORTYPE'; break;
    }        
    
    global $phpScript_startupTime;
    $time = microtime(true) - $phpScript_startupTime;
    
    $errhtml = 
        PHP_EOL.
        '<div class="phpError">'
            .'<div class="phpErrorLabel">PHP Error</div>'
            .'<div class="phpErrorDetails">'
                .'<span class="phpErrorTimeLabel">Time since start : </span>'
                .'<span class="phpErrorTimeSinceScriptStart">'.$time.'</span><br/>'
                .'<span class="phpErrorTypeLabel">Type : </span>'
                .'<span class="phpErrorType">'.$errtype.'</span><br/>'
                .'<span class="phpErrorMsgLabel">Message : </span>'
                .'<span class="phpErrorMsg">'.$errstr.'</span>'
            .'</div>'
            .'<div class="phpErrorLocation"><span class="phpErrorLocationLabel">Location : </span><span class="phpError_inFile">'.$errfile.'</span>:<span class="phpError_line">'.$errline.'</span></div>'.PHP_EOL;
        $errhtml .=
            '<div class="phpDebugBacktrace"><span class="phpDebugBacktraceLabel">Backtrace : </span>'.backtrace().'</div>';
        //echo '<pre style="color:darkgreen;font-size:small;">'; var_dump ($errcontext); echo '</pre>'; // info overload, usually        
        if (is_array($errcontext) && count($errcontext) > 0 && json_encode($errcontext)!='') {
            
            $errhtml .= 
                '<div class="phpErrorContext"><span class="phpErrorContextLabel">Context : </span>'.PHP_EOL.'<pre>'.json_encode($errcontext,JSON_PRETTY_PRINT).'</pre></div>';
        }
    $errhtml .= '</div>'.PHP_EOL.PHP_EOL;
    
    global $mainErrorLogFilepath; global $mainErrorLogLastWriteFilepath;
    if (isset($naErrorLogFile) && is_string($naErrorLogFile) && is_writeable($naErrorLogFile)) {
        $f = fopen ($naErrorLogFile, 'a');
        fwrite ($f, $errhtml);
        fclose ($f);
        $f = fopen ($naErrorLogLastWriteFile, 'w');
        fwrite ($f, date('Y-m-d H:i:s'));
        fclose ($f);
    } else {
        echo $errhtml;
    }
    
    if (
        $errno===E_ERROR
        || $errno===E_CORE_ERROR
        || $errno===E_COMPILE_ERROR
        || $errno===E_USER_ERROR
    ) die();
}

function backtrace() {
    $data = debug_backtrace();
    //$data = array_splice ($data, 1, 2);
    //return '<pre>'.json_encode($data, JSON_PRETTY_PRINT).'</pre>';
    $html = '';
    while (count($data) > 0) {
        
        $it = array_shift($data);
        
        if (
            array_key_exists('function', $it) 
            && (
                // ignore these, as they are part of the custom error handling system itself :
                $it['function'] == 'backtrace'
                || $it['function'] == 'mainErrorHandler'
                || $it['function'] == 'trigger_error'
            )
        ) continue;
        
        $html .= '<div class="backtraceItem">'.PHP_EOL;
        if (array_key_exists('class', $it) && $it['class']!=='') {
            $html .=
                '<span class="backtraceCodeLocation">'
                .'<span class="backtraceClassLabel">Class </span>'
                .'<span class="backtraceClass">'.$it['class'].'</span>'
                .'<span class="backtraceType">'.$it['type'].'</span>'
                .'<span class="backtraceFunction">'.$it['function'].'</span>'
                .'</span><br/>'.PHP_EOL;
        } else if (array_key_exists('function', $it) && $it['function']!=='') {
            $html .=
                '<span class="backtraceCodeLocation">'
                .'<span class="backtraceFunctionLabel">Function </span>'
                .'<span class="backtraceFunction">'.$it['function'].'</span>'
                .'</span><br/>'.PHP_EOL;
        }
        if (array_key_exists('file', $it) && $it['file']!=='') {
            $html .= 
                '<span class="backtraceFileLabel">Location : </span>'
                .'<span class="backtraceFile">'.$it['file'].'</span>:'
                .'<span class="backtraceLineNum">'.$it['line'].'</span>'.PHP_EOL;
        }
        if (array_key_exists('args',$it) && is_array($it['args']) && count($it['args']) > 0) {
            $html .= '<div class="backtraceFunctionArgs"><span class="backtraceFunctionArgsLabel">Function arguments : </span><br/>';
            foreach ($it['args'] as $idx => $arg) {
                if (is_array($arg)) {
                    $html .= "\t".'<span class="backtraceFunctionArg"><pre>'.json_encode($arg,JSON_PRETTY_PRINT).'</pre></span>,<br/>'.PHP_EOL;
                } else {
                    $html .= "\t".'<span class="backtraceFunctionArg">'.$arg.'</span><span class="backtraceFunctionArgSeperator">,</span><br/>'.PHP_EOL;
                }
            }
            $html .= '</div>'.PHP_EOL;
        }
        $html .= '</div><br/>'.PHP_EOL;    
    }
    return $html;
}

function checkForJSONerrors($rawData, $filepath, $exampleFilepath) {
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error =
            '<div class="phpJSONerror">'.PHP_EOL
                .'<div class="phpJSONerrorTitle">PHP JSON ERRROR :</div>'.PHP_EOL
                .'<div class="phpJSONerror_location">'.PHP_EOL
                    .'<span class="phpJSONerror_filepathLabel">File path : </span>'
                    .'<span class="phpJSONerror_filepath">'.$filepath.'</span><br/>'.PHP_EOL
                    .'<span class="phpJSONerror_exampleFilepathLabel">Example template file path : </span>'.PHP_EOL
                    .'<span class="phpJSONerror_exampleFilepath">'.$exampleFilepath.'</span>'.PHP_EOL
                .'</div>'.PHP_EOL
                .'<div class="phpJSONerror_rawData">'.htmlspecialchars($rawData).'</div>'.PHP_EOL
                .'<div class="phpJSONerror_message">'
                    .'<span class="phpJSONerror_msgLabel">Error message : </span>'
                    .'<span class="phpJSONerror_msg">'.json_last_error_msg().'</span>'
                .'</div>'
            .'</div>';
        trigger_error ($error, E_USER_ERROR);
    }
}





function login($cdb) {
    if (array_key_exists('cdb_authSession_cookie',$_SESSION) && !is_null($_SESSION['cdb_authSession_cookie'])) {
        $cdb->loginByCookie ($_SESSION['cdb_authSession_cookie']);
    } else {
        $cdb->login ('Guest', 'Guest');
    }
}


function useRememberme() {
    // Initialize RememberMe Library with file storage
    $storagePath = dirname(__FILE__)."/siteCache/tokens";
    if (!is_writable($storagePath) || !is_dir($storagePath)) {
        die(
            "'$storagePath' does not exist or is not writable by the web server.\n".
            "To run the example, please create the directory and give it the correct permissions."
        );
    }
    global $rememberMeStorage;
    $rememberMeStorage = new FileStorage($storagePath);
    global $rememberMe;
    $rememberMe = new Authenticator($rememberMeStorage);
    global $loginResult;
    $loginResult = $rememberMe->login();
    
    if ($loginResult->isSuccess()) {
        $_SESSION['cdb_loginName'] = $loginResult->getCredential();
        setcookie('cdb_loginName', $loginResult->getCredential());
        //echo '<pre>';var_dump ($_SESSION);die();
        // There is a chance that an attacker has stolen the login token, so we store
        // the fact that the user was logged in via RememberMe (instead of login form)
        $_SESSION['remembered_by_cookie'] = true;
        
        return;
    } else {
        $rememberMe->clearCookie();
        $_SESSION['cdb_loginName'] = 'Guest';
        setcookie('cdb_loginName', 'Guest');
        unset($_SESSION['remembered_by_cookie']);
        //echo 'Could not login'; die();
    }
    
    if ($loginResult->hasPossibleManipulation()) {
        //render_template("cookie_was_stolen");
        //exit();
        $rememberMe->clearCookie();
        $_SESSION['cdb_loginName'] = 'Guest';
        setcookie('cdb_loginName', 'Guest');
        unset($_SESSION['remembered_by_cookie']);
    }
    // Log out when tokens have expired and user is still logged in with remember me
    // This state can happen in two cases:
    // a) The triples were cleared after an attack or a "global logout"
    // b) The triples have expired
    if ($loginResult->isExpired() && !empty($_SESSION['cdb_loginName']) && !empty($_SESSION['remembered_by_cookie'])) {
        $rememberMe->clearCookie();
        $_SESSION['cdb_loginName'] = 'Guest';
        setcookie('cdb_loginName', 'Guest');
        unset($_SESSION['remembered_by_cookie']);
        //render_template('login', 'You were logged out because the "Remember Me" cookie was no longer valid.');
        //exit;
    }
    if ($loginResult->isExpired() && !empty($_SESSION['cdb_loginName'])) {
        // Do rate limiting here. Lots of requests for non-existing triplets can be an indicator of a brute force attack
        sleep(5);
    }
}





function base64_encode_url($string) {
    return str_replace(['+','/','='], ['-','_',''], base64_encode($string));
}

function base64_decode_url($string) {
    return base64_decode(str_replace(['-','_'], ['+','/'], $string));
}

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

/*
function isLocalhost () { // DEPRECATED
    $ip = array_key_exists('X-Forwarded-For', $_SERVER) ? $_SERVER['X-Forwarded-For'] : $_SERVER['REMOTE_ADDR'];
    //echo '<pre>';var_dump ($_SERVER);die();
    switch ($ip) {
        case '80.101.238.137':
        case '192.168.178.21':
        case '192.168.178.22':
        case '192.168.178.30':
        case '127.0.0.1':
        case '::1':
            return true;
        default:
            return false;
    }
}*/

function require_return ($file) {
// used by .../domainConfigs/DOMAIN.EXT/mainmenu.php
    ob_start();
    require ($file);
    $r = ob_get_clean();
    return $r;
}

function css_array_to_css($rules, $indent = 0) {
    $css = '';
    $prefix = str_repeat('  ', $indent);

    foreach ($rules as $key => $value) {
        if (is_array($value)) {
            $selector = $key;
            $properties = $value;

            $css .= $prefix . "$selector {\n";
            $css .= $prefix . css_array_to_css($properties, $indent + 1);
            $css .= $prefix . "}\n";
        } else {
            $property = $key;
            $selector2 = '';
            for ($i=0; $i<strlen($property); $i++) {
                $c = substr($property, $i, 1);
                if ($c === strtolower($c)) {
                    $selector2 .= $c;
                } else {
                    $selector2 .= '-'.strtolower($c);
                }
            }
            $property = $selector2;
            $css .= $prefix . "$property: $value;\n";
        }
    }

    return $css;
}

function createDirectoryStructure ($filepath, $ownerUser=null, $ownerGroup=null, $filePerms=null) {
$fncn = "createDirectoryStructure";
$debug = false;
/*	Creates a directory structure. 
    Returns a boolean success value. False usually indicates illegal characters in the directories.

    If you supply a filename as part of $filepath, all directories for that filepath are created.
    If $filepath==only a directory, you TODO**MUST**TODO end $filepath with / or \
*/
    //slash-direction doesn't matter for PHP4 file functions :-), so we even things out first;
    $filepath = strtr (trim($filepath), "\\", "/");
    if ($filepath[strlen($filepath)-1]!="/") $filepath.="/";
    if ($filepath[0]!="/") $filepath="/".$filepath;	
    if ($debug) { echo $fncn.'()'.PHP_EOL; echo '$filepath='; var_dump ($filepath); echo PHP_EOL.PHP_EOL; }

    if (($filepath[1]!=':') && ($filepath[0]!='/')) trigger_error ("$fncn: $filepath is not from the root. results would be unstable. gimme a filepath with / as first character.", E_USER_ERROR);

    $directories = explode ("/", $filepath);
    $i = count($directories)-1;
    $j = $i;
    while ($j > -1) {
        if ($directories[$j]==='') unset ($directories[$j]); 
        $j--;
    };
    $result = true;
    if ($debug && false) { echo '1::$directories='; var_dump ($directories); echo PHP_EOL.PHP_EOL; }

    for ($i = count($directories); $i>0; $i--) {
        $pathToTest = '/'.implode ("/", array_slice($directories,0,$i+1));
        if ($debug) { echo '$pathToTest='; var_dump ($pathToTest); echo PHP_EOL.'file_exists($pathToTest)='; var_dump(file_exists($pathToTest)); echo PHP_EOL.PHP_EOL; }
        if (file_exists($pathToTest)) break;
    }
    
    $dbg = array (
        'ptt' => $pathToTest,
        'i' => $i,
        'dirs' => $directories//,
         //'backtrace' => debug_backtrace()
    );
    
    if ( ($i < count($directories)) ) {
        if ($debug) { var_dump ($dbg); echo PHP_EOL.PHP_EOL; }

        for ($j = $i; $j < (count($directories)); $j++) {
            $pathToCreate = '/'.implode ("/", array_slice($directories,0,$j+1));
            //var_dump ($pathToCreate);
            if (!file_exists($pathToCreate)) {
                $filePerms = 0770; // dirty hack for said.by and the now more secure .../setPermissions.sh
                if ($debug) { echo 'p2='; var_dump ($pathToCreate); }
                $result=mkdir($pathToCreate,!is_null($filePerms)?$filePerms:0777);
                if (is_string($ownerUser)) $x = chown ($pathToCreate, $ownerUser);
                if (is_string($ownerGroup)) $y = chgrp ($pathToCreate, $ownerGroup);
                if (!is_null($filePerms)) $z = chmod ($pathToCreate, $filePerms);
                $dbg = [ '$result' => $result, '$x' => $x, '$y' => $y, '$z' => $z ];
                if ($debug) { echo '$dbg='; var_dump ($dbg); }
                    
            }
        }
    }
    return true;
}


function getLocationbarInfo ($queryString=null) {
    if (
        array_key_exists('url', $_GET)
    ) {
        $queryString = $_GET['url'];
    } else if (
        array_key_exists('apps', $_GET)
    ) {
        $queryString = $_GET['apps'];
    } else if (
        array_key_exists('QUERY_STRING', $_SERVER)
        && $_SERVER['QUERY_STRING']!==''
    ) {
        $queryString = $_SERVER['QUERY_STRING'];
        $queryString = str_replace ('\'url=','', $queryString);
        $queryString = str_replace ('url=','', $queryString);
        $queryString = str_replace ('&uc_subscription=index.php', '', $queryString);
    } else if (!array_key_exists('REDIRECT_QUERY_STRING', $_SERVER)
        || $_SERVER['REDIRECT_QUERY_STRING'] === ''
    ) {
        $dcFilepath = $saSiteHD.'/domainConfigs/'.$_SERVER['HTTP_HOST'].'/';
        
        $appParams = array(
            'saObjectType' => 'saPageContent',
            'saPageDivs' => array (
                'siteContent' => array(
                    'saObjectType' => 'vividDialog',
                    'saVividThemeName' => 'vividTheme__dialog_black_navyBorder_square',
                    'div.id' => 'siteContent',
                    //'require_once' => $saFrameworkHD.'/siteContent/frontpage.siteContent.php',
                    'require_once' => $dcFilepath.'/frontpage.siteContent.php',
                )
            )
        );
        //var_dump ($appParams);
        return $appParams;
    }
    //var_dump ($queryString); die();
    

    $ret = array (
        'saObjectType' => 'saPageContent'
    );
    
    if (array_key_exists('url',$_GET)) {
        $appsM1 = strpos($_GET['url'], 'apps/');
        if ($appsM1!==false) {
            $appsM2 = strpos($_GET['url'], '/', $appsM1 + strlen ('apps/') + 1);
            if ($appsM2!==false) {
                $apps = substr ($_GET['url'], $appsM1, $appsM2-$appsM1);
            } else {
                $apps = substr ($_GET['url'], $appsM1 + strlen('apps/'));
            }
            $ret = array (
                'apps' => json_decode(base64_decode_url($apps), true)
            );
        } else {
            $ret = array (
                'apps' => array (
                    '' => '' // default to domainConfigs/YOURDOMAIN.EXT/frontpage.siteContent.php
                )
            );
        };
    } elseif (array_key_exists('apps',$_GET)) {
        $ret = array (
            'apps' => json_decode(base64_decode_url($_GET['apps']), true)
        );
    } else {
        $ret = array (
            'apps' => array (
                '' => '' // default to domainConfigs/YOURDOMAIN.EXT/frontpage.siteContent.php
            )
        );
    };
    $appName = '';
    //echo '<pre>';var_dump ($ret); die();
    foreach ($ret['apps'] as $appName=>$appSettings) {
        break;
    };
    
    //$dbg = array ( 'appsM1' => $appsM1, 'appsM2' => $appsM2, 'ret' => $ret);
    //echo '<pre>getLocationbarInfo():$dbg:'."\r\n"; var_dump ($dbg); echo '</pre>'; die();
        
    global $naLocationBarInfo;
    $naLocationBarInfo = $ret;
    //$ret = array_merge_recursive ($ret, getAppSettings ($appName)); //getAppSettings is in .../nicerapp/apps/functions.php
    $naLocationBarInfo = $ret;
    //echo '<pre>getLocationbarInfo():$ret:'."\r\n"; var_dump ($ret); echo '</pre>'; die();
    return $ret;
}

function getAppSettings ($appName) {
	//echo '<pre>getAppSettings():$appName:'."\r\n"; var_dump ($appName); echo '</pre>'; die();
	$saFrameworkHD = realpath(dirname(__FILE__).'/..');
	$appSettings = array();
	switch ($appName) {
		case 'jsonViewer':
			$appSettings['saPageDivs'] = array (
				'#siteTools' => array (
					'require_once' => $saFrameworkHD.'/apps/nicerapp/tools/appContent/jsonViewer.siteTools.php'
				),
				'#siteContent' => array (
					'require_once' => $saFrameworkHD.'/apps/nicerapp/tools/appContent/jsonViewer.siteContent.php'
				)
			);
			break;
			
		case 'tarot':
			$appSettings['saPageDivs'] = array (
				'#siteContent' => array (
					'require_once' => $saFrameworkHD.'/apps/nicerapp/cardgame_tarot/appContent/tarotSite/index.php'
				)
			);
			break;
			
		case 'calculator':
			$appSettings['saPageDivs'] = array (
				'#siteContent' => array (
					// DIRECT DIV : 'require_once' => $saFrameworkHD.'/apps/nicerapp/musicPlayer/appContent/musicPlayer/index.php'
					'require_once' => $saFrameworkHD.'/apps/Carbonhoarder/carbonHoarderCalculator/appContent/index.siteContent.php' // iframe
				)
			);
			break;

			
			
		case 'musicPlayer':
			$appSettings['saPageDivs'] = array (
				'#siteContent' => array (
					// DIRECT DIV : 'require_once' => $saFrameworkHD.'/apps/nicerapp/musicPlayer/appContent/musicPlayer/index.php'
					'require_once' => $saFrameworkHD.'/apps/nicerapp/musicPlayer/appContent/index.siteContent.php' // iframe
				)
			);
			break;

			
		case 'news':
			$appSettings['saPageDivs'] = array (
				'#siteContent' => array (
					'require_once' => $saFrameworkHD.'/apps/nicerapp/news/appContent/newsApp/2.0.0/newsApp.siteContent.php'
				)
			);
			break;
			
        case 'text_filesystem':
			$appSettings['saPageDivs'] = array (
				'#siteContent' => array (
					'require_once' => $saFrameworkHD.'/apps/nicerapp/text_filesystem/appContent/text_filesystem.siteContent.php'
				)
			);
			break;
			
        case 'text_couchdb':
			$appSettings['saPageDivs'] = array (
				'#siteContent' => array (
					'require_once' => $saFrameworkHD.'/apps/nicerapp/text_couchdb/appContent/text_couchdb.siteContent.php'
				)
			);
			break;

        case 'photo':
			$appSettings['saPageDivs'] = array (
				'#siteContent' => array (
					'require_once' => $saFrameworkHD.'/userInterface/photoAlbum/3.0.0/photo.siteContent.php'
				)
			);
			break;
			
        case 'photoAlbum_couchdb':
			$appSettings['saPageDivs'] = array (
				'#siteContent' => array (
					'require_once' => $saFrameworkHD.'/apps/nicerapp/photoAlbum_couchdb/appContent/photoAlbum_couchdb.siteContent.php'
				)
			);
			break;
			
        case 'cookies':
			$appSettings['saPageDivs'] = array (
				'#siteContent' => array (
					'require_once' => $saFrameworkHD.'/siteContent/cookies.siteContent.php'
				)
			);
			break;

        case 'googleSearch':
			$appSettings['saPageDivs'] = array (
				'#siteContent' => array (
					'require_once' => $saFrameworkHD.'/apps/nicerapp/googleSearch/appContent/googleSearch.siteContent.php'
				)
			);
			break;
		
		case 'analytics':
			$appSettings['saPageDivs'] = array (
				'#siteContent' => array (
					'require_once' => $saFrameworkHD.'/businessLogic/analytics/analytics.siteContent.php'
				)
			);
			break;
		
			
		case '':
            $dcFilepath = $saSiteHD.'/domainConfigs/'.$_SERVER['HTTP_HOST'].'/';
			$appSettings['saPageDivs'] = array (
				'#siteContent' => array (
					//'require_once' => $saFrameworkHD.'/siteContent/frontpage.siteContent.php'
					'require_once' => $dcFilepath .'frontpage.siteContent.php'
				)
			);
			break;
		default :
            global $naLocationBarInfo;
            echo '<pre>getAppSettings():$appName:'."\r\n"; var_dump ($appName); echo '</pre>'; 
            echo '<pre>getAppSettings():global $naLocationBarInfo:'."\r\n"; var_dump ($naLocationBarInfo); echo '</pre>'; 
            echo '<pre>getAppSettings():$appSetting:'."\r\n"; var_dump ($appSettings); echo '</pre>'; 
            die();
            break;
	}
	
	//echo '<pre>getAppSettings():$appSetting:'."\r\n"; var_dump ($appSettings); echo '</pre>'; die();
	return $appSettings;
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


function walkArray (&$a, $keyCallback=null, $valueCallback=null, $callKeyForValues=false, $callbackParams=null, $k='', $level=0, $path='') {
// usage : walkArray ($someRecursiveArray, 'walkArray_printKey', 'walkArray_printValue');
// can handle recursive arrays. a nested array is a recursive array.
// is faster, especially on large arrays, than RecuriveArrayIterator, see speed testing comment at http://php.net/manual/en/class.recursiveiteratoriterator.php
// provides detailed information to callbacks on where in the data we are, something that array_walk_recursive just doesnt do.
// passes data around as pointers, not copies of data.
    if (!is_array($a)) {
        return badResult (E_USER_ERROR, array (
            'msg' => 'walkArray() was called but $a parameter passed is not an array.'
        ));
    } else {
        foreach ($a as $k=>&$v) {
            $cd = array ( // callback data
                'type' => 'key',
                'path' => $path,
                'level' => $level,
                'k' => &$k,
                'v' => &$v,
                'params' => &$callbackParams
            );                
            if (!is_null($keyCallback) && ($callKeyForValues || is_array($v))) call_user_func ($keyCallback, $cd);
            if (is_array ($v)) {
                walkArray ($a[$k], $keyCallback, $valueCallback, $callKeyForValues, $callbackParams, $k, $level+1, $path.'/'.$k);
            } else {
                $cd['type'] = 'value';
                if (!is_null($valueCallback)) call_user_func ($valueCallback, $cd);
            }
        }
    }
    $r = true;
    return goodResult($r);
}

function walkArray_printKey ($cd) {
    echo '<div style="background:blue;color:yellow;border-radius:5px;padding:2px;margin-top:5px;">'.PHP_EOL;
    $indent = 20 * $cd['level'];
    echo '<div style="padding-left:'.$indent.'px">'.PHP_EOL;
    echo 'key : '.$cd['k'].'<br/>'.PHP_EOL;
    echo 'path : '.$cd['path'].'<br/>'.PHP_EOL;
    echo '</div>'.PHP_EOL;
    echo '</div>'.PHP_EOL;
}

function walkArray_printValue ($cd) {
    echo '<pre style="background:green;color:white;border-radius:5px;padding:2px;margin-top:2px;">'.PHP_EOL;
    $indent = 20 * $cd['level'];
    echo '<div style="padding-left:'.$indent.'px">'.PHP_EOL;
    echo 'key : '.$cd['k'].'<br/>'.PHP_EOL;
    echo 'path : '.$cd['path'].'<br/>'.PHP_EOL;
    echo 'value : '.$cd['v'].'<br/>'.PHP_EOL;
    echo '</div>'.PHP_EOL;
    echo '</pre>'.PHP_EOL;
}


function &chaseToPath (&$wm, $path, $create=false) {
    //var_dump ($create); die();
    //echo '$wm=<pre>'; var_dump ($wm);echo '</pre>'; //die();
    //$path = str_replace ('/', '/d/', $path);
    //$path .= '/d';
    $nodes = explode ('/', $path);
    foreach ($nodes as $idx=>$node) {
        if (is_numeric($node) && is_string($node)) {
            if (strpos($node,'.')===false) {
                $nodes[$idx] = (int)$node;
            } else {
                $nodes[$idx] = (float)$node;
            }
        }
    }
    $chase = &chase ($wm, $nodes, $create);
    
    //echo '$wm=<pre>'; var_dump ($wm);echo '</pre>'; die();
    /*
    $dbg = array (
        '$path' => $path,
        '$nodes' => $nodes,
        '$wm' => $wm,
        '$chase' => $chase
    );
    echo '$dbg=<pre style="background:red;color:yellow;">'; var_dump ($dbg); echo '</pre>';
    */
    //die();
    
    
    $false = false;
    if (good($chase)) {
        $arr = &result($chase);	
        return $arr;
    } else return $false;
}		


function &chase (&$arr, $indexes, $create=false) {
        if (false) {
        echo 'sitewide/functions.php --- $arr=<pre>'; var_dump ($arr); echo '</pre>';
        echo 'sitewide/functions.php --- $indexes=<pre>'; var_dump ($indexes); echo '</pre>';
        echo 'sitewide/functions.php --- $create=<pre>'; var_dump ($create); echo '</pre>';
        }
	$r = &$arr;
	foreach ($indexes as $idx) {
            //echo 'sitewide/functions.php --- $idx=<pre>'; var_dump ($idx); var_dump (array_key_exists($idx,$r)); var_dump ($r); echo '</pre>';
            if (
                    is_array($r)
                    && (
                            $create===true 
                            || array_key_exists($idx,$r)
                    )
            ) {
                    if ($create===true && !array_key_exists($idx,$r)) $r[$idx]=array();
                    //echo 'sitewide/functions.php --- $idx=<pre>'; var_dump ($idx); echo '</pre>';
                    $r = &$r[$idx];
            } else {
                /*
                    $err = array(
                    'msg' => 'Could not walk the full tree',
                    'vars' => array(
                            '$idx--error'=>$idx,
                            '$indexes'=>$indexes,
                            '$arr'=>$arr
                            )
                    );
                    badResult (E_USER_NOTICE, $err);
                    */
                    $ret = false; // BUG #2 squashed
                    return $ret;
            }
	}
    
        //echo 'sitewide/functions.php --- $r=<pre>'; var_dump ($r); echo '</pre>';
	return goodResult($r);
}

function &chaseToReference (&$array, $path) {
	if (!empty($path)) {
		if (empty($array[$path[0]])) {
			$err = array(
				'msg' => 'Could not walk the full tree',
				'$path' => $path,
				'$array (possibly partially walked)' => $array
			);
			return badResult (E_USER_NOTICE, $err);
		} else return chaseToReference($array[$path[0]], array_slice($path, 1));
	} else {
		return goodResult($array);
	}	
}

function good($r) {
	return (
		is_array($r)
		&& array_key_exists('result',$r)
	);
}

function &result(&$r) {
	return $r['result'];
}

function &resultArray (&$r) {
  $r2 = array();
  foreach ($r as $k => $v) {
    $r2[$k] = result($v);
  }
  return $r2;
}


function &goodResult(&$r) {
	$r2 = array (
		'isMetaForFunc' => true,
		'result' => &$r
	);
	return $r2;
}


?>
