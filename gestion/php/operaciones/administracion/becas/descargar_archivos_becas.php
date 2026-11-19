<?php
	toba::memoria()->desactivar_reciclado();

	$becas = toba::memoria()->get_parametro('becas');
	$anio_academico = toba::memoria()->get_parametro('anio_academico');
	$responsable_academica = toba::memoria()->get_parametro('responsable_academica');

	$becas_dir = ($becas == beca::becas_manuel_belgrano) ? beca::becas_manuel_belgrano_dir :  beca::becas_progresar_dir;
	$nombre = !empty($responsable_academica) ? "becas_$responsable_academica.zip" : "becas.zip";
	$path = toba::proyecto()->get_path_temp()."/becas/$becas_dir/$anio_academico/$nombre";

	header ("Content-Disposition: attachment; filename=".$nombre." "); 
	header ("Content-Type: application/octet-stream");
	header ("Content-Length: ".filesize($path));

	echo readfile($path);