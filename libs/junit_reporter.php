<?php
require_once CAKE_TESTS_LIB . 'reporter' . DS . 'cake_cli_reporter.php';

class JunitReporter extends CakeCliReporter {
	protected $_output;
	protected $_testCaseFilename = '';
	protected $_testCaseName = '';
	protected $_testCases = array();
	protected $_methodTimeStart = 0;
	protected $_methodTimeEnd = 0;
	protected $_methodAssertions = 0;
	protected $_methodFailures = array();

	function paintGroupStart($testName, $size) {
		parent::paintGroupStart($testName, $size);

		if ($this->_output) {
			fclose($this->_output);
		}

		if (!file_exists($testName)) {
			return;
		}

		$this->_testCaseFilename = $testName;
		$this->_testCases = array();

		$outputDir = ROOT . DS . 'build' . DS . 'junit' . DS;
		$testName = str_replace(DS . DS, DS, $testName);

		$prefix = '';
		if (isset($_ENV['JUNIT_PREFIX'])) {
			$prefix = $_ENV['JUNIT_PREFIX'];
		}
		$filename = $outputDir . $prefix . str_replace(array(APP, '.test.php'), '', $testName) . '.xml';

		@mkdir(dirname($filename), 0777, true);
		$this->_output = fopen($filename, 'w');
		$this->_out('<?xml version="1.0" encoding="UTF-8"?>');
	}

	function paintGroupEnd($testName) {
		parent::paintGroupEnd($testName);

		if (!file_exists($testName)) {
			return;
		}

		$this->_out('<testsuite name="' . $this->_testCaseName . '" tests="' . count($this->_testCases) . '" assertions="' . $this->getPassCount() . '" failures="' . $this->getFailCount() . '" errors="' . $this->getExceptionCount() . '" time="' . $this->_timeDuration . '" file="' . $this->_testCaseFilename . '">');
			$this->_out(implode('', $this->_testCases));
		$this->_out('</testsuite>');
	}

	function paintMethodStart($method) {
		$this->_methodTimeStart = $this->_getTime();
		$this->_methodAssertions = 0;
		$this->_methodFailures = array();
		parent::paintMethodStart($method);
	}

	function paintMethodEnd($method) {
		$this->_methodTimeEnd = $this->_getTime();
		$timeDuration = $this->_methodTimeEnd - $this->_methodTimeStart;

		if (substr($method, 0, 4) == 'test') {
			$testCase = '<testcase name="' . $method . '" class="' . $this->_testCaseName . '" file="' . $this->_testCaseFilename . '" assertions="' . $this->_methodAssertions . '" time="' . $timeDuration . '"';
			if (empty($this->_methodFailures)) {
				$testCase .= ' />';
			} else {
				$testCase .= '>' . implode('', $this->_methodFailures) . '</testcase>';
			}
			$this->_testCases[] = $testCase;
		}

		parent::paintMethodEnd($method);
	}

	protected function _out($content) {
		fwrite($this->_output, $content);
	}

	function &createInvoker(&$invoker) {
		$package = '';
		if (isset($_ENV['JUNIT_PACKAGE'])) {
			$package = $_ENV['JUNIT_PACKAGE'];
		}
		$testCase = $invoker->getTestCase();
		$this->_testCaseName = $package . get_class($testCase);

		return parent::createInvoker($invoker);
	}

	function paintPass($message) {
		$this->_methodAssertions++;
		parent::paintPass($message);
	}

	function paintFail($message) {
		$this->_methodAssertions++;
		$this->_methodFailures[] = '<failure message="' . $message . '" type="assert" />';
		parent::paintFail($message);
	}

	function paintError($message) {
		$this->_methodAssertions++;
		parent::paintError($message);
	}

	function paintException($message) {
		$this->_methodAssertions++;
		parent::paintException($message);
	}
}