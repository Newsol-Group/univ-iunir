<?php
/**
 *
 *
 * @package		Reconocimiento de Actividades
 * @subpackage	Equivalencias
 * 
 */
class ci_imp_cerrar_equivalencia extends ci_reporte
{
	// Datos del trámite de equivalencia seleccionado en la primer pantalla.
	protected $s__tramite_a_cerrar;
	
	// Variable que controla la impresión.
	protected $s__imprimir = false;
	
	// Ejemplares a imprimir
	protected $cnt_ejemplares;
	
	//Elemento Visual de Información
	/**
	 *
	 * @var ev_info_datos
	 */
	protected $ev_info;

	//--------------------------------------------------------------------------
	//---- Configuraciones -----------------------------------------------------
	//--------------------------------------------------------------------------

	function ini()
	{
		toba::memoria()->desactivar_reciclado();
		parent::ini();

		// Se recupera el parámetro que indica cantidad de ejemplares a imprimir.
		$this->cnt_ejemplares = guarani::parametros()->get_valor('equiv_impresion_cnt_ejemplares');
		// Se recupera el trámite de equivalencia seleccionado en la primer pantalla.
		$this->s__tramite_a_cerrar = $this->controlador()->get_tramite_equiv_seleccionado();
		// Se setean los datos de configuración y título para la impresión.
		$this->s__datos_conf = toba::consulta_php('co_equivalencias')->get_datos_tramite_equivalencia_otorgado($this->s__tramite_a_cerrar);
		$this->s__titulo = 'TRÁMITE DE EQUIVALENCIA';
	}

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		// Seteo de datos en ev
		$this->ev_info()->set_datos($this->s__datos_conf);
	}

	//--------------------------------------------------------------------------
	//---- Componente visual para mostrar información --------------------------
	//--------------------------------------------------------------------------

	function ev_info()
	{
		// Si se modifica aquí también modificar la función 'datos_encabezado' de la clase 'php/nucleo/reconocimiento_actividades/equivalencias/equivalencia_nucleo.php'
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

	//--------------------------------------------------------------------------
	//---- actividades_equivalencia_tramite ------------------------------------
	//--------------------------------------------------------------------------

	function conf__actividades_equivalencia_tramite(toba_ei_cuadro $cuadro)
	{
		$detalle_tramite_equivalencia = toba::consulta_php('co_equivalencias')->get_detalle_actividades_tramite_equivalencia($this->s__tramite_a_cerrar);
		$cuadro->set_datos($detalle_tramite_equivalencia);

		$etiquetas = guarani_etiquetas::get_tabla('sga_equiv_otorgada');
		$etiqueta = $etiquetas->get_enf('plural');
		$mje = guarani_mensajes::get('800_eof_cuadro_filtrado', array($etiqueta));
		$cuadro->set_eof_mensaje($mje);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

    function cuadro() 
	{
		return $this->dep('actividades_equivalencia_tramite');
	}
    
    function get_datos($filtro) 
    {
        return array();
    }

    function get_tabla_principal()
	{
		return 'sga_equiv_tramite';
	}

}