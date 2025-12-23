<?php
require 'config.php';
session_destroy();
header("Location: accueil.php");
exit;
?>