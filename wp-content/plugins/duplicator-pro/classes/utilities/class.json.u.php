<?php
if (!class_exists('DUP_PRO_JSON_U'))
{
	class DUP_PRO_JSON_U
	{
		protected static $_messages = array(
			JSON_ERROR_NONE => 'No error has occurred',
			JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
			JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
			JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
			JSON_ERROR_SYNTAX => 'Syntax error',
			JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
		);

		private static function utf8ize($val)
		{
			if (is_array($val))
			{
				foreach ($val as $k => $v)
				{
					$val[$k] = self::utf8ize($v);
				}
			}
			else if (is_object($val))
			{
				foreach ($val as $k => $v)
				{
					$val->$k = self::utf8ize($v);
				}
			}
			else
			{
				if (mb_detect_encoding($val, 'UTF-8', true))
				{
					return $val;
				}
				else
				{
					return utf8_encode($val);
				}
			}

			return $val;
		}

		static function escape_string($str)
		{
			return addcslashes($str, "\v\t\n\r\f\"\\/");
		}

		static function custom_encode($in)
		{
			$out = "";
			
			if (is_object($in))
			{
				$class_vars = get_object_vars(($in));
				$arr = array();
				
				foreach ($class_vars as $key => $val)
				{
					$arr[$key] = "\"" . self::escape_string($key) . "\":\"{$val}\"";
				}
				
				$val = implode(',', $arr);
				$out .= "{{$val}}";
			}
			elseif (is_array($in))
			{
				$obj = false;
				$arr = array();
				
				foreach ($in AS $key => $val)
				{
					if (!is_numeric($key))
					{
						$obj = true;
					}
					$arr[$key] = self::custom_encode($val);
				}
				
				if ($obj)
				{
					foreach ($arr AS $key => $val)
					{
						$arr[$key] = "\"" . self::escape_string($key) . "\":{$val}";
					}
					$val = implode(',', $arr);
					$out .= "{{$val}}";
				}
				else
				{
					$val = implode(',', $arr);
					$out .= "[{$val}]";
				}
			}
			elseif (is_bool($in))
			{
				$out .= $in ? 'true' : 'false';
			}
			elseif (is_null($in))
			{
				$out .= 'null';
			}
			elseif (is_string($in))
			{
				$out .= "\"" . self::escape_string($in) . "\"";
			}
			else
			{
				$out .= $in;
			}
			
			return "{$out}";
		}

		public static function encode($value, $options = 0)
		{

			// RSR TODO: the 5.2 windows box is returning null json_encode - theoretically its a utf8 issue but the utf8ize isnt resolving the problem...
			//	$value = self::utf8ize($value);

			$result = json_encode($value, $options);

			if ($result !== FALSE)
			{

				return $result;
			}

			if (function_exists('json_last_error'))
			{
				$message = self::$_messages[json_last_error()];
			}
			else
			{
				$message = DUP_PRO_U::__('One or more filenames isn\'t compatible with JSON encoding');
			}

			throw new RuntimeException($message);
		}

		public static function decode($json, $assoc = false)
		{
			$result = json_decode($json, $assoc);

			if ($result)
			{
				return $result;
			}

			throw new RuntimeException(self::$_messages[json_last_error()]);
		}

	}

}
?>
