<?php
define('DS', DIRECTORY_SEPARATOR);
require dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php';

class ConfigurationTest extends PHPUnit_Framework_TestCase {
	
	protected $_Configuration;
	
	public function setUp() {
		parent::setUp();
		
		$this->_Configuration = Epimetheus\Configuration::getInstance();
	}
	
	public function testLoadConfigurationNoFile() {
		$this->setExpectedException('Exception', 'Configuration file not found');
		$this->_Configuration->loadConfiguration('faulty-file.json');
	}
	
	public function testLoadConfigurationInvalidJson() {
		$this->setExpectedException('Exception', 'Invalid JSON in configuration file');
		$file = tempnam(sys_get_temp_dir(), "foo");
		file_put_contents($file, '{ "property": 127, }');
		$this->_Configuration->loadConfiguration($file);
		
		unlink($file);
	}
	
	public function testLoadConfigurationInvalidJsonSchema() {
		$this->setExpectedException('Exception');
		$file = tempnam(sys_get_temp_dir(), "foo");
		file_put_contents($file, '{ "repo": "user/somerepo", "profile": "string" }');
		$this->_Configuration->loadConfiguration($file);
		
		unlink($file);
	}
	
	public function testLoadConfiguration() {
		$file = tempnam(sys_get_temp_dir(), "foo");
		file_put_contents($file, '{ "repo": "user/somerepo", "profile": {"dev": {"branch": "master", "target": "/home/", "address": "localhost"}}}');
		$this->_Configuration->loadConfiguration($file);
		
		$this->assertEquals("user/somerepo", $this->_Configuration['repo']);
		$this->assertEquals("master", $this->_Configuration['profile']['dev']['branch']);
		$this->assertEquals("/home/", $this->_Configuration['profile']['dev']['target']);
		
		unlink($file);
	}
	
	public function tearDown() {
		parent::tearDown();
		unset($this->_Configuration);
	}
}