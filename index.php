<?php
require_once (dirname(__FILE__).'/functions.php');

    global $vui_version;
    $vui_version = '4.1.0';
    global $vui_basePath;
    $vui_basePath = realpath(dirname(__FILE__));
    
        $cssLinks = getLinks ( [
            [ 'files' => getVividButtonCSSfiles(), 'type' => 'css' ]
        ] );
        $javascriptLinks = getLinks ( [
            [ 'files' => getVividButtonJavascriptFiles(), 'type' => 'javascript' ]
        ] );
        
        $siteMenu = getSiteMenu();
        
        
    function getLinks ($files) {
        global $vui_version;
        global $vui_basePath;
        
        $lines = '';
        foreach ($files as $idx => $fileRec) {
            if (array_key_exists('indexFile', $fileRec)) {
                $indexFilepath = $fileRec['indexFile'];
                $filesRaw = file_get_contents($indexFilepath);
                $files = json_decode ($filesRaw);
                checkForJSONerrors ($filesRaw, $indexFilepath, '"null"');
            } else if (array_key_exists('files', $fileRec)) {
                $files = $fileRec['files'];
            }
            $indexType = $fileRec['type'];
            
            switch ($indexType) {
                case 'css': $lineSrc = "\t".'<link type="text/css" rel="StyleSheet" href="{$src}?c={$changed}">'."\r\n"; break;
                case 'javascript': $lineSrc = "\t".'<script type="text/javascript" src="{$src}?c={$changed}"></script>'."\r\n"; break;
            };
            
            foreach ($files as $idx => $file) {
                if (file_exists($vui_basePath.'/'.$file)) {
                    $url = str_replace ($vui_basePath.'/','',$file);
                    $search = array ('{$src}', '{$changed}');
                    $replace = array ($url, date('Ymd_His', filemtime($vui_basePath.'/'.$file)));
                    $lines .= str_replace ($search, $replace, $lineSrc);
                } else {
                    trigger_error ('file "'.$vui_basePath.'/'.$file.'" is missing, referenced from <span class="naCMS_getLinksFileRec">'.json_encode($fileRec).'</span>.', E_USER_ERROR);
                }
            }
        }
        return $lines;
    }

    function getVividButtonCSSfiles () {
        global $vui_version;
        global $vui_basePath;
        
        $basePath = realpath(dirname(__FILE__));
        $files = getFilePathList ($basePath, true, '/btn_.*\.css/', array('file'), 1);
        foreach ($files as $idx => $file) {
            $files[$idx] = str_replace($basePath.'/', '', $file);
        }
        sort($files);
        return array_merge ([ 'vividButton-'.$vui_version.'/themes.css' ], $files);
    }
    
    function getVividButtonJavascriptFiles () {
        global $vui_version;
        global $vui_basePath;
        
        $basePath = realpath(dirname(__FILE__)).'/vividButton-'.$vui_version;
        $files = getFilePathList ($basePath, true, '/btn_.*\.source\.js/', array('file'), 1);
        //echo '<pre>';var_dump ($files); die();
        foreach ($files as $idx => $file) {
            $files[$idx] = str_replace(realpath(dirname(__FILE__)).'/', '', $file);
        }
        sort ($files);
        //echo '<pre>';var_dump ($files); die();
        return $files;
    }
    
    function getSiteMenu() {
        $contentFile = realpath(dirname(__FILE__).'/menu/mainmenu.php');
        $content = execPHP($contentFile);
        return $content;
    }
    
?>
<html>
<head>
    <title>vividUI HTML5 + CSS + Javascript (jQuery-based) + SVG + WebGL user interface components</title>
    <?php echo $cssLinks;?>
    <link type="text/css" rel="StyleSheet" href="naVividUI.css">
    <!--<script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="vividButton-3.0.0.source.js" type="text/javascript"></script>
    <script src="vividButton-4.1.0.source.js" type="text/javascript"></script>
    <script src="vividMenu.source.js" type="text/javascript"></script>
    <script src="naMisc.source.js" type="text/javascript"></script>
    <script src="naColorGradients-1.1.0.source.js" type="text/javascript"></script>
    <?php echo $javascriptLinks;?>
</head>
<body style="background:url('body_background.jpg') repeat;">
    <h1>vividUI HTML5 + CSS + Javascript (jQuery-based) + SVG + WebGL user interface components</h1>
    <p class="copyright">
    Copyright (c) 2002-2021 by Rene A.J.M. Veerman <a href="mailto:rene.veerman.netherlands@gmail.com">rene.veerman.netherlands@gmail.com</a>.<br/>
    LICENSE : <a href="https://opensource.org/licenses/MIT" target="naMIT">MIT</a>.
    </p>
    
    <div class="audioPlayerButtons" style="position:relative">
        <div id="btnPlayPause" class="vividButton4" buttonType="btn_audioVideo_playPause" onclick="mp3site.playpause()"></div>
        <div id="btnMuteUnmute" class="vividButton4" buttonType="btn_audioVideo_muteUnmute" onclick="mp3site.mute()"></div>
        <div id="btnShuffle" class="vividButton4" buttonType="btn_audioVideo_shuffle" onclick="mp3site.toggleShuffle()"></div>
        <div id="btnRepeat" class="vividButton4" buttonType="btn_audioVideo_repeat" onclick="mp3site.toggleRepeat()"></div>
    </div>

    <div id="spacer" style="position:relative;height:100px;">&nbsp;</div>
    
    <div id="siteMenu" class="vividMenu" theme="dark" style="position:relative">
        <?php echo $siteMenu;?>
    </div>
    
    <div id="spacer" style="position:relative;height:100px;">&nbsp;</div>

    <div id="siteMenu_vertical" class="vividMenu" type="verticalMenu" theme="dark" style="position:relative">
        <?php echo $siteMenu; ?>
    </div>
    
    <script type="text/javascript">
        $('.vividButton4').each(function(idx,el) {
            var btn = na.ui.vividButton.init (el.id);
        });
        
        $('.vividMenu').each(function(idx,el) {
            var 
            callback = function (menu) {
                console.log ('MENU FULLY INITIALIZED : #'+menu.el.id);
            },
            menu = new naVividMenu (el, callback);
        });
    </script>
</body>
</html>
