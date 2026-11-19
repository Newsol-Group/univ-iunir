<?php
interface cache_memoria
{
	static function instancia();
	
	function generar_clave($clave);
	
	function existe($clave);
	
	function guardar($clave, $datos, $expiracion = 0);
	
	function buscar($clave);
	
	function eliminar($clave);
}
?>