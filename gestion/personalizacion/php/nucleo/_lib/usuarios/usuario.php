<?php 
  class usuario extends usuario_nucleo { 


	static function get_usuario_default($apellido, $nombre, $nro_doc)
	{
		$usuario = $nro_doc;
		return $usuario;
	}

  } 
?>