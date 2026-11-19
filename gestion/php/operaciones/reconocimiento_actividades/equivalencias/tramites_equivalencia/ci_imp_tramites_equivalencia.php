<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class ci_imp_tramites_equivalencia extends ci_reporte
{
	protected $s__tramite;

	//Componente Visual de Información.
	protected $ev_info;

	function ini()
	{
		//Se desactiva el reciclado de memoria
		toba::memoria()->desactivar_reciclado();
		parent::ini();

		$this->s__tramite = $this->controlador()->get_tramite_seleccionado();

		// Se recupera el parámetro que indica cantidad de ejemplares a imprimir.
		//$this->cnt_ejemplares = guarani::parametros()->get_valor('equiv_impresion_cnt_ejemplares');

		// Se setean los datos de configuración y título para la impresión.
		//$this->s__datos_conf = toba::consulta_php('co_equivalencias')->get_datos_tramite_equivalencia_otorgado($this->s__tramite_a_cerrar);
		//$this->s__titulo = 'TRÁMITE DE EQUIVALENCIA';
	}

    function cuadro() 
	{
		return $this->dep('cuadro_detalle');
	}

	function get_tabla_principal()
	{
		return 'sga_equiv_tramite';
	}

	function get_datos($filtro)
	{
		return array();
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_inicial()
	{
		if (isset($this->s__tramite)) {
			$datos = toba::consulta_php('co_equivalencias')->get_datos_tramite_equivalencia_otorgado($this->s__tramite);
			$this->ev_info()->set_datos($datos);
		}
	}
	
	//---- cuadro_detalle ---------------------------------------------------------------
	
	function conf__cuadro_detalle(toba_ei_cuadro $cuadro)
	{
		$datos = toba::consulta_php('co_equivalencias')->get_detalle_actividades_tramite_equivalencia($this->s__tramite);
		$cuadro->set_datos($datos);

		$etiqueta = guarani_etiquetas::get_tabla('sga_equiv_actividades');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info->set_style_fila('height: 20px;');
			$this->ev_info->set_columnas_a_mostrar(equivalencia::columnas_a_mostrar_pdf);
			$this->ev_info->set_max_columnas_por_fila(3);
			
			$this->ev_info->set_td_etiqueta_width('110px');
			$this->ev_info->set_td_valores_width('300px');

			$this->ev_info->set_columnas_fila(array('equivalencia_tramite', 'documento_numero', 'fecha_formato_visual'), 1);
			$this->ev_info->set_columnas_fila(array('alumno_nombre_completo'), 2);
			$this->ev_info->set_columnas_fila(array('alumno_identificacion', 'legajo'), 3);
			$this->ev_info->set_columnas_fila(array('propuesta_alumno'), 4);
			$this->ev_info->set_columnas_fila(array('plan_alumno'), 5);
			$this->ev_info->set_columnas_fila(array('version_alumno'), 6);
			$this->ev_info->set_columnas_fila(array('origen_descr', 'rectifica_a'), 7);
			$this->ev_info->set_columnas_fila(array('institucion_nombre'), 8);
			$this->ev_info->set_columnas_fila(array('estado_nombre'), 9);

			$this->ev_info->set_etiqueta('equivalencia_tramite', equivalencia::etiquetas_pdf['equivalencia_tramite']);
			$this->ev_info->set_etiqueta('documento_numero', equivalencia::etiquetas_pdf['documento_numero']);
			$this->ev_info->set_etiqueta('fecha_formato_visual', equivalencia::etiquetas_pdf['fecha_formato_visual']);
			$this->ev_info->set_etiqueta('rectifica_a', equivalencia::etiquetas_pdf['rectifica_a']);
			$this->ev_info->set_etiqueta('alumno_nombre_completo', equivalencia::etiquetas_pdf['alumno_nombre_completo']);
			$this->ev_info->set_etiqueta('alumno_identificacion', equivalencia::etiquetas_pdf['alumno_identificacion']);
			$this->ev_info->set_etiqueta('legajo', equivalencia::etiquetas_pdf['legajo']);
			$this->ev_info->set_etiqueta('propuesta_alumno', equivalencia::etiquetas_pdf['propuesta_alumno']);
			$this->ev_info->set_etiqueta('plan_alumno', equivalencia::etiquetas_pdf['plan_alumno']);
			$this->ev_info->set_etiqueta('version_alumno', equivalencia::etiquetas_pdf['version_alumno']);
			$this->ev_info->set_etiqueta('origen_descr', equivalencia::etiquetas_pdf['origen_descr']);
			$this->ev_info->set_etiqueta('institucion_nombre', equivalencia::etiquetas_pdf['institucion_nombre']);
			$this->ev_info->set_etiqueta('estado_nombre', equivalencia::etiquetas_pdf['estado_nombre']);
		}
		return $this->ev_info;
	}
}
?>