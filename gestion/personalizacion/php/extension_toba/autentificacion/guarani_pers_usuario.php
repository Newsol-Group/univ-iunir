<?php
/**
 * Se realiza el require de la clase de núcleo de la que se extiende, debido a que estas clases
 * de autentificación de usuarios en el login se invocan previamente a la carga de los autoloads.
 */
require_once('extension_toba/autentificacion/guarani_usuario.php');
class guarani_pers_usuario extends guarani_usuario
{
}
?>
