<?php
/**
 *
 *
 * @package 	Tesis
 * @subpackage	-
 * 
 */
class ci_seleccion_alumnos extends guarani_pers_ci
{
	//Selección de alumnos
	protected $s__alumnos_seleccionados;
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA ALUMNOS 
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- asociados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__asociados(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_sin_registros'));
		$cuadro->set_datos($this->cn()->entidad()->get_datos_alumnos_asociados());
	}

	function evt__asociados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_datos_alumno_asociado($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__filtro(guarani_ei_filtro $filtro)
	{
		$filtro->set_sin_filtrado_inicial();
		$filtro->set_etiquetas($this->cn()->etiquetas('Alumnos'));
		$this->filtro_configurar('filtro', false);
	}

	function evt__filtro__filtrar($datos)
	{
		$this->filtro_filtrar('filtro', $datos);
	}

	function evt__filtro__cancelar()
	{
		$this->filtro_limpiar('filtro');
	}
	
	function filtro_limpiar($nombre)
	{
		unset($this->s__alumnos_seleccionados);
		parent::filtro_limpiar($nombre);
	}
	
	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__disponibles(cuadro_seleccion_multiple $cuadro)
	{
		$filtro = $this->get_filtro('filtro');
		if (isset($filtro)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_alumnos');
			$indx_msj = guarani_mensajes::get('800_eof_cuadro_filtrado', array($etiqueta->get_enf('plural')));
			$filtro = $this->filtro_esta_cargado('filtro') ? $this->get_filtro('filtro') : 'true';
			$alumnos_asociados = $this->cn()->entidad()->get_datos_alumnos_asociados();
			
			if (!empty($alumnos_asociados)) {
				$filtro .= ' AND '.guarani_sql::armar_condicion_in($alumnos_asociados, 'sga_alumnos', 'alumno', null, true);
			}
			
			$alumnos_disponibles = $this->cn()->get_listado_alumnos($filtro);
			
			if(empty($alumnos_disponibles)) {
				$cuadro->evento('agregar')->desactivar();
			}

			$cuadro->set_datos($alumnos_disponibles);
		} else {
			$indx_msj = guarani_mensajes::get('800_eof_cuadro_sinfiltro');
		}
		
		$cuadro->set_eof_mensaje($indx_msj);
	}

	function conf_evt__disponibles__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_clave_seleccionada($this->dep('disponibles')->get_clave_fila($fila, true)));
	}
	
	function es_clave_seleccionada($clave_fila)
	{
		$claves = array();
		if (isset($this->s__alumnos_seleccionados)) {
			foreach ($this->s__alumnos_seleccionados as $valor) {
				$claves[] = implode(apex_qs_separador, $valor);
			}
		}
		return in_array($clave_fila, $claves);
	}
	
	function evt__disponibles__seleccion($seleccion)
	{
		$this->s__alumnos_seleccionados = $seleccion;
	}

	function evt__disponibles__agregar()
	{
		if (!isset($this->s__alumnos_seleccionados)) {
			throw guarani::error(guarani_mensajes::get('800_sin_seleccion_alumnos'));
		}
		$this->cn()->entidad()->set_datos_alumnos($this->s__alumnos_seleccionados);
	}

}
?>