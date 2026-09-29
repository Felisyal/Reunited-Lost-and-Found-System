<?php

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "reunited_db"
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "
    UPDATE lost_items
    SET status = 'Expired'
    WHERE expiration_date IS NOT NULL
      AND expiration_date < CURDATE()
      AND status IN ('Approved', 'Ready for Claim')
";

$sql = "
    UPDATE found_items
    SET status = 'Expired'
    WHERE expiration_date IS NOT NULL
      AND expiration_date < CURDATE()
      AND status IN ('Approved', 'Ready for Claim')
";

if ($conn->query($sql)) {
    echo "Expired items updated successfully.";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>