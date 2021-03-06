<?php

/**
 * View for contact module.
 * @author matti
 *
 */
class KontakteView extends CrudRefLocationView {
	
	/**
	 * Create the contact view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Kontakt");
		$this->setJoinedAttributes(array(
			"address" => array("street", "city", "zip", "state", "country"),
			"instrument" => array("name")
		));
	}
	
	function start() {		
		// show band members
		$this->showContacts();
	}
	
	protected function isSubModule($mode) {
		if($mode == "groups") return true;
		return false;
	}
	
	protected function subModuleOptions() {
		$this->getController()->groupOptions();
	}
	
	protected function startOptions() {
		parent::startOptions();
		
		// set group filter if group is selected
		$groupFilter = "&group=";
		if(isset($_GET["group"])) {
			$groupFilter .= $_GET["group"];
		}
		else {
			$groupFilter .= KontakteData::$GROUP_MEMBER; // members by default
		}
		$eps = new Link($this->modePrefix() . "integration" . $groupFilter, "Einphasung");
		$eps->addIcon("integration");
		$eps->write();
		
		$groups = new Link($this->modePrefix() . "groups&func=start", "Gruppen");
		$groups->addIcon("mitspieler");
		$groups->write();
		
		$print = new Link($this->modePrefix() . "selectPrintGroups", "Liste drucken");
		$print->addIcon("printer");
		$print->write();
		
		$vc = new Link($this->modePrefix() . "contactImport", "Import (vCard)");
		$vc->addIcon("arrow_down");
		$vc->write();
		
		$vc = new Link($GLOBALS["DIR_EXPORT"] . "kontakte.vcd", "Export (vCard)");
		$vc->addIcon("arrow_up");
		$vc->setTarget("_blank");
		$vc->write();
	
		$gdprOk = new Link($this->modePrefix() . "gdprOk", "Datenschutz");
		$gdprOk->addIcon("question");
		$gdprOk->write();
	}
	
	function showContacts() {		
		// show correct group
		if(isset($_GET["group"]) && $_GET["group"] == "all") {
			$data = $this->getData()->getAllContacts();
		}
		else if(isset($_GET["group"])) {
			$data = $this->getData()->getGroupContacts($_GET["group"]);
		}
		else {
			// default: MEMBERS
			$data = $this->getData()->getMembers();
		}
		
		// write
		$this->showContactTable($data);
	}
	
	private function showContactTable($data) {
		$groups = $this->getData()->getGroups();
		
		// show groups as tabs
		echo "<div class=\"contact_view\">\n";
		echo " <div class=\"view_tabs\">";
		foreach($groups as $cmd => $info) {
			if($cmd == 0) {
				// instead of skipping the first header-row,
				// insert a tab with all contacts
				$cmd = "all";
				$info = array("name" => "Alle Kontakte", "id" => "all");
			}
			$label = $info["name"];
			$groupId = $info["id"];
			
			$active = "";
			if(isset($_GET["group"]) && $_GET["group"] == $groupId) $active = "_active";
			else if(!isset($_GET["group"]) && $groupId == 2) $active = "_active";
			
			echo "<a href=\"" . $this->modePrefix() . "start&group=$groupId\"><span class=\"view_tab$active\">$label</span></a>";
		}
		
		// show data
		echo " <table id=\"contact_table\" class=\"contact_view\">\n";
		foreach($data as $i => $row) {
			
			
			if($i == 0) {
				// header
				echo "<thead>";
				echo "   <td class=\"DataTable_Header\">Name, Vorname</td>";
				echo "   <td class=\"DataTable_Header\">Musik</td>";
				echo "   <td class=\"DataTable_Header\">Adresse</td>";
				echo "   <td class=\"DataTable_Header\">Telefone</td>";
				echo "   <td class=\"DataTable_Header\">Online</td>";
				echo "</thead>";
				echo "<tbody>";
			}
			else {
				echo "  <tr>\n";
				// body
				$contact_name = $row["surname"] . ", " . $row["name"];
				if($row['nickname'] != "") {
					$contact_name .= "<br/>(" . $row['nickname'] . ")";
				}
				echo "   <td class=\"DataTable\"><a href=\"" . $this->modePrefix() . "view&id=" . $row["id"] . "\">$contact_name</a></td>";
				
				// instrument, conductor
				echo "   <td class=\"DataTable\">Instrument: " . $row["instrumentname"];
				if(isset($row["is_conductor"])) {
					echo "<br>Dirigent: ";
					echo $row["is_conductor"] == "1" ? "ja" : "nein";
				}
				echo "</td>";
				
				echo "   <td class=\"DataTable\" style=\"width: 150px;\">" . $this->formatAddress($row, TRUE, "", TRUE) . "</td>";
				
				// phones
				$phones = "";
				if($row["phone"] != "") {
					$phones .= "Tel: " . $row["phone"];
				}
				if($row["mobile"] != "") {
					if($phones != "") $phones .= "<br/>";
					$phones .= "Mobil: " . $row["mobile"]; 
				}
				if($row["business"] != "") {
					if($phones != "") $phones .= "<br/>";
					$phones .= "Arbeit: " . $row["business"];
				}
				echo "   <td class=\"DataTable\" style=\"width: 150px;\">$phones</td>";
				
				// online
				echo "   <td class=\"DataTable\"><a href=\"mailto:" . $row["email"] . "\">" . $row["email"] . "</a>";
				if($row["web"] != "") {
					echo "<br/><a href=\"http://" . $row["web"] . "\">" . $row["web"] . "</a>";
				} 
				echo "</td>";
			}
			
			echo "  </tr>";
		}
		// show "no entries" row when this is the case
		if(count($data) == 1) {
			echo "<tr><td colspan=\"5\">Keine Kontaktdaten vorhanden.</td></tr>\n";
		}
		
		echo "</tbody>";
		echo "</table>\n";
		echo " </div>";
		echo "</div>";

		?>
			<script>
		
		// convert table to javasript DataTable
		$(document).ready(function() {
			var identifier = "#contact_table";
    		$(identifier).DataTable({
				 "paging": false, 
				 "info": false,  
				 "oLanguage": {
					 		 "sEmptyTable":  "<?php echo Lang::txt("table_no_entries"); ?>",
							 "sInfoEmpty":  "<?php echo Lang::txt("table_no_entries"); ?>",
							 "sZeroRecords":  "<?php echo Lang::txt("table_no_entries"); ?>",
        					 "sSearch": "<?php echo Lang::txt("table_search"); ?>"
		       }
			});
	});
		</script>
		<?php
	}
	
	function addEntity() {
		$form = new Form("Kontakt hinzufügen", $this->modePrefix() . "add");
		
		// just add all custom and regular fields
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement("id");
		$form->removeElement("status");
		
		// instrument
		$form->setForeign("instrument", "instrument", "id", "name", -1);
		$form->addForeignOption("instrument", "[keine Angabe]", 0);
		
		// address
		$form->removeElement("address");
		$this->addAddressFieldsToForm($form);
		
		// contact group selection
		$groups = $this->getData()->getGroups();
		$gs = new GroupSelector($groups, array(), "group");
		$form->addElement("Gruppen", $gs);
		
		$form->write();
	}
	
	function add() {
		$this->groupSelectionCheck();
		
		// do as usual
		parent::add();
	}
	
	function addOptions() {
		$this->backToStart();
		$this->buttonSpace();
		
		$addMore = new Link($this->modePrefix() . "addEntity", Lang::txt("kontakte_addMoreBtn"));
		$addMore->addIcon("plus");
		$addMore->write();
	}
	
	function view() {
		// fetch contact and user details
		$contact = $this->getData()->getContact($_GET["id"]);
		
		// the contact is a member of these groups
		$groups = $this->getData()->getContactGroups($_GET["id"]);
		$groups = str_replace(",", ", ", $groups);
		
		// custom field handling
		$customFields = $this->getData()->getCustomFields("c");
		
		// build output
		Writing::h1($contact["name"] . " " . $contact["surname"]);
		?>
		<div class="contactdetail_section">
			<div class="contactdetail_section_header">Stammdaten</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Kontakt ID</label>
				<div class="contactdetail_entry_value"><?php echo $contact["id"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Status</label>
				<div class="contactdetail_entry_value"><?php echo $contact["status"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Vor- und Nachname</label>
				<div class="contactdetail_entry_value"><?php echo $contact["name"] . " " . $contact["surname"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Spitzname</label>
				<div class="contactdetail_entry_value"><?php echo $contact["nickname"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Organisation</label>
				<div class="contactdetail_entry_value"><?php echo $contact["company"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Geburtstag</label>
				<div class="contactdetail_entry_value"><?php echo Data::convertDateFromDb($contact["birthday"]); ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Adresse</label>
				<div class="contactdetail_entry_value"><?php echo $this->formatAddress($contact, TRUE, "", TRUE); ?></div>
			</div>
		</div>
		
		<div class="contactdetail_section">
			<div class="contactdetail_section_header">Kommunikation</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Telefon privat</label>
				<div class="contactdetail_entry_value"><?php echo $contact["phone"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Telefon geschäftlich</label>
				<div class="contactdetail_entry_value"><?php echo $contact["business"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Mobil</label>
				<div class="contactdetail_entry_value"><?php echo $contact["mobile"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Fax</label>
				<div class="contactdetail_entry_value"><?php echo $contact["fax"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">E-Mail-Adresse</label>
				<div class="contactdetail_entry_value"><?php echo $contact["email"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Website</label>
				<div class="contactdetail_entry_value"><?php echo $contact["web"]; ?></div>
			</div>
		</div>
		
		<div class="contactdetail_section">
			<div class="contactdetail_section_header">Musikalische Daten</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Instrument</label>
				<div class="contactdetail_entry_value"><?php echo $contact["instrumentname"]; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Ist Dirigent</label>
				<div class="contactdetail_entry_value"><?php echo $contact["is_conductor"] == 0 ? "nein" : "ja"; ?></div>
			</div>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label">Gruppen</label>
				<div class="contactdetail_entry_value"><?php echo $groups; ?></div>
			</div>
			<?php
			for($i = 1; $i < count($customFields); $i++) {
				$field = $customFields[$i];
			?>
			<div class="contactdetail_entry">
				<label class="contactdetail_entry_label"><?php echo $field['txtdefsingle'] ?></label>
				<div class="contactdetail_entry_value"><?php
				$val = $contact[$field["techname"]];
				if($field["fieldtype"] == "BOOLEAN") {
					echo $val == 1 ? "ja" : "nein";
				}
				else {
					echo $val;
				}
				?></div>
			</div>
			<?php
			} 
			?>
		</div>
		<?php
	}
	
	function additionalViewButtons() {
		// only show when it doesn't already exist
		if(!$this->getData()->hasContactUserAccount($_GET["id"])) {
			// show button
			$btn = new Link($this->modePrefix() . "createUserAccount&id=" . $_GET["id"], "Benutzerkonto erstellen");
			$btn->addIcon("user");
			$btn->write();
			$this->buttonSpace();
		}
		
		// GDPR report
		$gdpr = new Link($this->modePrefix() . "gdprReport&id=" . $_GET["id"], "Datenauszug");
		$gdpr->addIcon("question");
		$gdpr->write();
	}
	
	function editEntityForm($write=true) {
		$contact = $this->getData()->getContact($_GET["id"]);
		
		$form = new Form("Kontakt bearbeiten", $this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(), $this->getData()->getTable(), $_GET["id"], array("company"));
		$form->removeElement("id");
		$form->setForeign("instrument", "instrument", "id", "name", $contact["instrument"]);
		
		$form->removeElement("address");
		$this->addAddressFieldsToForm($form, $this->getData()->getAddress($contact["address"]));
		
		$form->removeElement("status");
		
		// custom fields
		$fields = $this->getData()->getCustomFields('c');
		for($i = 1; $i < count($fields); $i++) {
			$field = $fields[$i];
			$techname = $field["techname"];
			$form->addElement($field['txtdefsingle'], new Field($techname, $contact[$techname], 
					$this->getData()->fieldTypeFromCustom($field['fieldtype'])));
		}
		
		// group selection
		$groups = $this->getData()->getGroups();
		$userGroups = $this->getData()->getContactGroupsArray($_GET["id"]);
		$gs = new GroupSelector($groups, $userGroups, "group");
		$form->addElement("Gruppen", $gs);
		
		$form->write();
	}
	
	function edit_process() {
		$this->groupSelectionCheck();
		
		// do as usual
		parent::edit_process();
	}
	
	private function groupSelectionCheck() {
		// make sure at least one group is selected
		$groups = $this->getData()->getGroups();
		$isAGroupSelected = false;
		
		for($i = 1; $i < count($groups); $i++) {
			$fieldId = "group_" . $groups[$i]["id"];
			if(isset($_POST[$fieldId])) {
				$isAGroupSelected = true;
				break;
			}
		}
		
		if(!$isAGroupSelected) {
			new BNoteError("Bitte weise dem Kontakt mindestens eine Gruppe zu.");
		}
	}
	
	function selectPrintGroups() {
		Writing::h2("Mitgliederliste drucken");
		Writing::p("Alle Mitglieder sind in Gruppen sortiert. Bitte wähle die Gruppen deren Mitglieder du drucken möchtest.");
		
		$form = new Form("Druckauswahl", $this->modePrefix() . "printMembers");
		
		// custom field selection
		$fields = $this->getData()->getCustomFields('c');
		$fieldSelector = new GroupSelector($fields, array(), "custom");
		$fieldSelector->setNameColumn("txtdefsingle");
		$form->addElement("Zeige Feld", $fieldSelector);
				
		// group filter
		$groups = $this->getData()->getGroups();
		$gs = new GroupSelector($groups, array(), "group");
		$form->addElement("Filter: Gruppe", $gs);
		
		$form->changeSubmitButton("Druckvorschau anzeigen");
		$form->write();
	}
	
	function printMembers() {
		// convert $_POST groups into a flat groups array
		$allGroups = $this->getData()->getGroups();
		$groups = array();
		for($i = 1; $i < count($allGroups); $i++) {
			$gid = $allGroups[$i]["id"];
			if(isset($_POST["group_" . $gid])) {
				$name = $allGroups[$i]["name"];
				$groups[$gid] = $name;
			}
		}
		if(count($groups) == 0) {
			new Message("Fehler bei Gruppenauswahl", "Wähle mindestens eine Gruppe zum drucken aus.");
			$this->backToStart();
			return;
		}
		
		// print styles
		?>
		<style>
			@media print {
				.dataTables_filter {
					display: none;
				}
				
				thead > tr {
					border-color: #61b3ff;
				}
				
				td.DataTable_Header {
					color: #61b3ff;
				}
			}
		</style>
		<?php
		// show a printable list of members for each group
		foreach($groups as $gid => $name) {
			Writing::h3($name);
			
			$members = $this->getData()->getGroupContacts($gid);
			$tab = new Table($this->formatMemberPrintTable($members));
			$tab->write();
			
			$this->verticalSpace();
		}
	}
	
	private function formatMemberPrintTable($members) {
		$formatted = array();
		// header
		$header = array (
				"Name",
				"Spitzname",
				"Instrument",
				"Telefon",
				"Mobil",
				"Business",
				"Email",
				"Adresse" 
		);
		// add selected custom fields
		$fields = $this->getData()->getCustomFields('c');
		$fieldInfo = $this->getData()->compileCustomFieldInfo($fields);
		$customFields = GroupSelector::getPostSelection($fields, "custom");		
		$selectedCustomFields = array();
		foreach($fieldInfo as $techname => $info) {
			if(in_array($info[2], $customFields)) {
				array_push($header, $info[0]);
				$selectedCustomFields[$techname] = $info;
			}
		}
		array_push($formatted, $header);
		
		// body
		for($i = 1; $i < count($members); $i++) {
			$row = $members[$i];
			$fRow = array(
				"Name" => $row["name"] . " " . $row["surname"],
				"Spitzname" => $row["nickname"],
				"Instrument" => $row["instrumentname"],
				"Telefon" => $row["phone"],
				"Mobil" => $row["mobile"],
				"Business" => $row["business"],
				"Email" => $row["email"],
				"Adresse" => $row["street"] . ", " . $row["zip"] . " " . $row["city"]
			);
			foreach($selectedCustomFields as $techname => $info) {
				if(isset($row[$techname])) {
					$fRow[$info[0]] = $row[$techname];
				}
				else {
					$fRow[$info[0]] = '-';
				}
			}
			array_push($formatted, $fRow);
		}
		
		return $formatted;
	}
	
	function printMembersOptions() {
		$this->backToStart();
		$this->buttonSpace();
		
		$prt = new Link("javascript:window.print();", Lang::txt("print"));
		$prt->addIcon("printer");
		$prt->write();
	}
	
	function userCreatedAndMailed($username, $email) {
		$m = "Die Zugangsdaten wurden an $email geschickt.";
		new Message("Benutzer $username erstellt", $m);
	}
	
	function userCredentials($username, $password) {
		$m = "<br />Die Zugangsdaten konnten dem Benutzer nicht zugestellt werden ";
		$m .= "da keine E-Mail-Adresse hinterlegt ist oder die E-Mail nicht ";
		$m .= "versandt werden konnte. Bitte teile dem Benutzer folgende ";
		$m .= "Zugangsdaten mit:<br /><br />";
		$m .= "Benutzername <strong>$username</strong><br />";
		$m .= "Passwort <strong>$password</strong>";
		new Message("Benutzer $username erstellt", $m);
	}
	
	function integration() {
		Writing::h2(Lang::txt("contacts_integration_header"));
		Writing::p(Lang::txt("contacts_integration_text"));
		?>
		<form method="POST" action="<?php echo $this->modePrefix(); ?>integration_process">
		<div class="start_box_table">
			<div class="start_box_row">
				<div class="start_box">
					<div class="start_box_heading">Mitglieder</div>
					<div class="start_box_content">
						<?php
						$grpFilter = isset($_GET["group"]) ? $_GET["group"] : null;
						$members = $this->getData()->getMembers($grpFilter);
						$group = new GroupSelector($members, array(), "member");
						$group->setNameColumns(array("name", "surname"));
						echo $group->write();
						?>
					</div>
				</div>
				<div class="start_box">
					<div class="start_box_heading">Proben</div>
					<div class="start_box_content">
						<?php
						$rehearsals = $this->getData()->adp()->getFutureRehearsals();
						$group = new GroupSelector($rehearsals, array(), "rehearsal");
						$group->setNameColumn("begin");
						$group->setCaptionType(FieldType::DATE);
						echo $group->write();
						?>
					</div>
					<div class="start_box_heading">Probenphasen</div>
					<div class="start_box_content">
						<?php 
						$phases = $this->getData()->getPhases();
						$group = new GroupSelector($phases, array(), "rehearsalphase");
						echo $group->write();
						?>
					</div>
				</div>
				<div class="start_box">
					<div class="start_box_heading">Auftritte</div>
					<div class="start_box_content">
						<?php
						$concerts = $this->getData()->adp()->getFutureConcerts();
						$group = new GroupSelector($concerts, array(), "concert");
						$group->setNameColumn("begin");
						$group->setCaptionType(FieldType::DATE);
						echo $group->write();
						?>
					</div>
					<div class="start_box_heading">Abstimmungen</div>
					<div class="start_box_content">
						<?php
						$votes = $this->getData()->getVotes();
						$group = new GroupSelector($votes, array(), "vote");
						echo $group->write();
						?>
					</div>
				</div>
			</div>
		</div>
		
		<input type="hidden" name="group" value="<?php echo isset($_GET["group"]) ? $_GET["group"] : ""; ?>" />
		<input type="submit" value="speichern" />
		</form>
		<?php
	}
	
	function contactImport() {
		$form = new Form("Kontaktdaten Import", $this->modePrefix() . "contactImportProcess");
		$form->setMultipart();
		$groups = $this->getData()->adp()->getGroups();
		$form->addElement("Importieren in Gruppe", new GroupSelector($groups, array(), "group"));
		$form->addElement("VCard Datei", new Field("vcdfile", "", FieldType::FILE));
		$form->write();
	}
	
	function importVCardSuccess($message) {
		new Message("VCard Import", $message);
	}
	
	function gdprReport() {
		Writing::h1("Datenauszug");
		
		// fetch contact and user details
		$contact = $this->getData()->getContact($_GET["id"]);
		$cid = $contact["id"];
		$user = $this->getData()->adp()->getUserForContact($cid);
		$uid = $user["id"];
		
		// the contact is a member of these groups
		$groups = $this->getData()->getContactGroups($cid);
		
		// report creation line
		Writing::p("Erstellt am: " . date("d.m.Y H:i:s") . " für " . $contact["surname"] . ", " . $contact["name"]);
		
		// personal information
		Writing::h2("Personendaten");
		$dv = new Dataview();
		$dv->autoAddElements($contact);
		$dv->autoRename($this->getData()->getFields());
		$dv->removeElement("Adresse");
		$dv->removeElement("instrument");
		$dv->renameElement("instrumentname", "Instrument");
		$dv->renameElement("street", "Straße");
		$dv->renameElement("city", "Ort");
		$dv->renameElement("zip", "PLZ");
		$dv->write();
		
		if($uid != NULL && $uid > 0) {
			// Votes: participation
			Writing::h2("Abstimmungen");
			Writing::p("Die Person hat an folgenden Abstimmungen teilgenommen:");
			
			$votes = $this->getData()->adp()->getUsersVotesAll($uid);
			$voteList = new Plainlist($votes);
			$voteList->write();
			Writing::p("Zum Zwecke der Auswertung des Abstimmungsergebnisses wurden Daten erfasst und verarbeitet.");
			
			// Tasks
			Writing::h2("Aufgaben");
			Writing::p("Die Person war für folgenden Aufgaben zuständig:");
			$tasks = $this->getData()->adp()->getUserTasks($uid);
			$taskList = new Plainlist($tasks);
			$taskList->setNameField("title");
			$taskList->write();
			Writing::p("Zum Zwecke der Zuordnung von Aufgaben zu Mitgliedern wurden die Daten erfasst und verarbeitet.");
		}
		
		// Concerts: participation
		Writing::h2("Auftritte");
		/*
		 * concert_user: participation
		 * concert_contact: invitation
		 */
		Writing::p("Die Person war für folgende Auftritte eingeladen:");
		$concertInvitations = $this->getData()->getConcertInvitations($cid);
		$concertInvitationList = new Plainlist($concertInvitations);
		$concertInvitationList->setNameField("title");
		$concertInvitationList->write();
		
		if($uid != NULL && $uid > 0) {
			Writing::p("Die Person hat für folgende Auftritte ihre Anwesenheit eingetragen:");
			$concertParticipation = $this->getData()->getConcertParticipation($uid);
			$concertParticipationList = new Plainlist($concertParticipation);
			$concertParticipationList->setNameField("title");
			$concertParticipationList->write();
		}
		
		Writing::p("Zum Zwecke der Auftrittsorganisation wurde die Anwesenheitsabfrage sowie die Einladung zu Auftritten erfasst und verarbeitet.");
		
		// Rehearsals: participation
		Writing::h2("Proben");
		/*
		 * rehearsal_contact: invitation
		 * rehearsal_user: participation
		 * rehearsalphase_contact: invitation
		 */
		Writing::p("Die Person war zu folgenden Proben eingeladen:");
		$rehearsalInvitations = $this->getData()->getRehearsalInvitations($cid);
		$rehearsalInvitationsList = new Plainlist($rehearsalInvitations);
		$rehearsalInvitationsList->setNameField("begin");
		$rehearsalInvitationsList->write();
		
		if($uid != NULL && $uid > 0) {
			Writing::p("Die Person hat ihre Teilnahme zu folgenden Proben angegeben:");
			$rehearsalParticipation = $this->getData()->getRehearsalParticipation($uid);
			$rehearsalParticipationList = new Plainlist($rehearsalParticipation);
			$rehearsalParticipationList->setNameField("begin");
			$rehearsalParticipationList->write();
		}
		
		Writing::p("Die Person war zu folgenden Probenphasen eingeladen:");
		$phaseInvitations = $this->getData()->getRehearsalphaseInvitations($cid);
		$phaseInvitationsList = new Plainlist($phaseInvitations);
		$phaseInvitationsList->write();
		
		// Tours: participation
		Writing::h2("Touren");
		Writing::p("Die Person war zur Teilnahme an folgenden Touren eingeladen:");
		$tourInvitations = $this->getData()->getTourInvitations($cid);
		$tourInvitationsList = new Plainlist($tourInvitations);
		$tourInvitationsList->write();
	}
	
	function gdprReportOptions() {
		$this->backToViewButton($_GET["id"]);
		$this->buttonSpace();
		
		$prt = new Link("javascript:window.print();", Lang::txt("print"));
		$prt->addIcon("printer");
		$prt->write();
	}
	
	function gdprOk() {
		Writing::h1("Einverständniserklärung der Kontakte");
		Writing::p("In der folgenden Tabelle sind die Kontakte und deren Einverständniserklärungsstatus gezeigt:");
		
		$contacts = $this->getData()->getContactGdprStatus();
		$table = new Table($contacts);
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("gdpr_ok", "Einverständnis");
		$table->setColumnFormat("gdpr_ok", "BOOLEAN");
		$table->removeColumn("contact_id");
		$table->removeColumn("user_id");
		$table->write();
	}
	
	function gdprOkOptions() {
		$this->backToStart();
	
		$get = new Link($this->modePrefix() . "getGdprOk", "Einverständnis anfordern");
		$get->addIcon("question");
		$get->write();
	
		$del = new Link($this->modePrefix() . "gdprNOK", "Kontakte ohne Einverständnis löschen");
		$del->addIcon("cancel");
		$del->write();
	}
	
	function getGdprOk() {
		/*
		 * Users should login and accept - otherwise not usable
		 * External contacts get an email with a link to accept showing a nice "thanks" page
		 */
		Writing::h1("Einverständnis der Kontakte");
		?>
		<p>Alle Benutzer, die sich am System anmelden können, sollten in einer internen E-Mail zur Anmeldung aufgefordert werden.
		Nach der Anmeldung müssen die Benutzer der Datenschutzerklärung zustimmen. Externe Kontakte werden mit folgender Nachricht
		um ihre Zustimmung zur Verarbeitung personenbezogener Daten gebeten. Die Nachricht lautet:</p>
		
		<div style="margin: 10px 20px;">
		<?php
		require_once "data/gdpr_mail.php";
		
		echo $this->getData()->getSysdata()->getCompany();
		?>
		</div>
		<?php
	}
	
	function getGdprOkOptions() {
		$this->backToStart();
		
		$send = new Link($this->modePrefix() . "gdprSendMail", "Mails an externe Kontakte senden");
		$send->addIcon("kommunikation");
		$send->write();
	}
	
	function gdprNOK() {
		Writing::p("Die Daten der Benutzer ohne Einverständnis wurden gelöscht.");
	}
}

?>