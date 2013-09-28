<?php
require dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php';

class EpimetheusTest extends PHPUnit_Framework_TestCase {
	
	protected $_Epimetheus;
	
	public function setUp() {
		parent::setUp();
		
		$this->_Epimetheus = new Epimetheus\Epimetheus();
	}
	
	public function testRun() {
		$this->_Epimetheus->run();
	}
	
	public function tearDown() {
		parent::tearDown();
	}
}