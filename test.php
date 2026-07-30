<?php

// Include database connection file
require_once __DIR__ . '/database.php';

// Check database connection
if ($conn) {
    echo "<h1 style='color: green;'>Database Connected Successfully ✅</h1>";
    echo "<p>Your PHP project is successfully connected to the ELMS MySQL database.</p>";
} else {
    echo "<h1 style='color: red;'>Database Connection Failed ❌</h1>";
}

?>