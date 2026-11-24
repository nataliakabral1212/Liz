<?php
header('Content-Type: application/json; charset=utf-8');

$datos = json_decode(file_get_contents('php://input'), true);
if (!is_array($datos) || !isset($datos['resultados']) || !is_array($datos['resultados'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Los resultados enviados no son válidos.']);
    exit;
}

$busqueda = isset($datos['busqueda']) && is_string($datos['busqueda']) ? trim($datos['busqueda']) : '';
$peliculas = [];
foreach ($datos['resultados'] as $resultado) {
    if (!is_array($resultado)) {
        continue;
    }

    $imdbId = isset($resultado['imdbID']) && is_string($resultado['imdbID']) ? trim($resultado['imdbID']) : '';
    $titulo = isset($resultado['Title']) && is_string($resultado['Title']) ? trim($resultado['Title']) : '';
    if ($imdbId === '' || $titulo === '') {
        continue;
    }

    $peliculas[] = [
        $imdbId,
        $titulo,
        isset($resultado['Year']) && is_string($resultado['Year']) ? $resultado['Year'] : '',
        isset($resultado['Type']) && is_string($resultado['Type']) ? $resultado['Type'] : '',
        isset($resultado['Poster']) && is_string($resultado['Poster']) ? $resultado['Poster'] : '',
        $busqueda
    ];
}

if (!$peliculas) {
    http_response_code(400);
    echo json_encode(['error' => 'No hay resultados válidos para guardar.']);
    exit;
}

try {
    require_once __DIR__ . '/conexion.php';
    $pdo->beginTransaction();
    $insertar = $pdo->prepare('INSERT INTO resultados (imdb_id, titulo, anio, tipo, poster, busqueda) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($peliculas as $pelicula) {
        $insertar->execute($pelicula);
    }
    $pdo->commit();
    echo json_encode(['guardados' => count($peliculas)]);
} catch (PDOException $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'No se pudieron guardar los resultados.']);
}
