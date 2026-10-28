<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

session_destroy();

// Al cerrar sesión se vuelve a la página pública principal.
header("Location: index.php?accion=landing");

exit();
