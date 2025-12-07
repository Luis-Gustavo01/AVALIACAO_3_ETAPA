<?php
session_start();

header('Location: controllers/AuthController.php?action=login');
exit;
?>