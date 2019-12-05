<?php
if (!defined('DUPLICATOR_PRO_VERSION'))
    exit; // Exit if accessed directly

/**
 * Helper Class for logging
 * @package Dupicator\classes
 */
class DUP_PRO_Log
{
    /**
     * The file handle used to write to the log file
     * @var file resource 
     */
    private static $logFileHandle;

    /**
     *  Open a log file connection for writing
     *  @param string $name Name of the log file to create
     */
    static public function Open($name_hash)
    {
        if (!isset($name_hash))
            throw new Exception("A name value is required to open a file log.");
        self::$logFileHandle = @fopen(DUPLICATOR_PRO_SSDIR_PATH . "/{$name_hash}.log", "a+");
    }

    /**
     *  Close the log file connection
     */
    static public function Close()
    {
        @fclose(self::$logFileHandle);
    }

    /**
     *  General information logging
     *  @param string $msg	The message to log
     * 
     *  REPLACE TO DEBUG: Memory consuption as script runs	
     * 	$results = DUP_PRO_Util::ByteSize(memory_get_peak_usage(true)) . "\t" . $msg;
     * 	@fwrite(self::$logFileHandle, "{$results} \n"); 
     */
    static public function Info($msg)
    {
        @fwrite(self::$logFileHandle, "{$msg} \n");
        //$results = DUP_PRO_Util::ByteSize(memory_get_usage(true)) . "\t" . $msg;
        //@fwrite(self::$logFileHandle, "{$results} \n"); 
    }

    /**
     *  Called when an error is detected and no further processing should occur
     *  @param string $msg The message to log
     *  @param string $details Additional details to help resolve the issue if possible
     */
    static public function Error($msg, $detail = '', $die = true)
    {

        DUP_PRO_U::log("Forced Error Generated: " . $msg);
        $source = self::getStack(debug_backtrace());

        $err_msg = "\n====================================================================\n";
        $err_msg .= "!RUNTIME ERROR!\n";
        $err_msg .= "---------------------------------------------------------------------\n";
        $err_msg .= "MESSAGE:\n{$msg}\n";
        if (strlen($detail))
        {
            $err_msg .= "DETAILS:\n{$detail}\n";
        }
        $err_msg .= "---------------------------------------------------------------------\n";
        $err_msg .= "TRACE:\n{$source}";
        $err_msg .= "====================================================================\n\n";
        @fwrite(self::$logFileHandle, "\n{$err_msg}");

        if ($die)
        {	
			//Output to browser
			$browser_msg  = "RUNTIME ERROR:<br/>An error has occured. Please try again!<br/>";
			$browser_msg .= "See the duplicator log file for full details: Duplicator Pro &gt; Tools &gt; Logging<br/><br/>";
			$browser_msg .= "MESSAGE:<br/> {$msg} <br/><br/>";
			if (strlen($detail))
			{
				$browser_msg .= "DETAILS: {$detail} <br/>";
			}
            die($browser_msg);
        }
    }

    /**
     * The current strack trace of a PHP call
     * @param $stacktrace The current debug stack
     * @return string 
     */
    public static function getStack($stacktrace)
    {
        $output = "";
        $i = 1;
        
        foreach ($stacktrace as $node)
        {
            $file_output = isset($node['file']) ? basename($node['file']) : '';
            $function_output = isset($node['function']) ? basename($node['function']) : '';
            $line_output = isset($node['line']) ? basename($node['line']) : '';

            $output .= "$i. " . $file_output . " : " . $function_output . " (" . $line_output . ")\n";
            $i++;
        }
        
        return $output;
    }

}

?>