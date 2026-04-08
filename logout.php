<?php
require_once 'config.php';

// Clear session
session_destroy();

// Redirect to home page
header('Location: ' . BASE_URL . '/index.php');
exit;
