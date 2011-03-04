<?php
require_once App::pluginPath('selenium') . 'vendors' . DS . 'shells' . DS . 'selenium.php';


class CiSeleniumShell extends SeleniumShell {
/**
 * Help screen
 *
 * @return void
 * @access public
 */
	function help() {
		$this->out('Usage: ');
		$this->out("\tJust prepend ci_ on selenium command, example:");
		$this->out("\tcake ci_selenium category test_type file");
		$this->out("\tSee selenium shell for more examples");
	}

/**
 * Executes the tests depending on our settings
 *
 * @return void
 * @access private
 */
	function __run() {
		App::import('Lib', 'Ci.JunitReporter');

		$Reporter =& new JunitReporter(null, $this->params);

		if ($this->type == 'all') {
			return $this->Manager->runAllTests($Reporter);
		}

		if ($this->type == 'group') {
			$ucFirstGroup = ucfirst($this->file);
			$result = $this->Manager->runGroupTest($ucFirstGroup, $Reporter);
			return $result;
		}

		$folder = $folder = $this->__findFolderByCategory($this->category);
		$case = $this->__getFileName($folder, $this->isPluginTest);

		$result = $this->Manager->runTestCase($case, $Reporter);
		return $result;
	}
}