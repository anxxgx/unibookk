<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion = new mysqli(
        "localhost",
        "root",
        "",
        "unibook"
    );

    $conexion->set_charset("utf8mb4");
} catch (mysqli_sql_exception $error) {
    // El detalle técnico queda en el registro del servidor, no en pantalla.
    error_log('UniBook: error de conexión a la base de datos: ' . $error->getMessage());
    http_response_code(500);
    exit('No fue posible conectar con la base de datos. Verifica que MySQL esté iniciado.');
}
