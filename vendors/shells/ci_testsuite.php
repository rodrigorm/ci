<?php
require_once CAKE . 'console' . DS . 'libs' . DS . 'testsuite.php';

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
			'codeCoverage' => false
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
				'codeCoverage' => false
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


		require_once 'PHP/CodeCoverage.php';
		require_once 'PHP/CodeCoverage/Report/Clover.php';
		require_once 'PHP/CodeCoverage/Report/HTML.php';
		$this->_coverage = new PHP_CodeCoverage();
		$this->_coverage->filter()->addDirectoryToBlacklist(CAKE);
		$this->_coverage->filter()->addDirectoryToBlacklist(ROOT . DS . 'plugins' . DS);
		$this->_coverage->filter()->addDirectoryToBlacklist(ROOT . DS . 'vendors' . DS);

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
		$writer->process($this->_coverage, ROOT . DS . 'build' . DS . 'logs' . DS . 'clover' . DS);
	}
}
