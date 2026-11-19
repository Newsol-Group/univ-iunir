<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Operación
 * 
 */
class ci_edi_conf_requisitos_x_operacion extends guarani_pers_ci
{
	protected $ev_info;                         // Componente Visual de Información
	
	// Datos del filtro y formulario de los requisitos a configurar.
	protected $datos_form_requisitos_temp;
	protected $s__datos_filtro;
	protected $s__filtro;

	function resetear()
	{
		unset($this->s__datos_filtro);
		unset($this->s__filtro);	
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_inicial($pantalla) 
	{
		$datos_requisitos = $this->get_datos_ev_requisito();
		$datos_operacion = $this->controlador()->get_datos_ev_operacion();
		$this->ev_info()->set_datos(array_merge($datos_requisitos, $datos_operacion));
	}
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('accion', 'operacion','requisito'));
			$this->ev_info->set_columnas_fila(array('accion', 'operacion'), 1);
			$this->ev_info->set_columnas_fila(array('requisito'), 2);            
			$this->ev_info->set_max_columnas_por_fila(2);
			$this->ev_info->set_etiqueta('accion', 'Acción');
			$this->ev_info->set_etiqueta('operacion', 'Operación');            
			$this->ev_info->set_etiqueta('requisito', 'Requisito');            
		}
		return $this->ev_info;
	}
	
	function get_datos_ev_requisito()
	{
		$seleccion = $this->controlador()->get_seleccion();
		$datos['requisito'] = $seleccion['requisito_descr'];
		return $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares para el filtro sobre el form_ml -----------------------------------
	//-----------------------------------------------------------------------------------
	
	 function obtener_datos()
	 {
		$columnas_a_filtrar = $this->dependencia('filtro_req_a_configurar')->get_datos();
		$condicion = array();
		if (isset($columnas_a_filtrar['interfaz'])) {
			$condicion = array_merge($condicion, array('interfaz' => $columnas_a_filtrar['interfaz']['valor']));
	  	}
	  	if (isset($columnas_a_filtrar['estado'])) {
			$condicion = array_merge($condicion, array('activo' => $columnas_a_filtrar['estado']['valor']));
	  	}
	  	if (isset($columnas_a_filtrar['comportamiento'])) {
	  		$condicion = array_merge($condicion, array('actua_como' => $columnas_a_filtrar['comportamiento']['valor']));	
	  	}
	  	$datos = $this->cn()->entidad()->get_datos($condicion); 
	  	// La columna de grupo es un filtro de tipo cadena por lo que debe considerarse como un caso especíal.
		if (isset($columnas_a_filtrar['grupo'])) {
			$columna_grupo_nombre = $columnas_a_filtrar['grupo'];
			$datos = comunes::filtrar_array_filas_por_cadena($datos, $columna_grupo_nombre, 'grupo_nombre');
		} 
		return $datos; 
	}
	 
	//-----------------------------------------------------------------------------------
	//---- filtro_req_a_configurar ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_req_a_configurar(toba_ei_filtro $filtro)
	{
		$filtro->columna('interfaz')->set_condicion_fija('es_igual_a');
		$filtro->columna('comportamiento')->set_condicion_fija('es_igual_a');
		$filtro->columna('estado')->set_condicion_fija('es_igual_a');
		if (isset($this->s__datos_filtro)) {
			$filtro->set_datos($this->s__datos_filtro);
		}		
	}
	
	function evt__filtro_req_a_configurar__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtro = $datos;
			$this->s__filtro = $this->dependencia('filtro_req_a_configurar')->get_sql_where();
		} else {
			unset($this->s__datos_filtro);
			$this->s__filtro = '';
		}
	}

	function evt__filtro_req_a_configurar__cancelar()
	{
		unset($this->s__datos_filtro);
		unset($this->s__filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_conf_requisitos ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_conf_requisitos(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_form_requisitos_temp)) {
			$datos = $this->datos_form_requisitos_temp;
		} else {
			// Se formatean los datos del filtro para poder enviarlos como condiciones para recuperar las filas del DT.
			$datos = $this->obtener_datos();
		} 
		$form_ml->set_datos($datos);
	}

	function evt__form_ml_conf_requisitos__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos($datos);
		} catch (guarani_error $e) {
			$this->datos_form_requisitos_temp = $datos;
			throw $e;
		}
	}	
}
?>