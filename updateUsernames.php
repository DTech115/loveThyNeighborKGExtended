<?php
    ini_set("display_errors",1);
    error_reporting(E_ALL);
    include_once('domain/Person.php');
    include_once('database/dbPersons.php');

    session_start();

    $UserNameUpdateSuccess =updateUsername();
?>