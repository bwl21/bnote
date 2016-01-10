<?php
/**
 * Initializes System
**/

# Start session
session_start();

# Load all widgets - not automated, due to exclusion of widgets and order
$widgets = array(
	"iwriteable", "box", "dropdown", "dataview", "error", "field",
	"form", "link", "message", "table", "writing", "textwriteable",
	"htmleditor", "imagetable", "filebrowser", "groupselector", "filterbox",
	"list"
);

foreach($widgets as $id => $file) {
	if($isMobile && file_exists($GLOBALS["DIR_WIDGETS_MOBILE"] . $file . ".php")) {
		require($GLOBALS["DIR_WIDGETS_MOBILE"] . $file . ".php");
	}
	else {
		$widget_file = $GLOBALS["DIR_WIDGETS"] . $file . ".php";
		if(file_exists($widget_file)) {
			require($widget_file);
		}
	}
}

# load additional PHP libraries
require_once $GLOBALS["DIR_LIB"] . "simpleimage.php";

# Inizialize System Array
include $GLOBALS["DIR_DATA"] . "systemdata.php";
$system_data = new Systemdata();

# Load language
include "lang.php";

# Logout
if($system_data->getModuleId() === "logout") {
	$_SESSION["user"] = "null";
	unset($_SESSION);
	session_destroy();
	include $GLOBALS["DIR_PRESENTATION_MODULES"] . "logoutview.php";
	exit(0);
}

?>