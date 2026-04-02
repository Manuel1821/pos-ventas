<?php
declare(strict_types=1);

// Prueba para detectar si `session_start()` se queda colgado en el servidor.
session_start();
header('Content-Type: text/plain; charset=utf-8');
echo 'session-ok';

