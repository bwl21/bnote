<?php

/**
 * Controller of the concert module.
 * @author matti
 *
 */
class KonzerteController extends DefaultController {
	
	private $programView;
	
	function start() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "programs") {
			$this->programs();
		}
		else {
			parent::start();
		}
	}
	
	private function programs($init = false) {
		require_once $GLOBALS["DIR_DATA_MODULES"] . "programdata.php";
		require_once $GLOBALS["DIR_PRESENTATION_MODULES"] . "programview.php";
		require_once $GLOBALS["DIR_LOGIC_MODULES"] . "programcontroller.php";
		
		$ctrl = new ProgramController();
		$data = new ProgramData();
		$this->programView = new ProgramView($ctrl);
		$ctrl->setData($data);
		$ctrl->setView($this->programView);
		
		if(!$init) {
			$ctrl->start();
		}
	}
	
	function getProgramView() {
		if($this->programView == null) {
			$this->programs(true);
		}
		return $this->programView;
	}
	
}