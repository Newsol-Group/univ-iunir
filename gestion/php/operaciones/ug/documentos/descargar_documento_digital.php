<?php
	toba::memoria()->desactivar_reciclado();

	$documento = toba::memoria()->get_parametro('documento');
	$documento_digital = toba::consulta_php('co_documentos')->get_documento_digital($documento);

	header("Pragma: no-cache");
	header("Expires: 0");
	header('Content-type: application/octet-stream');
	header("Content-Description: {$documento_digital['nombre_archivo']}");
	header("Content-Disposition: attachment; filename=\"{$documento_digital['nombre_archivo']}\";");

	echo stream_get_contents($documento_digital['archivo']);
?>