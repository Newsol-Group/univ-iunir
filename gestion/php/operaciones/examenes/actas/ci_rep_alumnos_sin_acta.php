<?php
class ci_rep_alumnos_sin_acta extends ci_rep_sin_listado_inicial
{
	protected $s__anio_academico;	
	protected $s__llamado_mesa_seleccionado;

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__get_parametros($params, toba_ajax_respuesta $respuesta)
	{
		$datos = $this->dep('cuadro')->get_datos();
		$fila = $params['fila'];
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '14000009', null, array('celda_memoria' => 'popup'));		
		$params_popup = (array('anio_academico' => $datos[$fila]['anio_academico'],
												 'codigo_nombre_actividad' => $datos[$fila]['codigo_nombre_actividad'],
												 'turno_nombre' => $datos[$fila]['turno_nombre'],
												 'nombre_mesa' => $datos[$fila]['mesa_examen_nombre'],
												 'llamado_mesa' => $datos[$fila]['llamado_mesa'],
												 'fecha_examen' => $datos[$fila]['fecha_examen'],
												 'llamado_nombre' => $datos[$fila]['llamado_nombre']
												));
		
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
			return toba::consulta_php('co_actas')->get_reporte_mesas_examen_con_alumnos_no_estan_en_acta($this->s__anio_academico, $filtro);
		} else {
			return array();
		}
	}

	function get_tabla_principal()
	{
		return 'sga_actas';
	}
	
	function extender_objeto_js()
	{
		$id_js_cuadro = $this->dependencia('cuadro')->get_id_objeto_js();
		parent::extender_objeto_js();
		
		echo "		
		//---- Eventos ---------------------------------------------
		
		{$id_js_cuadro}.evt__seleccion = function(fila)
		{
			var params = [];
			params['fila'] = fila;
			this.controlador.ajax('get_parametros', params, this, this.respuesta_reporte_actividades);
			return false;
		}
		
		{$id_js_cuadro}.respuesta_reporte_actividades = function(respuesta)
		{			
			abrir_popup('reporte', respuesta);
		}";
	}
}
?>