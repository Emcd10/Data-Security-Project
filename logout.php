<?php
//Code that simply logs you out and then sends you back to the login screen
session_start();
session_destroy();
header("Location: auth.php");
exit;
?>
