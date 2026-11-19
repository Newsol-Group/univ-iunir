<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Reportes
 * 
 */
class ci_rep_insc_alumnos extends ci_rep_sin_listado_inicial
{
	protected $s__persona_seleccionada;
	protected $ev_info;
	protected $s__filtro_inscripciones;
	protected $s__datos_inscripciones;
	
	protected $filtro_estado;
	protected $filtro_estado_rechazadas;
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares
	//-----------------------------------------------------------------------------------
	
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_inscripciones_cursadas')->get_listado_alumnos_propuestas($filtro);
	}

	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA: listado
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__persona_seleccionada = $seleccion['persona'];
		$this->set_pantalla('inscripciones');
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA: inscripciones
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__inscripciones(toba_ei_pantalla $pantalla)
	{
		if (isset($this->s__persona_seleccionada)) {
			$filtro = "vw_personas.persona = $this->s__persona_seleccionada";
			$datos = current(toba::consulta_php('co_inscripciones_cursadas')->get_listado_alumnos_propuestas($filtro));
			$this->ev_info()->set_datos($datos);
		}
	}
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('persona_nombre', 'nro_identificacion'));
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_etiqueta('persona_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('nro_identificacion', 'Identificación');
			$this->ev_info->set_td_etiqueta_width('90px');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_inscripciones ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__filtro_inscripciones__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_inscripciones = $datos;
			

			$this->s__filtro_inscripciones = $this->dep('filtro_inscripciones')->get_sql_where();
		} else {
			unset($this->s__datos_inscripciones);
			$this->s__filtro_inscripciones = '';
		}
	}

	function evt__filtro_inscripciones__cancelar()
	{
		unset($this->s__datos_inscripciones);
		$this->s__filtro_inscripciones = '';
	}

	function conf__filtro_inscripciones(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_inscripciones)) {
			$filtro->set_datos($this->s__datos_inscripciones);
		}
		$filtro->columna('estado')->set_condicion_fija('es_igual_a');
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_inscripciones ---------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__cuadro_inscripciones(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_ev_info_asociado($this->ev_info());
		$cuadro->set_usar_ordenamiento_con_cortes();
		
		if (isset($this->s__persona_seleccionada)) {
			$datos = toba::consulta_php('co_inscripciones_cursadas')->get_listado_inscripciones_alumno_propuestas($this->s__persona_seleccionada, $this->s__filtro_inscripciones);
			$cuadro->set_datos($datos);
		}
		
		$etiquetas = guarani_etiquetas::get_tabla('sga_insc_cursada');
		$etiqueta = $etiquetas->get_enf('plural');
		$msj = guarani_mensajes::get('800_eof_cuadro', array($etiqueta));
		$cuadro->set_eof_mensaje($msj);
	}
	
	//-----------------------------------------------------------------------------------
	//---- EVENTOS
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		unset($this->s__persona_seleccionada);
		unset($this->s__datos_inscripciones);
		unset($this->s__filtro_inscripciones);
		$this->set_pantalla('listado');
	}
	
	function cuadro() 
	{
		return $this->dep('cuadro_inscripciones');
	}

}
?>