<?php
include_once("../database/dbpersonhours.php");
include_once("../database/dbRoles.php");
include_once("../database/dbRoleEvents.php");
include_once("../database/reports.php");


$r = hoursPerRoleAllTime([100,2,3,4,5]);
var_dump($r);

