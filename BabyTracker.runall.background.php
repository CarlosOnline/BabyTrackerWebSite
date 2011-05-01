<?php

$_GET["testaction"]="runall.background";
$_GET["verbose"]=1;

require_once("BabyTracker.process.php");

while(true)
{
    ProcessLogTable("", "", 0);
    DumpLogTableResults();
    sleep(12 * 60 * 60);
}


?>