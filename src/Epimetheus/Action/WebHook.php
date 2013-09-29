<?php
namespace Epimetheus\Action;
/**
 * WebHook for pulling automatically whenever you push a commit
 * to Github.
 *
 * Simple example: create a file called hook.php, and in it:
 * require '../vendor/autoload.php';
 * try {
 * 		$hook = new Epimetheus\Action\Webhook('davidsteinsland/cakephp-gearman', 'dev');
 * 		$hook->execute();
 * 	} catch (Exception $e) {
 * 		// mail someone?
 *  }
 *
 * This will pull the repository davidsteinsland/cakephp-gearman, and execute the profile
 * 'dev' in .epimetheus.json.
 *
 * If the repository is private, you should generate a SSH public/private key pair
 * for the current user, and add it to your repo.
 *
 * The WebHook class will create the repository in the same directory it
 * is in. If you want to customize this, set the "path" value in your profile.
 *
 * The WebHook class will 'behave' the same as the deploy action, meaning it will
 * carry out the commands listed in 'deploy'.
 *
 * In case you want to only pull one specific branch, set the "branch" value in your
 * configuration JSON.
 */
class WebHook extends BaseAction {

	protected $_repository;

	protected $_owner;

	protected $_profile;

	protected $_path;

	protected $_allowedHosts = array(
		'204.232.175.64/27', '192.30.252.0/22'
	);

	public function __construct($repository, $profile) {
		list($owner, $repository) = explode('/', $repository);
		$this->_repository = $repository;
		$this->_owner = $owner;
		$this->_profile = $profile;
		$this->_path = __DIR__ . DIRECTORY_SEPARATOR . $this->_repository;
	}

	public function setAllowedHosts($hosts) {
		$this->_allowedHosts = $hosts;
	}

	public function execute() {
		$requester = getenv('REMOTE_ADDR');
		$allowed = false;
		foreach ($this->_allowedHosts as $network) {
			if (\Allty\Utils\IpTools::ipInRange($requester, $network)) {
				$allowed = true;
				break;
			}
		}

		if (! $allowed) {
			throw new \Exception('You are not allowed');
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			throw new \Exception("Requested method is not POST");
		}

		if (empty($_POST['payload'])) {
			throw new \Exception('Payload is empty');
		}

		$payload = json_decode($_POST['payload']);

		if (json_last_error() || $payload === null) {
			throw new \Exception('Invalid JSON in payload, or no payload at all');
		}

		if ($this->_owner !== $data->repository->owner->name || $this->_repository !== $data->repository->name) {
			throw new \Exception('This hook is not installed for your repository.');
		}

		if (! is_dir($this->_path)) {
			$cmd = sprintf('git clone git@github.com:%s/%s.git %s',
				$this->_owner, $this->_repository, $this->_path);

			if (! $this->_runCommand($cmd, $this->_path)) {
				throw new \Exception('Failed to initialize git repo');
			}
		}

		$configuration = \Epimetheus\Configuration::getInstance()
			->loadConfiguration($this->_path . DIRECTORY_SEPARATOR . '.epimetheus.json');
		$profileConfiguration = $configuration['profile'][$this->_profile];
		$branch = !empty($profileConfiguration['branch']) ? $profileConfiguration['branch'] : basename($data->ref);

		if (! empty($profileConfiguration['path'])) {
			if (! rename($this->_path, $profileConfiguration['path'])) {
				throw new \Exception('Failed to move repository to target specified in config');
			}

			$this->_path = $profileConfiguration['path'];
		}

		$cmd = sprintf('git checkout %s && git pull', $branch);
		if (! $this->_runCommand($cmd, $this->_path)) {
			throw new \Exception('Failed to check out branch');
		}

		$this->_runScripts($profileConfiguration, $this->_path, 'deploy');
	}

	protected function _runScripts($config, $path, $type) {
		$commands = array_merge(\Epimetheus\Configuration::getInstance()['scripts'][$type], $config['scripts'][$type]);

		foreach ($commands as $command) {
			if (! $this->_runCommand($command, $path)) {
				throw new \Exception('Command ' . $command . ' failed');
			}
		}
	}

	protected function _runCommand($cmd, $cwd) {
		$process = new \Symfony\Component\Process\Process($cmd, $cwd);
		$process->run();

		return $process->isSuccessful();
	}
}
