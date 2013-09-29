<?php
namespace Epimetheus\Action;
use Epimetheus\Net\Ssh;

class Deploy extends BaseAction {
	
	protected $_ssh;
	protected $_config;
	
	public function __construct($profile) {
		$this->_config = $profile;
	}
	
	public function execute() {
		$configuration = new \Ssh\Configuration($this->_config['address']);
		$authentication = null;
		
		do {
			if (! empty($this->_config['key'])) {
				$authentication = new \Ssh\Authentication\PublicKeyFile($this->_config['login'], $this->_config['key']['public'],
					$this->_config['key']['private'], \cli\prompt('Enter passphrase for private key'));
			} else {
				$authentication = new \Ssh\Authentication\Password($this->_config['login'], \cli\prompt('Password for user: ' . $this->_config['login']));
			}
			
			try {
				$this->_ssh = new \Ssh\Session($configuration, $authentication);
				// send a dummy command to spawn RuntimeException if login fails
				$this->_ssh->getExec()->run('echo Hello > /dev/null');
				break;
			} catch (\RuntimeException $e) {
				if (\cli\choose('Retry') == 'n') {
					throw new \Exception('Unable to login');
				}
			}
		} while (true);
		
		\cli\out("Connected to host\n");
		
		// run pre-deploy scripts
		
		$this->_runScripts('pre-deploy', dirname($this->_config['path']));
		$this->_deploy();
		$this->_runScripts('deploy', $this->_config['path']);
		
		// run deploy scripts
	}
	
	protected function _runScripts($type, $workingDir = null) {
		\cli\out('Running ' . $type . ' scripts ... ');
		$commands = array_merge(\Epimetheus\Configuration::getInstance()['scripts'][$type], $this->_config['scripts'][$type]);
		$exec = $this->_ssh->getExec();
		
		foreach ($commands as $command) {
			\cli\out("\n- " . $command . "\n");
			
			if ($workingDir) {
				$command = 'cd ' . $workingDir . ' && ' . $command;
			}
			
			\cli\out($exec->run($command . "\n"));
		}
		
		\cli\out("Done\n");
	}
	
	protected function _deploy() {
		$sftp = $this->_ssh->getSftp();
		$exists = $sftp->exists($this->_config['path']);
		$dir = dirname ($this->_config['path']);
		
		if (! $exists) {
			\cli\out("Creating directory {$dir} ... ");
			$sftp->mkdir($dir, 0770, true);
			\cli\out("Done\n");
		}
		
		$exec = $this->_ssh->getExec();
		$exec->run('cd ' . $dir);
		$repo = \Epimetheus\Configuration::getInstance()['repo'];
		
		if (! $exists) {
			\cli\out("Cloning repository to {$this->_config['path']} ...");
			\cli\out("\n" . $exec->run('git clone git@github.com:' . $repo . '.git ' . $this->_config['path']) . "\n");
			\cli\out("Done\n");
		}
		
		$exec->run('cd ' . $this->_config['path']);
		
		if ($exists) {
			\cli\out("Pulling from repository ... ");
			$exec->run('git pull');
			\cli\out("Done\n");
		}
		
		\cli\out('Checking out ' . $this->_config['branch'] . ' ... ');
		$exec->run('git checkout ' . $this->_config['branch']);
		\cli\out("Done\n");
	}
}