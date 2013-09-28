<?php

namespace Epimetheus;
/**
 * Epimetheus::main($argc, $argv);
 */
if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

/**
 *  epimetheus --deploy <profile>	deploys profile
 *  epimetheus --deploy				prompt which profile
 */
class Epimetheus {
	
	protected $_cliOptions;
	protected $_config;
	
	public function __construct() {
		$this->_cliOptions = new \cli\Arguments();
		$this->_cliOptions->addFlag(array('help', 'h'), 'Shows this help');
		$this->_cliOptions->addOption(array('config', 'c'), array(
			'default'	=> getcwd() . DS . '.epimetheus.json',
			'description'	=> 'The configuration file'
		));
		$this->_cliOptions->addOption('deploy', array(
			'description'	=> 'The profile you would like to deploy'
		));
	}
	
	public function run() {
		$this->_cliOptions->parse();
		
		if ($this->_cliOptions['help']) {
			echo $this->_cliOptions->getHelpScreen();
			exit;
		}
		
		$config = $this->_cliOptions['config'] ?: $this->_cliOptions->getOption('config')['default'];
		$this->_config = Configuration::getInstance()->loadConfiguration($config);
		
		$action = null;
		
		if (array_key_exists('deploy', $this->_cliOptions->getArguments())) {
			$profile = $this->_cliOptions['deploy'];
			
			if ($profile == null) {
				$profile = \cli\prompt("Which profile would you like to use?");
			}
			
			$action = new Action\Deploy($this->_config[$profile]);
		}
		
		if (! $action) {
			\cli\err('No action to be performed. See help.');
		} else {
			$action->execute();
		}
	}
}
