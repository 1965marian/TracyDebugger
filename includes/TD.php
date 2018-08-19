<?php

use Tracy\Debugger;
use Tracy\Dumper;

class TD extends TracyDebugger {

	/**
	 * These are here so that they are available even when user is not allowed or module not enabled so we
	 * don't get a undefined function error when calling these from a template file.
	 */

    protected static function tracyUnavailable() {
        if(!\TracyDebugger::getDataValue('enabled') || !\TracyDebugger::allowedTracyUsers() || !class_exists('\Tracy\Debugger')) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Tracy\Debugger::debugAll() shortcut.
     * @tracySkipLocation
     */
    public static function debugAll($var, $title = NULL, array $options = NULL) {
        if(self::tracyUnavailable()) return false;
        static::barDump($var, $title, $options);
        static::dump($var, $title, $options);
        static::fireLog($var);
        static::log($var);
    }

    /**
     * Tracy\Debugger::barDump() shortcut.
     * @tracySkipLocation
     */
    public static function barDump($var, $title = NULL, array $options = NULL) {
        if(self::tracyUnavailable()) return false;
        if(is_array($title)) {
            $options = $title;
            $title = NULL;
        }
        if(isset($options) && is_array($options) && !static::has_string_keys($options)) {
            $options['maxDepth'] = $options[0];
            if(isset($options[1])) $options['maxLength'] = $options[1];
        }
        $options[Dumper::DEPTH] = isset($options['maxDepth']) ? $options['maxDepth'] : \TracyDebugger::getDataValue('maxDepth');
        $options[Dumper::TRUNCATE] = isset($options['maxLength']) ? $options['maxLength'] : \TracyDebugger::getDataValue('maxLength');
        $options[Dumper::LOCATION] = Debugger::$showLocation;
        static::dumpToBar($var, $title, $options);
    }

    /**
     * Tracy\Debugger::barDumpLive() shortcut with live dumping.
     * @tracySkipLocation
     */
    public static function barDumpLive($var, $title = NULL) {
        if(self::tracyUnavailable()) return false;
        $options[Dumper::DEPTH] = 99;
        $options[Dumper::TRUNCATE] = 999999;
        $options[Dumper::LOCATION] = Debugger::$showLocation;
        $options[Dumper::LIVE] = true;
        static::dumpToBar($var, $title, $options);
    }

    /**
     * Tracy\Debugger::barDumpBig() shortcut dumping with maxDepth = 6 and maxLength = 9999.
     * @tracySkipLocation
     */
    public static function barDumpBig($var, $title = NULL, array $options = NULL) {
        if(self::tracyUnavailable()) return false;
        if(is_array($title)) {
            $options = $title;
            $title = NULL;
        }
        if(isset($options) && is_array($options) && !static::has_string_keys($options)) {
            $options['maxDepth'] = $options[0];
            if(isset($options[1])) $options['maxLength'] = $options[1];
        }
        $options[Dumper::DEPTH] = 6;
        $options[Dumper::TRUNCATE] = 9999;
        $options[Dumper::LOCATION] = Debugger::$showLocation;
        static::dumpToBar($var, $title, $options);
    }

    /**
     * Send content to dump bar
     * @tracySkipLocation
     */
    private static function dumpToBar($var, $title = NULL, array $options = NULL) {
        $dumpItem = array();
        $dumpItem['title'] = $title;
        $dumpItem['dump'] = static::generateDualDump($var, $options);
        array_push(\TracyDebugger::$dumpItems, $dumpItem);

        if(isset(\TracyDebugger::$showPanels) && in_array('dumpsRecorder', \TracyDebugger::$showPanels)) {
            $dumpsRecorderItems = wire('session')->tracyDumpsRecorderItems ?: array();
            array_push($dumpsRecorderItems, $dumpItem);
            wire('session')->tracyDumpsRecorderItems = $dumpsRecorderItems;
        }
    }

    /**
     * Generate debugInfo and Full Object tabbed output
     * @tracySkipLocation
     */
    private static function generateDualDump($var, $options) {
        $out = '';
        if(method_exists($var, '__debugInfo')) {
            $classExt = rand();
            $out .= '
            <ul class="dumpTabs">
                <li id="debugInfoTab_'.$classExt.'" class="active"><a href="javascript:void(0)" onclick="toggleDumpType(\'debugInfo\', '.$classExt.')">Debug Info</a></li>
                <li id="fullObjectTab_'.$classExt.'"><a href="javascript:void(0)" onclick="toggleDumpType(\'fullObject\', '.$classExt.')">Full Object</a></li>
            </ul>';
            if(($var instanceof Page || $var instanceof \ProcessWire\Page) && $var->id) {
                $out .= '<span style="float:right"><a href="'.wire('config')->urls->admin.'page/edit/?id=' . $var->id . '">#'.$var->id.'</a></span>';
            }
            $out .= '
            <div style="clear:both">';
                $options[Dumper::DEBUGINFO] = true;
                $out .= '<div id="debugInfo_'.$classExt.'">' . Dumper::toHtml($var, $options) . '</div>';
                $options[Dumper::DEBUGINFO] = false;
                $out .= '<div id="fullObject_'.$classExt.'" style="display:none">' . Dumper::toHtml($var, $options) . '</div>
            </div>';
        }
        else {
            $out .= Dumper::toHtml($var, $options);
        }
        return $out;
    }


    /**
     * Tracy\Debugger::dump() shortcut.
     * @tracySkipLocation
     */
    public static function dump($var, $title = NULL, array $options = NULL, $return = FALSE) {
        if(self::tracyUnavailable()) return false;
        if(is_array($title)) {
            $options = $title;
            $title = NULL;
        }
        if(isset($options) && is_array($options) && !static::has_string_keys($options)) {
            $options['maxDepth'] = $options[0];
            if(isset($options[1])) $options['maxLength'] = $options[1];
        }
        $options[Dumper::DEPTH] = isset($options['maxDepth']) ? $options['maxDepth'] : \TracyDebugger::getDataValue('maxDepth');
        $options[Dumper::TRUNCATE] = isset($options['maxLength']) ? $options['maxLength'] : \TracyDebugger::getDataValue('maxLength');
        $options[Dumper::LOCATION] = \TracyDebugger::$fromConsole ? false : Debugger::$showLocation;
        if($title) echo '<h2>'.$title.'</h2>';
        echo static::generateDualDump($var, $options);
    }

    /**
     * Tracy\Debugger::dumpBig() shortcut dumping with maxDepth = 6 and maxLength = 9999.
     * @tracySkipLocation
     */
    public static function dumpBig($var, $title = NULL, array $options = NULL, $return = FALSE) {
        if(self::tracyUnavailable()) return false;
        if(is_array($title)) {
            $options = $title;
            $title = NULL;
        }
        if(isset($options) && is_array($options) && !static::has_string_keys($options)) {
            $options['maxDepth'] = $options[0];
            if(isset($options[1])) $options['maxLength'] = $options[1];
        }
        $options[Dumper::DEPTH] = 6;
        $options[Dumper::TRUNCATE] = 9999;
        $options[Dumper::LOCATION] = \TracyDebugger::$fromConsole ? false : Debugger::$showLocation;
        if($title) echo '<h2>'.$title.'</h2>';
        echo static::generateDualDump($var, $options);
    }

    /**
     * Tracy\Debugger::log() shortcut.
     * @tracySkipLocation
     */
    public static function log($message, $priority = Debugger::INFO) {
        if(self::tracyUnavailable()) return false;
        return Debugger::log($message, $priority);
    }

    /**
     * Tracy\Debugger::timer() shortcut.
     * @tracySkipLocation
     */
    public static function timer($name = NULL) {
        if(self::tracyUnavailable()) return false;
        $roundedTime = round(Debugger::timer($name),4);
        if($name) {
            return $name.' : '.$roundedTime;
        }
        else{
            return $roundedTime;
        }
    }

    /**
     * Tracy\Debugger::fireLog() shortcut.
     * @tracySkipLocation
     */
    public static function fireLog($message = NULL) {
        if(self::tracyUnavailable()) return false;
        return Debugger::fireLog($message);
    }

    /**
     * Zarganwar\PerformancePanel\Register::add() shortcut.
     * @tracySkipLocation
     */
    public static function addBreakpoint($name = null, $enforceParent = null) {
        if(self::tracyUnavailable() || !class_exists('\Zarganwar\PerformancePanel\Register')) return false;
        return Zarganwar\PerformancePanel\Register::add($name, $enforceParent);
    }

    /**
     * Template vars shortcut.
     * @tracySkipLocation
     */
    public static function templateVars($vars) {
        if(self::tracyUnavailable()) return false;
        return \TracyDebugger::templateVars((array) $vars);
    }

    private static function has_string_keys(array $array) {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

}