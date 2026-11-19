<?php
class plan_elemento_gui_modulo_rtf extends plan_elemento_gui_modulo
{
	function __construct($id, $nombre, $datos, $id_padre=null, $id_revision = false)
	{
		if(!isset($datos['subtipo'])) {
			$datos['subtipo'] = elemento::subtipo_modulo_rtf;
		}
		parent::__construct($id, $nombre, $datos, $id_padre, $id_revision);
	}
}
	
?>
