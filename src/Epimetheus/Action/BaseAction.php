<?php
namespace Epimetheus\Action;

abstract class BaseAction implements \Epimetheus\Action {
	
	public function execute() {
		\cli\out('Nothing to do.');
	}
}