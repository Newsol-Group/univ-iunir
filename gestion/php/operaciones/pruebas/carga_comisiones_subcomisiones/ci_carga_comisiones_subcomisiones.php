<?php
class ci_carga_comisiones_subcomisiones extends guarani_pers_ci 
{
	function conf()
	{
		echo('start');
		$i = 0;
		while ($i <= 100) {
			$this->cn()->cargar(array('comision' => 1));
			$this->cn()->entidad()->dep('datos')->forzar_insercion();
			$this->cn()->guardar();
			$this->cn()->resetear();
			$i++;
		}
		echo('end');
		//$this->cn()->entidad()->dep('datos')->dump_contenido();
	}
}

?>