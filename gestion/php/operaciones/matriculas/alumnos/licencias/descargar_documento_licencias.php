<?php
	toba::memoria()->desactivar_reciclado();

	$licencia_doc = toba::memoria()->get_parametro('licencia_doc');
	$documento_lic = toba::consulta_php('co_licencias')->get_licencia_doc($licencia_doc);

	header("Pragma: no-cache");
	header("Expires: 0");
	header('Content-type: application/octet-stream');
	header("Content-Description: {$documento_lic['nombre_archivo']}");
	header("Content-Disposition: attachment; filename=\"{$documento_lic['nombre_archivo']}\";");

	echo stream_get_contents($documento_lic['archivo']);
?>