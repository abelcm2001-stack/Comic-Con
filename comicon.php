<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

function calcularPrecioBase(string $tipo): int
{
    switch ($tipo) {
        case "general":
            return 30;
        case "vip":
            return 60;
        case "infantil":
            return 10;
        default:
            throw new Exception("El tipo de entrada no es válido");
    }
}

function procesarInscripcion(string $nombre, string $tipo, array $talleresSeleccionados): array
{
    $talleresDisponibles = [
        "torneo-videojuegos" => 10,
        "taller-cosplay" => 15,
        "rol-en-vivo" => 5
    ];

    $precioBase = calcularPrecioBase($tipo);
    $talleresConPrecio = [];
    $precioTotal = $precioBase;

    foreach ($talleresSeleccionados as $taller) {
        if (!is_string($taller) || !isset($talleresDisponibles[$taller])) {
            throw new Exception("Uno de los talleres seleccionados no es válido");
        }

        $precioTaller = $talleresDisponibles[$taller];
        $talleresConPrecio[$taller] = $precioTaller;
        $precioTotal += $precioTaller;
    }

    return [
        "nombre" => $nombre,
        "tipo" => $tipo,
        "precioBase" => $precioBase,
        "talleres" => $talleresConPrecio,
        "precioTotal" => $precioTotal
    ];
}

$inscripcionProcesada = false;
$mensajeError = "";
$errorTipoEntrada = false;
$resultado = [];
$nombreFormulario = "";
$edadFormulario = "";
$tipoEntradaFormulario = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Recogida de datos, si no se marca ningún taller el array está vacío.
        $nombre = $_POST["nombre"] ?? "";
        $edadRecibida = $_POST["edad"] ?? "";
        $tipoEntrada = $_POST["entrada"] ?? "";
        $talleresSeleccionados = $_POST["talleres"] ?? [];

        if (is_string($nombre)) {
            $nombreFormulario = $nombre;
        }
        if (is_string($edadRecibida)) {
            $edadFormulario = $edadRecibida;
        }
        if (is_string($tipoEntrada)) {
            $tipoEntradaFormulario = $tipoEntrada;
        }

        if (!is_string($nombre) || !is_string($tipoEntrada)) {
            throw new Exception("Los datos de la inscripción no son válidos");
        }

        if (!is_array($talleresSeleccionados)) {
            throw new Exception("Los talleres seleccionados no son válidos");
        }

        if (!is_numeric($edadRecibida)) {
            throw new Exception("La edad debe ser un número entero positivo");
        }

        $edad = (int) $edadRecibida;

        if ($edad <= 0 || $edad != $edadRecibida) {
            throw new Exception("La edad debe ser un número entero positivo");
        }

        if ($edad < 18 && $tipoEntrada === "vip") {
            $errorTipoEntrada = true;
            throw new Exception("La entrada VIP está restringida a mayores de edad");
        }

        if ($edad >= 18 && $tipoEntrada === "infantil") {
            $errorTipoEntrada = true;
            throw new Exception("La entrada infantil es solo para menores de 18 años");
        }

        $resultado = procesarInscripcion($nombre, $tipoEntrada, $talleresSeleccionados);
        $inscripcionProcesada = true;
    } catch (Exception $e) {
        $mensajeError = $e->getMessage();
    } finally {
        if ($inscripcionProcesada) {
            $estado = "Inscripción procesada correctamente";
        } else {
            $estado = "Error al procesar inscripción";
        }
        error_log("[" . date("Y-m-d H:i:s") . "] " . $estado);
    }
}
?>
<!Doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Formulario</title>
  </head>
  <body>
    <h1>Formulario de inscripcion Comic-Con</h1>

    <form action="comicon.php" method="POST">
      <p>
        <label for="nombre">Nombre completo:</label>
        <input id="nombre" name="nombre" type="text" value="<?= htmlspecialchars($nombreFormulario, ENT_QUOTES, "UTF-8") ?>" required>
      </p>

      <p>
        <label for="edad">Edad:</label>
        <input id="edad" name="edad" type="number" min="0" max="120" value="<?= htmlspecialchars($edadFormulario, ENT_QUOTES, "UTF-8") ?>" required>
      </p>

      <p>
        <label for="entrada">Tipo de entrada:</label>
        <select id="entrada" name="entrada" required>
          <option value="selected disabled">Selecciona una entrada</option>
          <option value="general" <?= $tipoEntradaFormulario === "general" ? "selected" : "" ?>>General (30 euros)</option>
          <option value="vip" <?= $tipoEntradaFormulario === "vip" ? "selected" : "" ?>>VIP (60 euros)</option>
          <option value="infantil" <?= $tipoEntradaFormulario === "infantil" ? "selected" : "" ?>>Infantil (10 euros)</option>
        </select>
        <?php if ($_SERVER["REQUEST_METHOD"] === "POST" && $errorTipoEntrada): ?>
          <span><?= htmlspecialchars($mensajeError, ENT_QUOTES, "UTF-8") ?></span>
        <?php endif; ?>
      </p>

      
        <legend>Talleres especiales:</legend>
        <label>
          <input name="talleres[]" type="checkbox" value="torneo-videojuegos">
          Torneo de Videojuegos (10 euros)
        </label>
        <br>
        <label>
          <input name="talleres[]" type="checkbox" value="taller-cosplay">
          Taller de Cosplay (15 euros)
        </label>
        <br>
        <label>
          <input name="talleres[]" type="checkbox" value="rol-en-vivo">
          Zona de Rol en Vivo (5 euros)
        </label>
    

      <p><button type="submit">Confirmar inscripcion</button></p>
    </form>

    <?php if ($_SERVER["REQUEST_METHOD"] === "POST" && $inscripcionProcesada): ?>
      <h2>Resguardo de inscripción</h2>
      <p>Nombre: <?= htmlspecialchars($resultado["nombre"], ENT_QUOTES, "UTF-8") ?></p>
      <p>Tipo de entrada: <?= htmlspecialchars($resultado["tipo"], ENT_QUOTES, "UTF-8") ?></p>
      <p>Precio base: <?= $resultado["precioBase"] ?> €</p>
      <p>
        Talleres seleccionados:
        <?php if (count($resultado["talleres"]) === 0): ?>
          No se han seleccionado talleres.
        <?php endif; ?>
      </p>
      <?php if (count($resultado["talleres"]) > 0): ?>
        <ul>
          <?php foreach ($resultado["talleres"] as $taller => $precio): ?>
            <li><?= htmlspecialchars($taller, ENT_QUOTES, "UTF-8") ?>: <?= $precio ?> €</li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <p>Precio total: <?= $resultado["precioTotal"] ?> €</p>
    <?php elseif ($_SERVER["REQUEST_METHOD"] === "POST" && !$errorTipoEntrada): ?>
      <p>Error: <?= htmlspecialchars($mensajeError, ENT_QUOTES, "UTF-8") ?></p>
    <?php endif; ?>
  </body>
</html>
