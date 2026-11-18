<?php

echo "Admin Password Hash:<br>";
echo password_hash("Admin@12345", PASSWORD_DEFAULT);

echo "<br><br>";

echo "Pharmacist Password Hash:<br>";
echo password_hash("Pharmacist@12345", PASSWORD_DEFAULT);

?>