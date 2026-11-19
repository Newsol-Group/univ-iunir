<?php

class constructor_arbol_plan extends constructor_arbol
{	
	protected function construir_elemento($clase, $info)
	{
		$nombre = $info['nombre'];
		// Se definió que para los Módulos de cualquier subtipo también se antepone el código al nombre, como para las Actividades.
		if (in_array($info['entidad_tipo'], [elemento::tipo_actividad, elemento::tipo_modulo]) && isset($info['codigo'])) {
			$nombre = "({$info['codigo']}) {$info['nombre']}";
		}
		
		$e = new $clase($info['elemento'], $nombre, $info);
		$this->elementos[$info['elemento']] = $e;
		return $e;
	}	
}
?>