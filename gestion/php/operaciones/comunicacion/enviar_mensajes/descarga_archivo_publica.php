<?php
	parse_str(toba::encriptador()->descifrar($_GET[apex_hilo_qs_parametros]), $parametros);
	
	if (empty($parametros['id_archivo'])) {
		die();
	}
	
	toba::memoria()->desactivar_reciclado();

	$datos = guarani::co('co_mensajes')->get_archivo_adjunto($parametros['id_archivo']);
	$nombre = $datos['nombre_archivo'].'.'.$datos['extension'];
	
	header ("Content-Disposition: attachment; filename=\"$nombre\"");
	header ("Content-Type: application/octet-stream");
	header ("Content-Length: ".filesize($datos['path_archivo']));
	
	readfile($datos['path_archivo']);
?>