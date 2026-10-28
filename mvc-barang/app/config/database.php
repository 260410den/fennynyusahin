<?php
declare(strict_types=1);

function databaseConnection(): PDO
{
    $host = 'localhost';
    $database = 'db_toko';
    $username = 'root';
    $password = 'root';
    $port = '3306';

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
