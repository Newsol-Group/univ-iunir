<?php
/**
 * 
 * @package Encuestas
 */
class ci_nav_modificar_vigencia_encuestas extends ci_navegacion
{
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('encuestas')->set_condicion_fija('contiene');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_encuestas = '';
			if (isset($datos['encuestas'])) {
                            
                                $expresion_filtro_gde_encuestas_nombre = guarani_ei_filtro::get_expresion('gde_encuestas.nombre', $datos['encuestas']['valor'], $datos['encuestas']['condicion_cs'], $datos['encuestas']['condicion_as']);
				$filtro_encuestas .= " AND EXISTS (SELECT * FROM gde_encuestas WHERE gde_grupos.encuesta = gde_encuestas.encuesta AND $expresion_filtro_gde_encuestas_nombre)";
				$this->dep('filtro')->columna('encuestas')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_encuestas";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		try {
			$this->cn()->guardar();

			$this->cn()->resetear();
			$this->set_pantalla('seleccion');

			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
			toba::notificacion()->agregar($this->get_mensaje('800ENC_falta_sincronizacion'), 'info');
		} catch (toba_error $e) {
			throw $e;
		}
	}
}