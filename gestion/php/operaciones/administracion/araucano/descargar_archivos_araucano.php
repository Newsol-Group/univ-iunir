<?php
	toba::memoria()->desactivar_reciclado();

	$anio_academico = toba::memoria()->get_parametro('anio_academico');
	$estudiantes_ingreso = toba::memoria()->get_parametro('estudiantes_ingreso');
	$responsable_academica = toba::memoria()->get_parametro('responsable_academica');
	
	$nombre = !empty($responsable_academica) ? "araucano_$responsable_academica.zip" : "araucano.zip";

	$path = isset($estudiantes_ingreso) ? toba::proyecto()->get_path_temp().'/araucano/estudiantes_ingreso/'.$anio_academico.'/'.$nombre : toba::proyecto()->get_path_temp().'/araucano/'.$anio_academico.'/'.$nombre;
	
	header ("Content-Disposition: attachment; filename=".$nombre." "); 
	header ("Content-Type: application/octet-stream");
	header ("Content-Length: ".filesize($path));

	echo readfile($path);