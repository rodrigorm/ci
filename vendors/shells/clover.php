<?php
require_once CAKE . 'console' . DS . 'libs' . DS . 'testsuite.php';

class CloverShell extends TestSuiteShell {
	protected $_coverage;

/**
 * Help screen
 *
 * @return void
 * @access public
 */
	function help() {
		$this->out('Usage: ');
		$this->out("\tcake clover");
	}

	function main() {
		App::import('Lib', 'Ci.JunitReporter');

		if (!extension_loaded('xdebug')) {
			$this->out(__('You must install Xdebug to use the CakePHP(tm) Code Coverage Analyzation. Download it from http://www.xdebug.org/docs/install', true));
			$this->_stop(0);
		}

		$Reporter =& new JunitReporter(null, array(
			'app'          => $this->Manager->appTest,
			'plugin'       => $this->Manager->pluginTest,
			'group'        => ($this->type === 'group'),
			'codeCoverage' => false
		));

		$this->_startCoverage('Clover Shell');

		$Manager = new TestManager();

		$this->out(__('Running app all', true));
		$Manager->appTest = true;
		$Manager->pluginTest = null;
		$result = $Manager->runAllTests($Reporter);

		$Manager->appTest = false;
		$plugins = App::objects('plugin', APP . 'plugins' . DS, false);
		foreach ($plugins as $plugin) {
			$this->hr();
			$Reporter =& new JunitReporter(null, array(
				'app'          => $this->Manager->appTest,
				'plugin'       => $this->Manager->pluginTest,
				'group'        => ($this->type === 'group'),
				'codeCoverage' => false
			));

			$plugin = Inflector::underscore($plugin);
			$this->out(sprintf(__('Running %s all', true), $plugin));
			$Manager->pluginTest = $plugin;
			$result = $result && $Manager->runAllTests($Reporter);
		}

		$this->_stopCoverage();
		return $result;
	}

	protected function _startCoverage($name) {
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
		$this->_coverage->stop();
		$writer = new PHP_CodeCoverage_Report_Clover;
		$writer->process($this->_coverage, ROOT . DS . 'build' . DS . 'logs' . DS . 'clover.xml');
		$writer = new PHP_CodeCoverage_Report_HTML;
		$writer->process($this->_coverage, ROOT . DS . 'build' . DS . 'logs' . DS . 'clover' . DS);
	}
}
