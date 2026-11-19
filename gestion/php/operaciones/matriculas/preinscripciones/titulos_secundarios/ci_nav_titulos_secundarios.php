<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Preinscripciones
 */
class ci_nav_titulos_secundarios extends ci_nav_sin_listado_inicial
{
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		if ($this->pantalla('edicion')->existe_evento('eliminar') && !$this->cn()->entidad_es_nueva()) {			
			$datos = $this->cn()->entidad()->get_datos();
			if (guarani::co('co_titulos')->existe_estudio_titulo($datos['titulo'])) {
				$pantalla->eliminar_evento('eliminar');
			}			
		}
	}
}