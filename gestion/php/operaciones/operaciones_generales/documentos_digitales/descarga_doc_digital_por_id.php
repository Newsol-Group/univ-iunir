<?php
	toba::memoria()->desactivar_reciclado();
	
	$requisito_digital = toba::memoria()->get_parametro('requisito_digital');
	$datos_requisito = toba::consulta_php('co_requisitos')->get_info_requisito_digital($requisito_digital);
	$tipo_doc_digital = $datos_requisito['tipo_documento_digital'];
	$archivo = guarani_cliente_rdi::get_contenido_documento($datos_requisito['id_documento'], $tipo_doc_digital);
	$nombre = toba::proyecto()->get_www_temp(md5(time()));
	file_put_contents($nombre['path'], $archivo['contents']);
	$path = $nombre['path'];
	$nombre_descarga = md5($path);
	
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$mime_type = finfo_file($finfo, $path);
	finfo_close($finfo);

	header('Content-type: '.$mime_type);
	header('Content-Description: File Transfer');
	header("Content-Disposition: attachment; filename=\"$nombre_descarga\"");
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: no-cache');
	header('Content-Length: ' . filesize($path));
	ob_clean();
	flush();
	readfile($path);
?>