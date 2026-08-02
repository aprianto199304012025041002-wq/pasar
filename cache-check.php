<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
require_once __DIR__ . "/config.php";
echo "<h3>Config constants</h3>";
echo "APP_BASE_URL: " . (defined("APP_BASE_URL") ? APP_BASE_URL : "TIDAK ADA") . "<br>";
echo "SSO_URL: " . (defined("SSO_URL") ? SSO_URL : "TIDAK ADA") . "<br>";
echo "SSO_CLIENT_ID: " . (defined("SSO_CLIENT_ID") ? SSO_CLIENT_ID : "TIDAK ADA") . "<br>";
echo "SSO_CLIENT_SECRET_PLAIN: " . (defined("SSO_CLIENT_SECRET_PLAIN") ? "ADA" : "TIDAK ADA") . "<br>";
echo "SSO_PUBLIC_KEY: " . (defined("SSO_PUBLIC_KEY") ? "ADA " . strlen(SSO_PUBLIC_KEY) . " chars" : "TIDAK ADA") . "<br>";
echo "DB_HOST: " . (defined("DB_HOST") ? DB_HOST : "TIDAK ADA") . "<br>";
