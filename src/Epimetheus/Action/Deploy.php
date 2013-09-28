<?php
namespace Epimetheus\Action;
use Epimetheus\Net\Ssh;

class Deploy extends BaseAction {
	
	protected $_ssh;
	protected $_config;
	
	public function __construct($profile) {
		$this->_config = $profile;
		
		$this->_ssh = new Ssh($this->_config['address']);
		$username = $this->_config['login'];
		
		if (! empty($this->_config['key'])) {
			$this->_ssh->auth(Ssh::AUTH_KEY, $username, array(
				'public'	=> $this->_config['key']['public'],
				'private'	=> $this->_config['key']['private'],
				'passphrase'	=> \cli\prompt('Enter passphrase for private key:')
			));
		} else {
			$this->_ssh->auth(Ssh::AUTH_PASSWORD, $username, \cli\prompt('Password for user: ' . $username));
		}
	}
	
	public function execute() {
		// run pre-deploy scripts
		
		$exists = $this->_ssh->createDirectory($this->_config['path']);
		$this->_ssh->changeDirectory($this->_config['path']);
		$repo = \Epimetheus\Configuration::getInstance()['repo'];
		
		if ($exists) {
			$this->_ssh->command('git pull');
		} else {
			$this->_ssh->command('git clone git@github.com:' . $repo . '.git . ');
		}
		
		$this->_ssh->command('git checkout ' . $this->_config['branch']);
		
		// run deploy scripts
	}
}