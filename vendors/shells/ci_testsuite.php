<?php
require_once CAKE . 'console' . DS . 'libs' . DS . 'testsuite.php';
require_once CAKE . 'tests' . DS . 'lib' . DS . 'code_coverage_manager.php';

class CiTestsuiteShell extends TestSuiteShell {
	protected $_coverage;

	public $doCoverage = false;

/**
 * Help screen
 *
 * @return void
 * @access public
 */
	function help() {
		$this->out('Usage: ');
		$this->out("\tcake ci_testsuite");
		$this->nl();
		$this->out('Add cov to generate Clover Code Coverage report');
		$this->out("\tcake ci_testsuite cov");
	}

	function main() {
		$this->doCoverage = in_array('cov', $this->args);
		
		App::import('Lib', 'Ci.JunitReporter');

		$this->_startCoverage('Clover Shell');

		$Manager = new TestManager();
		$Manager->appTest = true;
		$Manager->pluginTest = null;

		$Reporter =& new JunitReporter(null, array(
			'app'          => $Manager->appTest,
			'plugin'       => $Manager->pluginTest,
			'group'        => ($this->type === 'group'),
			'codeCoverage' => false,
			'coverage'     => $this->_coverage
		));

		$this->out(__('Running app all', true));
		$result = $Manager->runAllTests($Reporter);

		$Manager->appTest = false;
		$plugins = App::objects('plugin', APP . 'plugins' . DS, false);
		foreach ($plugins as $plugin) {
			$Manager->pluginTest = $plugin;
			$Reporter =& new JunitReporter(null, array(
				'app'          => $Manager->appTest,
				'plugin'       => $Manager->pluginTest,
				'group'        => ($this->type === 'group'),
				'codeCoverage' => false,
				'coverage'     => $this->_coverage
			));

			$this->hr();
			$plugin = Inflector::underscore($plugin);
			$this->out(sprintf(__('Running %s all', true), $plugin));
			$result = $result && $Manager->runAllTests($Reporter);
		}

		$this->_stopCoverage();
		return $result;
	}

	protected function _startCoverage($name) {
		if (!$this->doCoverage) {
			return;
		}

		if (!extension_loaded('xdebug')) {
			$this->out(__('You must install Xdebug to use the CakePHP(tm) Code Coverage Analyzation. Download it from http://www.xdebug.org/docs/install', true));
			$this->_stop(0);
		}

		require 'PHP/CodeCoverage/Autoload.php';
		$this->_coverage = new PHP_CodeCoverage();
		$this->_coverage->filter()->addDirectoryToWhitelist(APP);
		$this->_coverage->filter()->addDirectoryToBlacklist(APP . 'config' . DS);
	}

	protected function _stopCoverage() {
		if (!$this->doCoverage) {
			return;
		}
		$writer = new PHP_CodeCoverage_Report_Clover;
		$writer->process($this->_coverage, ROOT . DS . 'build' . DS . 'logs' . DS . 'clover.xml');
		$writer = new PHP_CodeCoverage_Report_HTML;
		$writer->process($this->_coverage, ROOT . DS . 'build' . DS . 'logs' . DS . 'clover' . DS);
	}
}
