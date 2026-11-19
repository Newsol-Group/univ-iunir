<?php
class ci_rep_alumnos_comisiones_sin_acta extends ci_rep_sin_listado_inicial
{
	protected $s__anio_academico;

	function get_tabla_principal()
	{
		return 'sga_comisiones';
	}	
	
	function ajax__get_parametros($params, toba_ajax_respuesta $respuesta)
	{
		$datos = $this->dep('cuadro')->get_datos();
		$fila = $params['fila'];
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '14000011', array(), array('celda_memoria'=>'popup'));
		$params_popup = array(	'anio_academico' => $datos[$fila]['anio_academico'],
								'codigo_y_nombre_actividad' => $datos[$fila]['codigo_y_nombre_actividad'],												 
								'periodo_nombre' => $datos[$fila]['periodo_anio_nombre'],												 
								'comision_nombre' => $datos[$fila]['comision_nombre'],
								'comision' => $datos[$fila]['comision'],
								'instancia' => $datos[$fila]['instancia'],
								'instancia_nombre' => $datos[$fila]['instancia_nombre'],
							);
		
		//Se almacenan los datos en la instancia de impresión para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos($params_popup);
		//guarani_datos_impresion::instancia()->set_conf($comision['mensajes']);
		guarani_datos_impresion::instancia()->guardar_datos_imp();
		
		$respuesta->set($vinculo);
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		parent::conf__filtro($filtro);
	}
	
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$this->s__anio_academico = $datos['anio_academico']['valor'];
			$this->dep('filtro')->columna('anio_academico')->resetear_estado();
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos);
			unset($this->s__anio_academico);
			unset($this->s__filtro);
		}
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__anio_academico);
		unset($this->s__filtro);
	}

	function get_datos($filtro)
	{
		if (isset($this->s__anio_academico)) {
			return toba::consulta_php('co_comisiones')->get_comisiones_con_alumnos_no_estan_en_acta($this->s__anio_academico, $filtro);
		} else {
			return array();
		}
	}
	
}
?>