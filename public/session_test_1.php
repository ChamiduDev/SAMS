<?php
// public/session_test_1.php

echo "<h1>Session Test: Page 1</h1>";

// Start a new session
session_start();

// Set a test variable in the session
$_SESSION['test_data'] = 'Hello, this is a test!';

// Confirm that the variable was set in the current script
if (isset($_SESSION['test_data'])) {
    echo "<p>Session variable was set successfully on this page.</p>";
    echo "<p>Value: " . htmlspecialchars($_SESSION['test_data']) . "</p>";
} else {
    echo "<p style='color:red;'>ERROR: Failed to set session variable on this page.</p>";
}

// Provide a link to the second page to check if the data persists
echo "<hr>";
echo "<p>Now, click the link below to see if the session data persists on another page.</p>";
echo "<a href='session_test_2.php'>Go to Page 2</a>";

?>
