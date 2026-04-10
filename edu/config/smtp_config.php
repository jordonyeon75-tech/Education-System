<?php
// Fetch SMTP configuration from environment variables
$smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';  
$smtpUsername = getenv('SMTP_USERNAME') ?: 'yeohxinpei@gmail.com'; 
$smtpPassword = getenv('SMTP_PASSWORD') ?: 'dqik xris wxej grnk';
$smtpPort = getenv('SMTP_PORT') ?: 587;

?>