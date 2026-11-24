<?php
require_once __DIR__ . '/conexion.php';
$consulta = $pdo->query('SELECT imdb_id, titulo, anio, tipo, poster, busqueda, fecha FROM resultados ORDER BY fecha DESC, id DESC LIMIT 20');
$guardados = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscador de películas</title>
    <style>
        body{font-family:Arial,sans-serif;max-width:1000px;margin:36px auto;padding:0 18px;color:#20242a;background:#f5f7fa}
        h1,h2{color:#172b4d}
        section{background:#fff;padding:22px;border-radius:8px;margin:20px 0;box-shadow:0 2px 8px #172b4d12}
        form{display:flex;gap:10px;flex-wrap:wrap}
        input,select,button{padding:10px;border:1px solid #cbd2dc;border-radius:5px;font-size:15px}
        input{flex:1;min-width:200px}
        button{background:#2457a7;color:#fff;border:0;cursor:pointer}
        button:disabled{background:#9aa8bb;cursor:not-allowed}
        .tabla{overflow-x:auto}
        table{width:100%;border-collapse:collapse;margin-top:14px}
        th,td{text-align:left;padding:10px;border-bottom:1px solid #e3e7ed}
        th{background:#eef2f8}
        .poster{width:45px;height:auto}
        #mensaje{min-height:20px;color:#a12622}
    </style>
</head>
<body>
    <h1>Buscador de películas</h1>
    <section>
        <form id="formulario">
            <input id="titulo" type="search" placeholder="Escribe un título" required>
            <select id="tipoBusqueda" aria-label="Tipo de búsqueda">
                <option value="s">Títulos que coincidan</option>
                <option value="t">Título específico</option>
            </select>
            <button type="submit">Buscar</button>
        </form>
        <p id="mensaje" role="status"></p>
        <button id="guardar" type="button" disabled>Guardar resultados</button>
        <div class="tabla">
            <table>
                <thead>
                    <tr><th>Título</th><th>Año</th><th>Tipo</th><th>imdbID</th><th>Poster</th></tr>
                </thead>
                <tbody id="resultados"></tbody>
            </table>
        </div>
    </section>
    <section>
        <h2>Últimos 20 resultados guardados</h2>
        <div class="tabla">
            <table>
                <thead>
                    <tr><th>Título</th><th>Año</th><th>Tipo</th><th>imdbID</th><th>Búsqueda</th><th>Fecha</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($guardados as $guardado): ?>
                        <tr>
                            <td><?= htmlspecialchars($guardado['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($guardado['anio'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($guardado['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($guardado['imdb_id'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($guardado['busqueda'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($guardado['fecha'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$guardados): ?>
                        <tr><td colspan="6">Todavía no hay resultados guardados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <script>
        const formulario = document.getElementById('formulario');
        const entradaTitulo = document.getElementById('titulo');
        const tipoBusqueda = document.getElementById('tipoBusqueda');
        const tablaResultados = document.getElementById('resultados');
        const botonGuardar = document.getElementById('guardar');
        const mensaje = document.getElementById('mensaje');
        let resultadosActuales = [];

        formulario.addEventListener('submit', async function(evento) {
            evento.preventDefault();
            const titulo = entradaTitulo.value.trim();
            const parametro = tipoBusqueda.value;
            mensaje.textContent = 'Buscando...';
            tablaResultados.replaceChildren();
            resultadosActuales = [];
            botonGuardar.disabled = true;

            try {
                const url = 'https://www.omdbapi.com/?apikey=8efb52d2&' + parametro + '=' + encodeURIComponent(titulo);
                const respuesta = await fetch(url);
                const datos = await respuesta.json();

                if (!respuesta.ok) {
                    throw new Error('No se pudo completar la búsqueda.');
                }
                if (datos.Response === 'False') {
                    mensaje.textContent = datos.Error || 'No se encontraron resultados.';
                    return;
                }

                resultadosActuales = parametro === 's' ? datos.Search : [datos];
                if (!Array.isArray(resultadosActuales) || resultadosActuales.length === 0) {
                    mensaje.textContent = 'No se encontraron resultados.';
                    return;
                }

                resultadosActuales.forEach(function(pelicula) {
                    const fila = document.createElement('tr');
                    [pelicula.Title, pelicula.Year, pelicula.Type, pelicula.imdbID].forEach(function(valor) {
                        const celda = document.createElement('td');
                        celda.textContent = valor || '';
                        fila.appendChild(celda);
                    });
                    const celdaPoster = document.createElement('td');
                    if (pelicula.Poster && pelicula.Poster !== 'N/A') {
                        const imagen = document.createElement('img');
                        imagen.src = pelicula.Poster;
                        imagen.alt = 'Poster de ' + (pelicula.Title || '');
                        imagen.className = 'poster';
                        celdaPoster.appendChild(imagen);
                    }
                    fila.appendChild(celdaPoster);
                    tablaResultados.appendChild(fila);
                });
                mensaje.textContent = '';
                botonGuardar.disabled = false;
            } catch (error) {
                mensaje.textContent = error.message || 'Ocurrió un error al buscar.';
            }
        });

        botonGuardar.addEventListener('click', async function() {
            if (resultadosActuales.length === 0) {
                return;
            }

            botonGuardar.disabled = true;
            mensaje.textContent = 'Guardando...';
            try {
                const respuesta = await fetch('guardar.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({resultados: resultadosActuales, busqueda: entradaTitulo.value.trim()})
                });
                const datos = await respuesta.json();
                if (!respuesta.ok) {
                    throw new Error(datos.error || 'No se pudieron guardar los resultados.');
                }
                window.location.reload();
            } catch (error) {
                mensaje.textContent = error.message || 'Ocurrió un error al guardar.';
                botonGuardar.disabled = false;
            }
        });
    </script>
</body>
</html>
