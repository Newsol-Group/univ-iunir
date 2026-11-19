<?php
	toba::memoria()->desactivar_reciclado();
	
	$path = toba::memoria()->get_parametro('path');
	$nombre = toba::memoria()->get_parametro('nombre');
	
	header ("Content-Disposition: attachment; filename=".$nombre." "); 
	header ("Content-Type: application/octet-stream");
	header ("Content-Length: ".filesize($path));
	
	readfile($path);
?>