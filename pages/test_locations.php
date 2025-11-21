<?php
// Test if locations are loading correctly

$locations = require '../config/locations.php';

echo "<h1>Locations Test</h1>";
echo "<p>Number of locations: " . count($locations) . "</p>";
echo "<hr>";
echo "<h2>All Locations:</h2>";
echo "<ul>";
foreach ($locations as $location) {
    echo "<li>" . htmlspecialchars($location) . "</li>";
}
echo "</ul>";

echo "<hr>";
echo "<h2>Dropdown Test:</h2>";
echo '<select>';
echo '<option value="">Select a location</option>';
foreach ($locations as $location) {
    echo '<option value="' . htmlspecialchars($location) . '">' . htmlspecialchars($location) . '</option>';
}
echo '</select>';
?>
