<?php

class GruppenView extends CrudView {
	
	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Gruppe");
	}
	
	/**
	 * Extended version of modePrefix for sub-module.
	 */
	function modePrefix() {
		return "?mod=" . $this->getModId() . "&mode=groups&func=";
	}
	
	function start() {
		Writing::h1("Gruppen");
		$explanation = "Auf dieser Seite verwaltest du die Gruppen deiner Band.
		                Die Gruppen \"Administratoren\" und \"Mitglieder\" können nicht gelöscht werden.
						Möglich weitere Gruppen sind, z.B. Rhythmusgruppe, Combo, etc.";
		Writing::p($explanation);
		
		$groups = $this->getData()->getGroups();
		$table = new Table($groups);
		$table->renameAndAlign($this->getData()->getFields());
		$table->setEdit("id");
		$table->changeMode("groups&func=view");
		$table->write();
	}
	
	function showOptions() {
		if(!isset($_GET["func"]) || $_GET["func"] == "start") {
			$this->startOptions();
		}
		else {
			$subOptionFunc = $_GET["func"] . "Options";
			if(method_exists($this, $subOptionFunc)) { 
				$this->$subOptionFunc();
			}
			else {
				$this->defaultOptions();
			}
		}
	}
	
	function startOptions() {
		$backBtn = new Link("?mod=" . $this->getModId() . "&mode=start", "Zurück");
		$backBtn->addIcon("arrow_left");
		$backBtn->write();
		$this->buttonSpace();
		
		$new = new Link($this->modePrefix() . "addEntity", "Gruppe hinzufügen");
		$new->addIcon("plus");
		$new->write();
	}
	
	function backToStart() {
		$back = new Link($this->modePrefix() . "start", "Zurück");
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	function view() {
		$this->checkID();
		
		$group = $this->getData()->findByIdNoRef($_GET["id"]);
		Writing::h2("Gruppe: " . $group["name"]);
		
		// group information
		$dv = new Dataview();
		$dv->autoAddElements($group);
		$dv->autoRename($this->getData()->getFields());
		$dv->write();
		
		// group members
		Writing::h3("Gruppenmitglieder");
		
		$members = $this->getData()->getGroupMembers($_GET["id"]);
		$table = new Table($members);
		$table->write();
	}
	
	function viewOptions() {
		$this->backToStart();
		
		if($_GET["id"] != KontakteData::$GROUP_ADMIN && $_GET["id"] != KontakteData::$GROUP_MEMBER) {
			$this->buttonSpace();
			
			// show buttons to edit and delete
			$edit = new Link($this->modePrefix() . "edit&id=" . $_GET["id"],
					$this->getEntityName() . " bearbeiten");
			$edit->addIcon("edit");
			$edit->write();
			
			$this->buttonSpace();
			
			$del = new Link($this->modePrefix() . "delete_confirm&id=" . $_GET["id"],
					$this->getEntityName() . " löschen");
			$del->addIcon("remove");
			$del->write();
		}
	}
}

?>