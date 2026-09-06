<?php
require_once(__DIR__ . '/includes/portal_bootstrap.inc');
$_SESSION = array();
session_destroy();
header('Location: login.php');
