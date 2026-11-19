<?php
/**
 *
 * 
 * @package Reconocimiento de Actividades
 * @subpackage Aprobación por Resolución
 * 
 */
class ci_impresion_aprobacion extends ci_impresion
{
	// Datos del trámite de aprobación seleccionado en la primera pantalla
	protected $s__tramite;
	// Elemento Visual de Información
	protected $ev_info;

	function conf()
	{
		// Se desactiva reciclado de memoria
		toba::memoria()->desactivar_reciclado();

		// Se recupera el trámite de aprobación seleccionado en la primera pantalla
		$this->s__tramite = $this->controlador()->get_tramite_aprobacion_seleccionado();

		// Se setean los datos de configuración y título para la impresión.
		$formateo = new toba_formateo('html');
		$this->s__datos_conf = toba::consulta_php('co_aprobacion_por_resolucion')->get_datos_tramite_aprobacion_por_resolucion($this->s__tramite);
		$this->s__datos_conf['fecha_format'] = $formateo->formato_fecha($this->s__datos_conf['fecha_generacion']);
		$this->s__titulo = 'TRÁMITE DE APROBACIÓN POR RESOLUCIÓN';

		// Resto de las configuraciones
		parent::conf();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		if ($this->s__datos_conf['tipo_reconocimiento'] == aprobacion_por_resolucion::activ_modulos) {
			$pantalla->agregar_dep('actividades_modulos_reconocimiento');
			unset($this->s__datos_conf['total_creditos']);
		}

		$this->ev_info()->set_datos($this->s__datos_conf);
	}

	//-----------------------------------------------------------------------------------
	//---- actividades_aprobacion -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__actividades_aprobacion(toba_ei_cuadro $cuadro)
	{
		$actividades = toba::consulta_php('co_aprobacion_por_resolucion')->get_actividades_tramite_aprobacion($this->s__tramite);
		$cuadro->set_datos($actividades);
	}

	//-----------------------------------------------------------------------------------
	//---- actividades_modulos_reconocimiento -------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__actividades_modulos_reconocimiento(toba_ei_cuadro $cuadro)
	{
		if ($this->s__datos_conf['tipo_reconocimiento'] == aprobacion_por_resolucion::activ_modulos) {
			$reconocimiento = toba::consulta_php('co_aprobacion_por_resolucion')->get_actividades_reconocimiento_tramite_aprobacion($this->s__tramite);
			$cuadro->set_datos($reconocimiento);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Componente visual para mostrar Información -----------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {

			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('nro_tramite', 'documento_numero', 'fecha_format', 'estado_nombre', 'alumno_nombre_completo', 'alumno_identificacion', 'legajo', 'tipo_reconocimiento_descr', 'propuesta_tramite', 'plan_tramite', 'version_tramite', 'total_creditos'));
			$this->ev_info->set_max_columnas_por_fila(4);

			$this->ev_info->set_columnas_fila(array('nro_tramite', 'documento_numero', 'fecha_format', 'estado_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('alumno_nombre_completo', 'alumno_identificacion', 'legajo', 'tipo_reconocimiento_descr'), 2);
			$this->ev_info->set_columnas_fila(array('propuesta_tramite', 'plan_tramite', 'version_tramite', 'total_creditos'), 3);

			$this->ev_info->set_etiqueta('nro_tramite', 'Nº TRAMITE');
			$this->ev_info->set_etiqueta('documento_numero', 'Nº RESOLUCIÓN');
			$this->ev_info->set_etiqueta('fecha_format', 'FECHA');
			$this->ev_info->set_etiqueta('estado_nombre', 'Estado');
			$this->ev_info->set_etiqueta('alumno_nombre_completo', 'Apellido y Nombre');
			$this->ev_info->set_etiqueta('alumno_identificacion', 'Identificación');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
			$this->ev_info->set_etiqueta('tipo_reconocimiento_descr', 'RECONOCE');
			$this->ev_info->set_etiqueta('propuesta_tramite', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_tramite', 'Plan');
			$this->ev_info->set_etiqueta('version_tramite', 'Versión');
			$this->ev_info->set_etiqueta('total_creditos', 'Créditos Otorgados');
		}

		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function vista_xml($inicial = false, $xmlns = null)
	{
		//----------------------------------------------------------------------
		//---- Configuraciones Iniciales
		//----------------------------------------------------------------------
		
		//Se modifican los márgenes y el alto de la cabecera
		$this->xml_set_margenes(array('sup' => '0.3cm', 'inf' => '0.3cm', 'izq' => '0.3cm', 'der' => '0.3cm'));
		$this->xml_set_alto_cabecera('7.5cm');

		//----------------------------------------------------------------------
		//---- ENCABEZADO
		//----------------------------------------------------------------------

		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		// Tabla para el título y logo
		$this->xml_set_tabla_cols();

		// Datos de la tabla
		$filas[] = array(array('valor' => $this->xml_imagen($path_logo), 'border' => 'none', 'number-columns-spanned' => 4), array('valor' => '', 'border' => 'none', 'number-columns-spanned' => 4));
		$filas[] = array(array('valor' => $this->xml_texto($this->s__titulo, array('font-weight' => 'bold', 'font-size' => 12, 'text-align' => 'center')), 'border-bottom' => 'solid black', 'text-align' => 'center', 'number-columns-spanned' => 8));

		$cabecera = $this->xml_tabla($filas, false);

		// Se generan los datos básicos del trámite de aprobación por resolución y se resetean las columnas de tablas y $fila.
		// Tabla que reemplaza a la cabecera
		// Se generan los datos básicos del acta y se resetean las columnas de tablas y $fila.
		$cols = array();
		$cols[] = array('column-width' => '3cm');
		$cols[] = array('column-width' => '4cm');
		$cols[] = array('column-width' => '2.7cm');
		$cols[] = array('column-width' => '2.5cm');
		$cols[] = array('column-width' => '1.5cm');
		$cols[] = array('column-width' => '2cm');
		$cols[] = array('column-width' => '2cm');
		$cols[] = array('column-width' => '3cm');
		$this->xml_set_tabla_cols($cols);

		$filas = array();
		$filas[] = array(
			array('valor' => 'Nº TRAMITE: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos_conf['nro_tramite'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => 'Nº RESOLUCIÓN: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos_conf['documento_numero'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => 'FECHA: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos_conf['fecha_format'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => 'Estado: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos_conf['estado_nombre'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left')
		);
		$filas[] = array(
			array('valor' => 'Apellido y Nombre: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos_conf['alumno_nombre_completo'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left','wrap-option' => 'wrap'),
			array('valor' => 'Identificación: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos_conf['alumno_identificacion'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => 'Legajo: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos_conf['legajo'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => 'RECONOCE: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos_conf['tipo_reconocimiento_descr'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left')
		);
		if ($this->s__datos_conf['tipo_reconocimiento'] == aprobacion_por_resolucion::activ_modulos) {
			$filas[] = array(
				array('valor' => 'Propuesta: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
				array('valor' => $this->s__datos_conf['propuesta_tramite'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left','wrap-option' => 'wrap'),
				array('valor' => 'Plan: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
				array('valor' => $this->s__datos_conf['plan_tramite'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
				array('valor' => 'Versión: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
				array('valor' => $this->s__datos_conf['version_tramite'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left')
			);
		} else {
			$filas[] = array(
				array('valor' => 'Propuesta: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
				array('valor' => $this->s__datos_conf['propuesta_tramite'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left','wrap-option' => 'wrap'),
				array('valor' => 'Plan: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
				array('valor' => $this->s__datos_conf['plan_tramite'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
				array('valor' => 'Versión: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
				array('valor' => $this->s__datos_conf['version_tramite'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
				array('valor' => 'Créditos Otorgados: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
				array('valor' => $this->s__datos_conf['total_creditos'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left')
			);
		}

		// Se genera la segunda tabla.
		$cabecera .= $this->xml_tabla($filas, false);
		$this->xml_set_cabecera($cabecera);

		//----------------------------------------------------------------------
		//---- CUERPO
		//----------------------------------------------------------------------

		$xml = $this->xml_get_tag_inicio();
		
		if ($this->dep('actividades_aprobacion')->get_datos()) {
			$xml .= $this->dep('actividades_aprobacion')->vista_xml(false, $xmlns);
		}

		if ($this->existe_dependencia('actividades_modulos_reconocimiento')) {
			if ($this->dep('actividades_modulos_reconocimiento')->get_datos()) {
				$xml .= $this->dep('actividades_modulos_reconocimiento')->vista_xml(false, $xmlns);
			}
		}

		$xml .= $this->xml_get_tag_fin();
		return $xml;
	}

}
?>