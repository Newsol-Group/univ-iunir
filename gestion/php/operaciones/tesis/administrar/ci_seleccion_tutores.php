<?php
/**
 *
 *
 * @package 	Tesis
 * @subpackage  -
 * 
 */
class ci_seleccion_tutores extends guarani_pers_ci
{
	// Temporales
	protected $datos_tutores_asociados_temp;
	
	// Selección de personas
	protected $s__personas_seleccionadas;
	
	// Filtro por legajo
	protected $s__filtro_legajo = '';
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA TUTORES 
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- ml_asociados -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__ml_asociados(toba_ei_formulario_ml $form_ml)
	{
		$form_ml->set_datos($this->cn()->entidad()->get_datos_tutores_asociados());
	}

	function evt__ml_asociados__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_tutores($datos);
		} catch (guarani_error $e) {
			$this->datos_tutores_asociados_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
	function evt__ml_asociados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_datos_tutor_asociado($seleccion);
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__filtro(guarani_ei_filtro $filtro)
	{
		$filtro->set_sin_filtrado_inicial();
		$filtro->set_etiquetas($this->cn()->etiquetas('Personas'));
		$this->filtro_configurar('filtro', false);
		$filtro->columna('legajo')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtros['filtro'] = $datos;
			if (isset($datos['legajo'])) {
				$this->s__filtro_legajo = guarani_ei_filtro::get_expresion('sga_docentes.legajo', $datos['legajo']['valor'], $datos['legajo']['condicion_cs'], $datos['legajo']['condicion_as'], 'es_igual_a');
				$this->dep('filtro')->columna('legajo')->resetear_estado();
			} else {
				$this->s__filtro_legajo = '';
			}
			$this->s__filtros['filtro'] = $this->dep('filtro')->get_sql_where();
		} else {
			$this->filtro_limpiar('filtro');
			$this->s__filtros['filtro'] = '';
		}
	}

	function evt__filtro__cancelar()
	{
		$this->filtro_limpiar('filtro');
	}
	
	function filtro_limpiar($nombre)
	{
		$this->s__filtro_legajo = '';
		unset($this->s__personas_seleccionadas);
		parent::filtro_limpiar($nombre);
	}
	
	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * @todo Se deben obtener todas aquellas personas que sean docentes y/o alumnos
	 *
	 */
	function conf__disponibles(cuadro_seleccion_multiple $cuadro)
	{
		$filtro = $this->get_filtro('filtro');
		
		if (isset($filtro)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_docentes');
			$indx_msj = guarani_mensajes::get('800_eof_cuadro_filtrado', array($etiqueta->get_enf('plural')));
			$filtro = $this->filtro_esta_cargado('filtro') ? $this->get_filtro('filtro') : 'true';
			$tutores_asociados = $this->cn()->entidad()->get_datos_tutores_asociados();
			
			if (!empty($tutores_asociados)) {
				$filtro .= ' AND '.guarani_sql::armar_condicion_in($tutores_asociados, 'vw_personas', 'persona', null, true);
			}
			
			$tutores_disponibles = $this->cn()->get_listado_tutores($filtro, $this->s__filtro_legajo);
			
			if(empty($tutores_disponibles)) {
				$cuadro->evento('agregar')->desactivar();
			}

			$cuadro->set_datos($tutores_disponibles);
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
		if (isset($this->s__personas_seleccionadas)) {
			foreach ($this->s__personas_seleccionadas as $valor) {
				$claves[] = implode(apex_qs_separador, $valor);
			}
		}
		return in_array($clave_fila, $claves);
	}
	
	function evt__disponibles__seleccion($seleccion)
	{
		$this->s__personas_seleccionadas = $seleccion;
	}

	function evt__disponibles__agregar()
	{
		if (!isset($this->s__personas_seleccionadas)) {
			throw guarani::error(guarani_mensajes::get('800_sin_seleccion_personas'));
		}
		$this->cn()->entidad()->agregar_tutores($this->s__personas_seleccionadas);
	}
	
}