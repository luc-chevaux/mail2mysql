<?php

/**
 * email DB PARSER
 * @author Luca Martini
 * @version 1.0.0
 * @year 2011
 */

// --------------------
// Do some PHP settings
// --------------------

error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", "on");
ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . "./library" . PATH_SEPARATOR . "./application/models");
ini_set("display_startup_errors", "on");
ini_set("display_errors", "on");
date_default_timezone_set("Europe/Rome");

// --------------------------------------
// Require the Zend Class Loader and Init
// --------------------------------------

require "Zend/Loader.php";
Zend_Loader::registerAutoload();

// ----------------------
// Load custom library
// ---------------------------------

require "Libs/dldControllerPluginNoRoute.php";
require "Libs/dldExtractEmail.php";
require "Libs/dldCheckBase64Encode.php";
require "Libs/dldCheckWinFileSystem.php";

// ----------------------------------
// Define base url to start rewriting
// ----------------------------------

define ("BASE_URL", str_replace("index.php", "", $_SERVER ['PHP_SELF']));

// -------------------
// Connect to database
// -------------------

$config = new Zend_Config_Xml ("./config/config.xml", "production");

try {
    $db = Zend_Db::factory("mysqli", array(
        "host" => $config->database->host,
        "username" => $config->database->username,
        "password" => $config->database->password,
        "dbname" => $config->database->name
    ));

    $mail = new Zend_Mail_Storage_Pop3 (array(
        'host' => 'mailcon.tesoro.it',
        'user' => 'ticket.dld',
        'password' => 'prima'
    ));

    $db->getConnection();
} catch (Zend_Db_Adapter_Exception $e) {
    die ("Zend_Db_Adapter_Exception: " . $e->getMessage());
} catch (Zend_Exception $e) {
    die ("Zend_Exception" . $e->getMessage());
}

/* ------------------------------------- */
/* Register the view we are going to use */
/* ------------------------------------- */

$view = new Zend_View ();
$view->setScriptPath("./application/views/scripts");

// -----------------
// Load the registry
// -----------------

Zend_Registry::set("db", $db);
Zend_Registry::set("mail-server", $mail);
Zend_Registry::set("view", $view);

// ---------------------------
// Setup Zend_Controller_Front
// ---------------------------

$front = Zend_Controller_Front::getInstance();

$front->setDefaultControllerName("Index");
$front->setControllerDirectory("./application/controllers");
$front->throwExceptions(true);
$front->registerPlugin(new dldControllerPluginNoRoute());
$front->dispatch();
?>
