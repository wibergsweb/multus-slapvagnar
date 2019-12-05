<?php
if (!class_exists('DUP_PRO_SHELL_U'))
{
	class DUP_PRO_SHELL_U 
	{
		public static function execute_and_get_value($command, $index)
        {
            $command = "$command | awk '{print $$index }'";
            
            $ret_val = shell_exec($command);                        
            
            return trim($ret_val);
        }
	}
}
?>
