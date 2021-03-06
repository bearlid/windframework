<?php
/**
 * 基础帮助类,提供框架错误处理基础操作
 * 
 * @author Qiong Wu <papa0924@gmail.com>
 * @copyright ©2003-2103 phpwind.com
 * @license http://www.windframework.com
 * @version $Id$
 * @package base
 */
class WindHelper {
	const INTERNAL_LOCATION = "~Internal Location~";
	/**
	 * 默认错误视图位置
	 * 
	 * 默认错误视图位置,可以通过应用配置进行重定义<code>
	 * <errorpage>WIND:web.view</errorpage></code>
	 * @var string
	 */
	protected static $errorDir = 'WIND:web.view';

	/**
	 * 错误处理句柄
	 * 
	 * @param string $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param string $errline
	 * @return void
	 */
	public static function errorHandle($errno, $errstr, $errfile, $errline) {
		if ($errno & error_reporting()) {
			restore_error_handler();
			restore_exception_handler();
			$trace = debug_backtrace();
			unset($trace[0]["function"], $trace[0]["args"]);
			self::crash(self::getErrorName($errno) . ':' . $errstr, $errfile, $errline, $trace);
		}
	}

	/**
	 * 以静态错误页面终结请求
	 *
	 * @param string $message
	 * @param string $template
	 * @param WindHttpResponse $response
	 * @param int $status
	 */
	public static function triggerError($message, $template) {
		$message = substr($message, 0, 8000);
		if ($template) {
			$file = Wind::getRealPath($template, true);
			ob_start();
			include $file;
			$message = ob_get_clean();
		}
		die($message);
	}

	/**
	 * 异常处理句柄
	 * 
	 * @param Exception $exception
	 * @return void
	 */
	public static function exceptionHandle($exception) {
		restore_error_handler();
		restore_exception_handler();
		$trace = $exception->getTrace();
		if (@$trace[0]['file'] == '') {
			unset($trace[0]);
			$trace = array_values($trace);
		}
		$file = @$trace[0]['file'];
		$line = @$trace[0]['line'];
		self::crash($exception->getMessage(), $file, $line, $trace, $exception->getCode());
	}

	/**
	 * 错误信息处理方法
	 * 
	 * @param string $message
	 * @param string $file
	 * @param string $line
	 * @param array $trace
	 */
	protected static function crash($message, $file, $line, $trace, $status = 0) {
		$errmessage = substr($message, 0, 8000);
		$_headers = Wind::getApp()->getResponse()->getHeaders();
		$_errhtml = false;
		foreach ($_headers as $_header) {
			if (strtolower($_header['name']) == strtolower('Content-type')) {
				$_errhtml = strpos(strtolower($_header['value']), strtolower('text/html')) !== false;
				break;
			}
		}
		$msg = '';
		if (WIND_DEBUG) {
			$_errorPage = 'error.htm';
			$count = count($trace);
			$padLen = strlen($count);
			foreach ($trace as $key => $call) {
				if (!isset($call['file']) || $call['file'] == '') {
					$call['file'] = self::INTERNAL_LOCATION;
					$call['line'] = 'N/A';
				}
				$traceLine = '#' . str_pad(($count - $key), $padLen, "0", STR_PAD_LEFT) . '  ' . self::getCallLine(
					$call);
				$trace[$key] = $traceLine;
			}
			$fileLines = array();
			if (is_file($file)) {
				$currentLine = $line - 1;
				$fileLines = explode("\n", file_get_contents($file, null, null, 0, 10000000));
				$topLine = $currentLine - 5;
				$fileLines = array_slice($fileLines, $topLine > 0 ? $topLine : 0, 10, true);
				if (($count = count($fileLines)) > 0) {
					$padLen = strlen($count);
					foreach ($fileLines as $line => &$fileLine)
						$fileLine = " " . htmlspecialchars(
							str_pad($line + 1, $padLen, "0", STR_PAD_LEFT) . ": " . str_replace("\t", "    ", 
								rtrim($fileLine)), null, "UTF-8");
				}
			}
			$msg .= "$file\n" . implode("\n", $fileLines) . "\n" . implode("\n", $trace);
		} else
			$_errorPage = '404.htm';
		if ($status >= 400 && $status <= 505) {
			$_statusMsg = ucwords(Wind::getApp()->getResponse()->codeMap($status));
			$topic = "$status - " . $_statusMsg . "\n";
		} else
			$topic = "Wind Framework - Error Caught";
		$msg = "$topic\n$errmessage\n" . $msg;
		if (WIND_DEBUG & 2) {
			Wind::getApp()->getComponent('windLogger')->error($msg, 'wind.error', 'core.error', true);
		}
		if ($_errhtml) {
			ob_start();
			$errDir = Wind::getApp()->getConfig('errorpage');
			$errDir || $errDir = self::$errorDir;
			if (isset($_statusMsg)) {
				header('HTTP/1.x ' . $status . ' ' . $_statusMsg);
				header('Status: ' . $status . ' ' . $_statusMsg);
				is_file(Wind::getRealPath($errDir) . '.' . $status . '.htm') && $_errorPage = $status . '.htm';
			}
			require Wind::getRealPath(($errDir ? $errDir : self::$errorDir) . '.' . $_errorPage, true);
			$msg = ob_get_clean();
		}
		$msg = str_replace(Wind::getRootPath(Wind::getAppName()), '~/', $msg);
		die($msg);
	}

	/**
	 * @param array $call
	 * @return string
	 */
	private static function getCallLine($call) {
		$call_signature = "";
		if (isset($call['file'])) $call_signature .= $call['file'] . " ";
		if (isset($call['line'])) $call_signature .= "(" . $call['line'] . ") ";
		if (isset($call['function'])) {
			$call_signature .= $call['function'] . "(";
			if (isset($call['args'])) {
				foreach ($call['args'] as $arg) {
					if (is_string($arg))
						$arg = '"' . (strlen($arg) <= 64 ? $arg : substr($arg, 0, 64) . "…") . '"';
					else if (is_object($arg))
						$arg = "[Instance of '" . get_class($arg) . "']";
					else if ($arg === true)
						$arg = "true";
					else if ($arg === false)
						$arg = "false";
					else if ($arg === null)
						$arg = "null";
					else
						$arg = strval($arg);
					$call_signature .= $arg . ',';
				}
			}
			$call_signature = trim($call_signature, ',') . ")";
		}
		return $call_signature;
	}

	/**
	 * 错误代码和错误信息映射方法,接受一个错误代码返回相对应错误代码的错误名称
	 * 
	 * @param int $errorNumber 错误代码 必填
	 * @return string
	 */
	protected static function getErrorName($errorNumber) {
		$errorMap = array(
			E_ERROR => "E_ERROR", 
			E_WARNING => "E_WARNING", 
			E_PARSE => "E_PARSE", 
			E_NOTICE => "E_NOTICE ", 
			E_CORE_ERROR => "E_CORE_ERROR", 
			E_CORE_WARNING => "E_CORE_WARNING", 
			E_COMPILE_ERROR => "E_COMPILE_ERROR", 
			E_COMPILE_WARNING => "E_COMPILE_WARNING", 
			E_USER_ERROR => "E_USER_ERROR", 
			E_USER_WARNING => "E_USER_WARNING", 
			E_USER_NOTICE => "E_USER_NOTICE", 
			E_STRICT => "E_STRICT", 
			E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR", 
			E_ALL => "E_ALL");
		return isset($errorMap[$errorNumber]) ? $errorMap[$errorNumber] : 'E_UNKNOWN';
	}
}
?>