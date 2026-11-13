<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="student_template.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Required headers based on import script
$headers = ['Register No', 'Student Name', 'Gender', 'Mobile No', 'Email', 'DOB', 'Address'];
fputcsv($output, $headers);

// Sample row to guide user
$sample = ['RA20110001', 'John Doe', 'Male', '9876543210', 'john@example.com', '2002-05-15', '123 Main St, City'];
fputcsv($output, $sample);

fclose($output);
exit;
