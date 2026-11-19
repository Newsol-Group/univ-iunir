<?php
/**
 * Clase de Consulta Usuarios Personalizada. Se esquematiza la personalización de este CO, debido a que como se 
 * consulta esta clase con anterioridad a la carga de los autoloads, es necesario realizar explícitamente en la 
 * misma los require de todas las clases que se utilicen, incluyendo la clase de consulta del core, co_usuarios, 
 * de la que se extiende.
 */

$co_usuarios_file_gestion = __DIR__ . '/../../../../../php/nucleo/_lib/usuarios/co_usuarios.php';
if (file_exists($co_usuarios_file_gestion)) {
	require_once $co_usuarios_file_gestion;
}

class co_usuarios_pers extends co_usuarios
{
}
?>
