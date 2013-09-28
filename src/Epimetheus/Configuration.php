<?php

namespace Epimetheus;

class Configuration implements \ArrayAccess {
	
	protected $_configuration = array();
	protected static $_instance = null;
	
	private function __construct() {
	}
	
	private function __clone() {
	}
	
	public static function getInstance() {
		if (! self::$_instance) {
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
	
	public function loadConfiguration($file) {
		if (! file_exists($file)) {
			throw new \Exception('Configuration file not found');
		}
		
		$data = file_get_contents($file);
		$json = json_decode($data);
		
		if (json_last_error()) {
			throw new \Exception("Invalid JSON in configuration file");
		}
		
		$schemaFile = __DIR__ . DS . 'epimetheus-schema.json';
		$retriever = new \JsonSchema\Uri\UriRetriever;
		
		$schema = $retriever->retrieve($schemaFile);
		
		$refResolver = new \JsonSchema\RefResolver($retriever);
		$refResolver->resolve($schema, 'file://' . __DIR__);
		
		$validator = new \JsonSchema\Validator();
		$validator->check($json, $schema);
		
		if (! $validator->isValid()) {
			$errors = '';
			foreach ($validator->getErrors() as $error) {
				$errors .= sprintf("[%s] %s\n", $error['property'], $error['message']);
			}
			
			throw new \Exception("JSON schema is not valid:\n" . $errors);
		}

		$this->_configuration = json_decode($data, true);
		
		return $this;
	}
	
	/**
	 * Returns true if a given argument exists in the configuration.
	 *
	 * @param mixed  $offset  An Argument object or the name of the argument.
	 * @return bool
	 */
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->_configuration);
	}

	/**
	 * Get the configuration argument's value.
	 *
	 * @param mixed  $offset  An Argument object or the name of the argument.
	 * @return mixed
	 */
	public function offsetGet($offset) {
		if (isset($this->_configuration[$offset])) {
			return $this->_configuration[$offset];
		}
	}

	/**
	 * Sets the value of a configuration argument.
	 *
	 * @param mixed  $offset  An Argument object or the name of the argument.
	 * @param mixed  $value   The value to set
	 */
	public function offsetSet($offset, $value) {
		$this->_configuration[$offset] = $value;
	}

	/**
	 * Unset a configuration argument.
	 *
	 * @param mixed  $offset  An Argument object or the name of the argument.
	 */
	public function offsetUnset($offset) {
		unset($this->_configuration[$offset]);
	}
}
