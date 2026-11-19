<?php
/**
 *
 * 
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Aprobación por Resolución
 * 
 */
class ci_edi_actividades_realizadas extends guarani_pers_ci
{
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';
	protected $datos_generales_temp;
	protected $datos_actividad_doc_temp;
	protected $datos_doc_digital_temp;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		$tipo = $this->dependencia('form_datos_generales_actividad_doc')->ef('tipo_actividad')->get_estado();

		if ($this->pantalla()->existe_dependencia('form_ayudantia')) {
			$this->pantalla()->eliminar_dep('form_ayudantia');
		}
		if ($this->pantalla()->existe_dependencia('form_doc_digital')) {
			$this->pantalla()->eliminar_dep('form_doc_digital');
		}
		if ($this->pantalla()->existe_dependencia('form_curso')) {
			$this->pantalla()->eliminar_dep('form_curso');
		}
		if ($this->pantalla()->existe_dependencia('form_investigacion')) {
			$this->pantalla()->eliminar_dep('form_investigacion');
		}
		if ($this->pantalla()->existe_dependencia('form_pasantia')) {
			$this->pantalla()->eliminar_dep('form_pasantia');
		}
		if ($this->pantalla()->existe_dependencia('form_seminario')) {
			$this->pantalla()->eliminar_dep('form_seminario');
		}
		if ($this->pantalla()->existe_dependencia('form_trabajo_profesional')) {
			$this->pantalla()->eliminar_dep('form_trabajo_profesional');
		}
		if ($this->pantalla()->existe_dependencia('form_tutoria')) {
			$this->pantalla()->eliminar_dep('form_tutoria');
		}

		if ($tipo) {
			// Se habilitan dependencias necesarias según el tipo de actividad 
			// seleccionado, si el mismo tiene estado.
			switch ($tipo) {
				case aprobacion_por_resolucion::ayudantia:
					$this->pantalla()->agregar_dep('form_ayudantia');
					break;
				case aprobacion_por_resolucion::curso:
					$this->pantalla()->agregar_dep('form_curso');
					$this->pantalla()->agregar_dep('form_doc_digital');
					break;
				case aprobacion_por_resolucion::investigacion:
					$this->pantalla()->agregar_dep('form_investigacion');
					$this->pantalla()->agregar_dep('form_doc_digital');
					break;
				case aprobacion_por_resolucion::pasantia:
					$this->pantalla()->agregar_dep('form_pasantia');
					break;
				case aprobacion_por_resolucion::seminario:
					$this->pantalla()->agregar_dep('form_seminario');
					$this->pantalla()->agregar_dep('form_doc_digital');
					break;
				case aprobacion_por_resolucion::trabajo_profesional:
					$this->pantalla()->agregar_dep('form_trabajo_profesional');
					break;
				case aprobacion_por_resolucion::tutoria:
					$this->pantalla()->agregar_dep('form_tutoria');
					break;
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCIÓN ACTIVIDADES DOCUMENTOS 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- cuadro_activ_realizadas_documentacion ----------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_activ_realizadas_documentacion(toba_ei_cuadro $cuadro)
	{
		// Recupera la etiqueta enfatizada y plural del concepto asociado a la tabla de fechas de períodos de inscripción.
		$etiqueta = guarani_etiquetas::get_tabla('sga_reconocimiento_doc');
		$etiqueta = $etiqueta->get_enf('plural');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800EQU_sin_documentacion'));
		$datos_actividades_docs = $this->cn()->entidad()->get_datos_documentos();
		$cuadro->set_datos($datos_actividades_docs);
	}

	function evt__cuadro_activ_realizadas_documentacion__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_documento($seleccion);
		$this->set_pantalla('pant_edicion_actividades_docs');
	}

	function evt__cuadro_activ_realizadas_documentacion__agregar($datos)
	{
		$this->set_pantalla('pant_edicion_actividades_docs');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICIÓN ACTIVIDADES DOCUMENTOS
	//-----------------------------------------------------------------------------------

	function conf__pant_edicion_actividades_docs(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->controlador()->evento('cancelar')->desactivar();
		if ($this->controlador()->controlador()->pantalla('edicion')->existe_evento('eliminar')) {
			$this->controlador()->controlador()->evento('eliminar')->desactivar();
		}
		$this->controlador()->controlador()->evento('guardar')->desactivar();

		if ($this->cn()->entidad()->hay_documento_seleccionado()) {
			$this->evento('alta')->ocultar();
			$this->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array('la documentación')));
		} else {
			$this->evento('baja')->ocultar();
			$this->evento('modificacion')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_datos_generales_actividad_doc -------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_datos_generales_actividad_doc(form_datos_generales_actividad_doc $form)
	{
		$form->ef('creditos')->set_expreg(comunes::er_numero_dos_decimales);
		$this->marcar_efs_obligatorios($form, array('nombre', 'carga_horaria', 'tipo_actividad'));

		// Si el documento se está editando el tipo de Actividad no puede modificarse.
		// Para chequear esto debe verificarse si hay algún cursor posicionado en la tabla de documentos.
		if ($this->cn()->entidad()->hay_documento_seleccionado()) {
			$form->ef('tipo_actividad')->set_solo_lectura();
		}
		if (isset($this->datos_generales_temp)) {
			$form->set_datos($this->datos_generales_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_documento_seleccionado();
			$form->set_datos($datos);
		}
	}

	function evt__form_datos_generales_actividad_doc__modificacion($datos)
	{
		$this->datos_generales_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_ayudantia ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ayudantia(form_ayudantia $form)
	{
		$this->marcar_efs_obligatorios($form, array('comision'));

		if (isset($this->datos_actividad_doc_temp)) {
			$form->set_datos($this->datos_actividad_doc_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_documento_seleccionado();
			$form->set_datos($datos);
		}
	}

	function evt__form_ayudantia__modificacion($datos)
	{
		$this->datos_actividad_doc_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_doc_digital -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_doc_digital(form_doc_digital $form)
	{
		$upload_max_filesize = ini_get('upload_max_filesize');
		$form->ef('nombre_archivo')->set_descripcion("Los formatos de archivo permitidos son: pdf, png, odt, doc,jpg, bmp, txt, docx, xls, xlsx, rtf, ods, zip y rar. Tamaño máximo de archivo: $upload_max_filesize.");

		$doc_seleccionado = $this->cn()->entidad()->hay_documento_seleccionado();
		$documentos = $this->cn()->entidad()->get_datos_documento_seleccionado();
		$doc_digitales = $this->cn()->entidad()->tabla('documentos_digitales')->get_filas();

		if (isset($this->datos_doc_digital_temp)) {
			$this->controlador->controlador->set_hubo_cambios();
			$datos = $this->datos_doc_digital_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_doc_digital();
		}

		if (isset($datos['reconocimiento_doc'])) {
			$form->evento('descargar')->vinculo()->set_parametros(array('documento' => $datos['reconocimiento_doc']));
		} else {
			$form->evento('descargar')->anular();
			$form->evento('eliminar')->anular();
		}

		$form->set_datos($datos);
	}

	function evt__form_doc_digital__modificacion($datos)
	{
		$this->datos_doc_digital_temp = $datos;
	}

	function evt__form_doc_digital__eliminar()
	{
		$this->cn()->entidad()->eliminar_datos_doc_digital();
	}

	//-----------------------------------------------------------------------------------
	//---- form_curso -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_curso(form_curso $form)
	{
		$this->marcar_efs_obligatorios($form, array('responsable', 'institucion'));

		if (isset($this->datos_actividad_doc_temp)) {
			$this->controlador->controlador->set_hubo_cambios();
			$datos = $this->datos_actividad_doc_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_documento_seleccionado();
		}
		$form->set_datos($datos);
	}

	function evt__form_curso__modificacion($datos)
	{
		$this->datos_actividad_doc_temp = $datos;
	}

	function evt__form_curso__eliminar_archivo()
	{
		$this->cn()->entidad()->eliminar_archivo_documento();
	}

	//-----------------------------------------------------------------------------------
	//---- form_investigacion -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_investigacion(form_investigacion $form)
	{
		$this->marcar_efs_obligatorios($form, array('responsable'));

		if (isset($this->datos_actividad_doc_temp)) {
			$form->set_datos($this->datos_actividad_doc_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_documento_seleccionado();
			$form->set_datos($datos);
		}
	}

	function evt__form_investigacion__modificacion($datos)
	{
		$this->datos_actividad_doc_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_pasantia ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_pasantia(form_pasantia $form)
	{
		$form->ef('email')->set_expreg(comunes::er_email);

		$this->marcar_efs_obligatorios($form, array('responsable', 'institucion'));

		if (isset($this->datos_actividad_doc_temp)) {
			$form->set_datos($this->datos_actividad_doc_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_documento_seleccionado();
			$form->set_datos($datos);
		}
	}

	function evt__form_pasantia__modificacion($datos)
	{
		$this->datos_actividad_doc_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_seminario ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_seminario($form)
	{
		$this->marcar_efs_obligatorios($form, array('responsable', 'institucion'));

		if (isset($this->datos_actividad_doc_temp)) {
			$form->set_datos($this->datos_actividad_doc_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_documento_seleccionado();
			$form->set_datos($datos);
		}
	}

	function evt__form_seminario__modificacion($datos)
	{
		$this->datos_actividad_doc_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_trabajo_profesional -----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_trabajo_profesional($form)
	{
		$this->marcar_efs_obligatorios($form, array('responsable', 'institucion'));

		if (isset($this->datos_actividad_doc_temp)) {
			$form->set_datos($this->datos_actividad_doc_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_documento_seleccionado();
			$form->set_datos($datos);
		}
	}

	function evt__form_trabajo_profesional__modificacion($datos)
	{
		$this->datos_actividad_doc_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_tutoria -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_tutoria(form_tutoria $form)
	{
		$this->marcar_efs_obligatorios($form, array('comision'));

		if (isset($this->datos_actividad_doc_temp)) {
			$form->set_datos($this->datos_actividad_doc_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_documento_seleccionado();
			$form->set_datos($datos);
		}
	}

	function evt__form_tutoria__modificacion($datos)
	{
		$this->datos_actividad_doc_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__alta()
	{
		$datos_activ_doc = is_array($this->datos_actividad_doc_temp) ? array_merge($this->datos_generales_temp, $this->datos_actividad_doc_temp) : $this->datos_generales_temp;
		$this->cn()->entidad()->nuevo_documento($datos_activ_doc);
		if (isset($this->datos_doc_digital_temp)) {
			$this->cn()->entidad()->set_datos_doc_digital($this->datos_doc_digital_temp);
		}
		$this->evt__cancelar();
	}

	function evt__modificacion()
	{
		$datos_activ_doc = array_merge($this->datos_generales_temp, $this->datos_actividad_doc_temp);
		if (isset($this->datos_doc_digital_temp)) {
			$this->cn()->entidad()->set_datos_doc_digital($this->datos_doc_digital_temp);
		}
		$this->cn()->entidad()->set_datos_documento($datos_activ_doc);
		$this->evt__cancelar();
	}

	function evt__baja()
	{
		$this->cn()->entidad()->eliminar_documento_seleccionado();
		$this->evt__cancelar();
	}

	function evt__cancelar()
	{
		unset($this->datos_generales_temp);
		unset($this->datos_actividad_doc_temp);

		$this->cn()->entidad()->deseleccionar_documento();
		$this->set_pantalla('pant_seleccion_actividades_docs');
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function marcar_efs_obligatorios($form, $efs)
	{
		foreach ($efs as $ef) {
			$form->ef($ef)->set_estilo_etiqueta('ei-form-etiq-oblig');
			$etiqueta = $form->ef($ef)->get_etiqueta() . ' (*)';
			$form->ef($ef)->set_etiqueta($etiqueta);
		}
	}

	function get_nombre_comision($comision)
	{
		return toba::consulta_php('co_comisiones')->get_nombre_comision($comision);
	}

}
?>