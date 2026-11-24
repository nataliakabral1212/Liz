<?php
$host = 'localhost';
$baseDatos = 'peliculas';
$usuario = 'root';
$contrasena = '';
$pdo = new PDO(
    "mysql:host=$host;dbname=$baseDatos;charset=utf8mb4",
    $usuario,
    $contrasena,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
