<?php
namespace Epimetheus\Net;

class Ssh {
	const AUTH_KEY = 0;
	const AUTH_PASSWORD = 1;
	
	protected $_ssh;
	
	public function __construct ($host, $port = 22) {
		$this->_ssh = \ssh2_connect($host, $port, array(
			'hostkey'	=> 'ssh-rsa'
		), array(
			'disconnect' => function($reason, $message, $language) {
				\cli\out(sprintf("Server disconnected with code [%d] and message: %s\n", $reason, $message));
			}
		));
		
		if (! $this->_ssh) {
			throw new \Exception('Unable to connect to server');
		}
	}
	
	public function auth($type, $username, $value) {
		switch($type) {
			case self::AUTH_KEY:
				$ret = \ssh2_auth_pubkey_file($this->_ssh, $username, $value['public'], $value['private'], $value['passphrase']);
			break;
			case self::AUTH_PASSWORD:
				$ret = \ssh2_auth_password($this->_ssh, $username, $value);
			break;
			default:
				throw new \Exception('Unsupported auth type');
			break;
		}
		
		if (! $ret) {
			throw new \Exception('Authentication failed');
		}
		
		return true;
	}
	
	public function createDirectory($dir) {
		$sftp = \ssh2_sftp($this->_ssh);
		return \ssh2_sftp_mkdir($sftp, $dir, 0770, true);
	}
	
	public function changeDirectory($dir) {
		return $this->command('cd ' . $dir);
	}
	
	public function command($cmd) {
		$stdOut = \ssh2_exec($this->_ssh, $cmd);

		$stdErr = ssh2_fetch_stream($stdOut, SSH2_STREAM_STDERR);
		stream_set_blocking($stdErr, true);
		stream_set_blocking($stdOut, true);
		$error = stream_get_contents($stdErr);
        
		if ($error !== '') {
			throw new \Exception($error);
		}
		
		return stream_get_contents($stdout);
	}
}