<?php
require_once CAKE . 'console' . DS . 'libs' . DS . 'testsuite.php';

class CiTestSuiteShell extends TestSuiteShell {
	protected $_coverage;

/**
 * Help screen
 *
 * @return void
 * @access public
 */
	function help() {
		$this->out('Usage: ');
		$this->out("\tJust prepend ci_ on testsuite command, example:");
		$this->out("\tcake ci_testsuite category test_type file");
		$this->out("\tSee testsuite shell for more examples");
	}

/**
 * Executes the tests depending on our settings
 *
 * @return void
 * @access private
 */
	function __run() {
		App::import('Lib', 'Ci.JunitReporter');

		$Reporter =& new JunitReporter(null, array(
			'app'          => $this->Manager->appTest,
			'plugin'       => $this->Manager->pluginTest,
			'group'        => ($this->type === 'group'),
			'codeCoverage' => false
		));

		if ($this->doCoverage) {
			if (!extension_loaded('xdebug')) {
				$this->out(__('You must install Xdebug to use the CakePHP(tm) Code Coverage Analyzation. Download it from http://www.xdebug.org/docs/install', true));
				$this->_stop(0);
			}
		}

		if ($this->type == 'all') {
			$this->_startCoverage($this->Manager->appTest ? 'App All Tests' : $this->Manager->pluginTest . ' All Tests');
			$result = $this->Manager->runAllTests($Reporter);
			$this->_stopCoverage();
			return $result;
		}

		if ($this->type == 'group') {
			$ucFirstGroup = ucfirst($this->file);
			$this->_startCoverage($ucFirstGroup);
			$result = $this->Manager->runGroupTest($ucFirstGroup, $Reporter);
			$this->_stopCoverage();

			return $result;
		}

		$folder = $folder = $this->__findFolderByCategory($this->category);
		$case = $this->__getFileName($folder, $this->isPluginTest);

		$this->_startCoverage($case);
		$result = $this->Manager->runTestCase($case, $Reporter);
		$this->_stopCoverage();

		return $result;
	}

	protected function _startCoverage($name) {
		if (!$this->doCoverage) {
			return;
		}
		require_once 'PHP/CodeCoverage.php';
		require_once 'PHP/CodeCoverage/Report/Clover.php';
		require_once 'PHP/CodeCoverage/Report/HTML.php';
		$this->_coverage = new PHP_CodeCoverage();
		$this->_coverage->filter()->addDirectoryToBlacklist(CAKE);
		$this->_coverage->filter()->addDirectoryToBlacklist(ROOT . DS . 'plugins' . DS);
		$this->_coverage->filter()->addDirectoryToBlacklist(ROOT . DS . 'vendors' . DS);

		if ($this->isPluginTest) {
			$this->_coverage->filter()->addDirectoryToBlacklist(APP);
			$this->_coverage->filter()->removeDirectoryFromBlacklist(App::pluginPath($this->category));
		}

		$this->_coverage->start($name);
	}

	protected function _stopCoverage() {
		if (!$this->doCoverage) {
			return;
		}
		$this->_coverage->stop();
		$writer = new PHP_CodeCoverage_Report_Clover;
		$writer->process($this->_coverage, ROOT . DS . 'build' . DS . 'logs' . DS . 'clover.xml');
		$writer = new PHP_CodeCoverage_Report_HTML;
		$destination = ROOT . DS . 'build' . DS . 'logs' . DS . 'clover' . DS . $this->category . DS;
		@mkdir($destination);
		$writer->process($this->_coverage, $destination);
	}
}
