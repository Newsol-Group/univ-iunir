<?php

use Clegginabox\PDFMerger\PDFMerger;

class ci_nav_imp_actas_cursadas_masiva extends guarani_pers_ci
{
	protected $indx_msg_eof = '';
	protected $s__imprimir = false;
	protected $s__filtro;
	protected $s__datos_filtro;
		
	protected $s__operacion_a_volver;
	protected $s__celda_a_restaurar;
	protected $s__ids_actas_sel;
	protected $s__volante;
	
	// Arreglo que contiene los datos de cada una de las actas seleccionadas y su cantidad de alumnos
	protected $s__datos_actas = array();

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ini()
	{
		toba::memoria()->desactivar_reciclado();
		parent::ini();
	}

	function ini__operacion()
	{
		$this->s__operacion_a_volver = toba::memoria()->get_parametro('operacion_a_volver');
		$this->s__celda_a_restaurar = toba::memoria()->get_parametro('celda_a_restaurar');
		if (!empty($this->s__operacion_a_volver) && !empty($this->s__celda_a_restaurar)) {
			$datos = guarani_datos_impresion::instancia()->recuperar_datos_imp();
			$this->s__filtro = 'sga_actas.id_acta IN (0';
			if (isset($datos['datos']['actas'])) {
				foreach ($datos['datos']['actas'] as $dato) {
					$this->s__ids_actas_sel[]['id_acta'] = $dato['id_acta'];
					$this->s__filtro .= ',' . $dato['id_acta'];
				}
			}
			$this->s__filtro .= ')';
		}
	}

	function conf()
	{
		$this->indx_msg_eof = '800_eof_cuadro';
		$this->dep('filtro')->set_sin_filtrado_inicial();
		if (!isset($this->s__operacion_a_volver)) {
			$this->evento('cancelar')->ocultar();
		}
	}

	function set_sin_listado_inicial()
	{
		$this->con_listado_inicial = false;
		$this->dep('filtro')->set_sin_filtrado_inicial();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: pant_inicial
	//-----------------------------------------------------------------------------------
	
	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$datos = $this->dep('cuadro')->get_datos();
		if (!empty($datos)) {
			$descr = 'Sólo pueden imprimirse actas de comisiones para las cuales no haya que seleccionar manualmente el libro de actas para la asignación de folio físico en impresión';
			$pantalla->set_descripcion($descr);
		} else {
			$pantalla->evento('pdf')->desactivar();
			$pantalla->evento('pdf2')->desactivar();
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_filtro_condicion()
	{
		return isset($this->s__filtro) ? $this->s__filtro : null;
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {

			$this->s__datos_filtro = $datos;
			$filtro_impreso = '';
			$filtro_edif = '';
			$filtro_cerrado = '';
			$filtro_propuesta = '';
			if ((isset($datos['impreso']))) {
				$filtro_impreso = $datos['impreso']['condicion'] == 'es_igual_a' ? ' AND ' : ' AND NOT ';
				$filtro_impreso .= ($datos['impreso']['valor'] == comunes::si) ? ' sga_actas.version_impresa <> 0 ' :
						' sga_actas.version_impresa = 0 ';

				$this->dep('filtro')->columna('impreso')->resetear_estado();
			}
			if ((isset($datos['edificacion']))) {
				$filtro_edif = $datos['edificacion']['condicion'] == 'es_igual_a' ? ' AND ' : ' AND NOT ';
				$filtro_edif .= ' sga_comisiones.comision IN (
									SELECT 
										sga_comisiones_bh.comision 
									FROM 
										sga_comisiones_bh,
										sga_espacios,
										sga_espacios_asignacion,
										sga_clases
									WHERE 
										sga_espacios.edificacion = ' . $datos['edificacion']['valor'] . '
										AND sga_espacios_asignacion.espacio = sga_espacios.espacio
										AND sga_clases.clase = sga_espacios_asignacion.clase
										AND sga_comisiones_bh.banda_horaria = sga_clases.banda_horaria
								) ';
				$this->dep('filtro')->columna('edificacion')->resetear_estado();
			}
			if (isset($datos['cerrada_por_docente'])) {
				if ($datos['cerrada_por_docente']['valor'] == comunes::si) {
					$filtro_cerrado = " AND sga_actas.estado = '".acta::estado_cerrada."' "
									 . "AND sga_actas.cerrada_por_docente = '".comunes::si."'";
				} else {
					$filtro_cerrado = " AND sga_actas.estado = '".acta::estado_abierta."' AND sga_actas.cerrada_por_docente = '".comunes::no."'";
				}
			}
			if (isset($datos['propuesta'])) {
				$filtro_propuesta = " AND EXISTS (SELECT 1 FROM sga_comisiones_propuestas WHERE sga_comisiones_propuestas.comision = sga_comisiones.comision AND sga_comisiones_propuestas.propuesta = {$datos['propuesta']['valor']})";
				$this->dep('filtro')->columna('propuesta')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_impreso $filtro_edif $filtro_cerrado $filtro_propuesta";
		} else {
			unset($this->s__datos_filtro);
			$this->s__filtro = '';
		}
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos_filtro);
		unset($this->s__filtro);
		unset($this->s__ids_actas_sel);
	}

	function conf__filtro(toba_ei_filtro $filtro)
	{
		$filtro->columna('codigo')->set_condicion_fija('contiene');		
		$filtro->columna('cerrada_por_docente')->set_condicion_fija('es_igual_a');
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
		if (isset($this->s__datos_filtro)) {
			$filtro->set_datos($this->s__datos_filtro);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_msg_eof($etiqueta = null)
	{
		if (isset($this->s__filtro) && !empty($this->s__filtro)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		} else {
			$indx_msg = $this->indx_msg_eof . '_sinfiltro';
		}
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (isset($this->s__filtro)) {
			$filtro = empty($this->s__filtro) ? 'true' : $this->s__filtro;
			$datos = toba::consulta_php('co_actas')->get_listado($filtro);
			$cuadro->set_datos($datos);
		}
		$etiqueta = guarani_etiquetas::get_tabla('sga_actas');
		$msj = $this->get_msg_eof($etiqueta->get_enf('plural'));
		$cuadro->set_eof_mensaje($msj);
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__ids_actas_sel = $seleccion;
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_clave_seleccionada($this->dep('cuadro')->get_clave_fila($fila, true)));

		if (isset($this->s__operacion_a_volver)) {
			$evento->anular();
		} else {
			$datos = $this->dep('cuadro')->get_datos();
			$propuestas = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($datos[$fila]['comision']), 'propuesta');
			$param_carga_manual_libro = ($datos[$fila]['origen'] == acta::origen_regulares) ? 'cur_carga_manual_libro_regulares' : 'cur_carga_manual_libro_promocion';
			$param_forma_asignacion_folio_fisico = ($datos[$fila]['origen'] == acta::origen_regulares) ? 'cur_forma_asignacion_folio_fisico_regul' : 'cur_forma_asignacion_folio_fisico_promo';
			if ($datos[$fila]['digital'] == comunes::no &&
				guarani::parametros()->get_valor($param_carga_manual_libro, $propuestas) == comunes::si &&
				guarani::parametros()->get_valor($param_forma_asignacion_folio_fisico, $propuestas) == acta::forma_asignacion_folio_fisico_impresion) {
				$evento->anular();
			}
		}
	}

	function es_clave_seleccionada($clave_fila)
	{
		$claves = array();
		if (isset($this->s__ids_actas_sel)) {
			foreach ($this->s__ids_actas_sel as $valor) {
				$claves[] = implode(apex_qs_separador, $valor);
			}
		}
		return in_array($clave_fila, $claves);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		if (!empty($this->s__operacion_a_volver) && !empty($this->s__celda_a_restaurar)) {
			echo toba_js::abrir();
			echo "document.location.href='" .
			toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $this->s__operacion_a_volver, array('volvio_imp' => true), array('celda_memoria' => $this->s__celda_a_restaurar)) . "'\n";
			echo toba_js::cerrar();
		} else {
			parent::evt__cancelar();
		}
	}

	function actualizar_versiones_actas()
	{
		// Se recorren las actas seleccionadas y se almacenan los datos respectivos
		$i = 0;
		$this->s__datos_actas = array();
		foreach ($this->s__ids_actas_sel as $acta) {
			$id_acta = $acta['id_acta'];
			$datos = toba::consulta_php('co_actas')->get_datos_acta($id_acta);
			$this->s__datos_actas[$i]['datos_basicos'] = $datos;
			
			// Atributos para resolver valor de parámetros
			$comision = $datos['comision'];
			$atributos = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');
			
			$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($atributos);

			$params_sistema = array(
				'cur_orden_detalle_actas' => guarani::parametros()->get_valor('cur_orden_detalle_actas', $ras),
			);
			
			if ($this->es_acta_regulares($datos)) {
				$instancia = comision::inst_cursar;
			} else {
				$instancia = comision::inst_promocionar;
				$params_sistema['cur_inserta_alumnos_no_promocionales_en_acta'] = guarani::parametros()->get_valor('cur_inserta_alumnos_no_promocionales_en_acta', $atributos);
			}
			if ($this->es_acta_anulada($datos) || $this->es_acta_cerrada($datos)) {
				$instancia = null;
			}
			
			$datos_alumnos = toba::consulta_php('co_actas')->get_alumnos_acta_cursadas($id_acta, $instancia, '', '', '', false, $params_sistema);
			$this->s__datos_actas[$i]['datos_alumnos'] = $datos_alumnos;

			if ($this->s__datos_actas[$i]['datos_basicos']['digital'] == comunes::no) {
				$forma_asignacion_folio = $this->es_acta_regulares($datos) ? guarani::parametros()->get_valor('cur_forma_asignacion_folio_fisico_regul', $atributos) : $forma_asignacion_folio = guarani::parametros()->get_valor('cur_forma_asignacion_folio_fisico_promo', $atributos);
				// Actualizar datos impresión
				$this->cn()->actualizar_datos_impresion($datos, comunes::no, $forma_asignacion_folio, null, $atributos);
			}

			$i++;
		}
	}

	function evt__pdf()
	{
		if (isset($this->s__ids_actas_sel)) {
			$this->s__volante=false;
			$this->actualizar_versiones_actas();
			$this->s__imprimir = true;
		} else {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un acta'));
			throw guarani::error($mje);
		}
	}

	function evt__pdf2()
	{
		if (isset($this->s__ids_actas_sel)) {
			$this->s__volante=true;
			$this->actualizar_versiones_actas();
			$this->s__imprimir = true;
		} else {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un acta'));
			throw guarani::error($mje);
		}
	}

	// Si se modifica aquí también modificar la función 'vista_jasperreports' de la clase 'php/operaciones/cursadas/actas/imprimir/ci_nav_imprimir_actas_comision.php'
	function vista_jasperreports(toba_vista_jasperreports $report)
	{
		$pdfMerger = new PDFMerger();
		$pdfs = [];

		foreach ($this->s__datos_actas as $acta) {
			$datos_acta = $acta['datos_basicos'];
			$datos_alumnos = $acta['datos_alumnos'];

			/**
			 * Descarga desde Araí-Documentos.
			 * @see https://redmine.siu.edu.ar/redmine/issues/32518 Ticket #32518
			 * @see https://redmine.siu.edu.ar/redmine/issues/47649 Ticket #47649
			 */
			// Si el acta esta cerrada y es digital
			if ($this->es_acta_cerrada($datos_acta) && $this->es_digital($datos_acta)) {
				$fallo = false;
				switch ($datos_acta['estado_documento_digital']) {
					// Si falló la creación del documento en SIU-Araí.
					case arai_documentos::ESTADO_DOCUMENTO_DIGITAL_FALLO:
						$fallo = true;
						break;
					// Si aún no se creo el documento en SIU-Araí.
					case arai_documentos::ESTADO_DOCUMENTO_DIGITAL_PENDIENTE:
						$fallo = true;
						break;
					// Si la creación del documento en SIU-Araí fue exitosa (incluye autorizados).
					case arai_documentos::ESTADO_DOCUMENTO_DIGITAL_EXITO:
					case arai_documentos::ESTADO_DOCUMENTO_DIGITAL_AUTORIZADO:
						// Si no hay documento de SIU-Araí asociado
						if (is_null($datos_acta['id_documento_digital'])) {
							$fallo = true;
						} else {
							$archivo = arai_documentos::descargarDocumentoToFilesystem($datos_acta['id_documento_digital']);
							$pdfs[] = $archivo;
							$pdfMerger->addPDF($archivo);
							// Continuo con la siguiente acta
							continue 2;
						}
						break;
				}

				if ($fallo) {
					toba::logger()->error("id_documento_digital: {$datos_acta['id_documento_digital']}, estado_documento_digital: {$datos_acta['estado_documento_digital']}");
					$mensaje = guarani_mensajes::get('800_fallo_impresion_acta_cursada');
					throw guarani::error($mensaje);
				}
			}

			$titulo = 'ACTA';
			if ($this->es_rectificativa($datos_acta)) {
				$titulo .= ' RECTIFICATIVA ';
			}

			$nombre_parametro = 'cur_actas_';
			if ($this->es_acta_regulares($datos_acta)) {
				$nombre_parametro .= 'regulares';
				$path = acta::plantilla_regulares;
				$titulo .= ' DE REGULARES ';
			} else {
				$nombre_parametro .= 'promocion';
				$path = acta::plantilla_promocion;
				$titulo .= ' DE PROMOCIÓN ';
			}

			$report = new toba_vista_jasperreports();

			// Plantilla a usar
			$path = guarani::get_path_reporte_jasper($path);
			$report->set_path_reporte($path);

			// Cantidad de Ejemplares
			$nombre_parametro .= $this->es_acta_cerrada($datos_acta) ? '_cerradas' : '_abiertas';
			$nombre_parametro .= '_cnt_ejemplares';
			
			$atributos = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($datos_acta['comision']), 'propuesta');
			$cnt_ejemplares = guarani::parametros()->get_valor($nombre_parametro, $atributos);

			//Institucion
			$institucion = current(toba::consulta_php('co_instituciones')->get_institucion_gestionante());
			// Es copia o acta anulada?		
			$copia = $this->es_copia($datos_acta) ? 'ES COPIA' : '';
			$anulada = $this->es_acta_anulada($datos_acta) ? 'ANULADA' : '';
			$text_aux = $copia . $anulada;

			$actividad = $datos_acta['elemento'];
			$comision = $datos_acta['comision'];
			$datos = toba::consulta_php('co_responsables_academicas')->get_ras_actividad_comision($actividad, $comision);
			// Parámetros para el Ev y las fechas de las clases para Asistencia.
			$report->set_parametro('volante', 'S', $this->s__volante);
			$report->set_parametro('titulo', 'S', $titulo);
			$path_logo = guarani::get_path_logo_impresion();
			$report->set_parametro('logo', 'S', $path_logo);
			$report->set_parametro('institucion', 'S', $institucion['nombre']);
			$report->set_parametro('ra_nombre', 'S', $datos['resp_acad_nombre']);
			$report->set_parametro('texto_aux', 'S', $text_aux);
			$str = (is_null($datos_acta['observaciones'])) ? '' : $datos_acta['observaciones'];
			$report->set_parametro('observaciones', 'S', $str);
			$report->set_parametro('version', 'S', $datos_acta['version']);
			$report->set_parametro('comision_nombre', 'S', $datos_acta['comision_nombre']);
			$report->set_parametro('anio_academico_periodo', 'E', $datos_acta['anio_academico_periodo']);
			$report->set_parametro('nro_acta', 'S', $datos_acta['nro_acta']);
			$report->set_parametro('codigo_y_nombre_actividad', 'S', $datos_acta['codigo_y_nombre_actividad']);
			$report->set_parametro('comision_nombre', 'S', $datos_acta['comision_nombre']);
			$report->set_parametro('periodo_nombre', 'S', $datos_acta['periodo_lectivo_nombre']);
			$report->set_parametro('nombre_ubicacion', 'S', $datos_acta['nombre_ubicacion']);
			$report->set_parametro('acta_digital', 'S', $datos_acta['digital']);
			if ($this->es_rectificativa($datos_acta)) {
				$nro_acta_rectificada = toba::consulta_php('co_actas')->get_nro_acta_rectificada($datos_acta['acta_referencia']);
				if (!empty($nro_acta_rectificada)) {
					$report->set_parametro('acta_referencia', 'S', $nro_acta_rectificada[0]['nro_acta_rectificada']);
				}
			}
			$report->set_parametro('fecha_inicio', 'S', $datos_acta['fecha_inicio_periodo_visual']);
			$report->set_parametro('fecha_fin', 'S', $datos_acta['fecha_fin_periodo_visual']);
			$docentes = is_null($datos_acta['docentes']) ? '' : $datos_acta['docentes'];
			$report->set_parametro('docentes', 'S', $docentes);

			$datos = array();
			foreach ($datos_alumnos as $dato_alumno) {
				$estado_acta = $datos_acta['estado'];
				switch ($estado_acta) { // Verifico el estado del acta.
					//Siempre se imprime lo que tiene la cabecera, y sino tiene datos,
					//entonces se asume que se cerro con firma digital.
					case acta::estado_cerrada :
						$default_valor = ($datos_acta['digital'] == comunes::si)? acta::libro_no_aplica : 0;
						$datos_aux = $this->datos_alumnos($default_valor, $datos_acta['id_acta'], $dato_alumno['folio']);
						$dato_alumno['folio_fisico'] = $datos_aux['folio_fisico'];
						$dato_alumno['libro'] = $datos_aux['libro'];
						$dato_alumno['tomo'] = $datos_aux['tomo'];
						break;
					//Cuando el acta esta abierta o anulada debo verificar el 
					//parametro de firma digital.
					case acta::estado_abierta:
					case acta::estado_anulada:
						$default_valor = 0;
						if ($datos_acta['digital'] == comunes::no) {
							$datos_aux = $this->datos_alumnos($default_valor, $datos_acta['id_acta'], $dato_alumno['folio']);
							$dato_alumno['folio_fisico'] = $datos_aux['folio_fisico'];
							$dato_alumno['libro'] = $datos_aux['libro'];
							$dato_alumno['tomo'] = $datos_aux['tomo'];
						} else {
							// Completo los datos del folio, libro y tomo con valores vacíos.
							$dato_alumno['folio_fisico'] = acta::libro_no_aplica;
							$dato_alumno['libro'] = acta::libro_no_aplica;
							$dato_alumno['tomo'] = acta::libro_no_aplica;
						}
						break;
				}
				$datos[] = $dato_alumno;
			}

			$this->dep('cuadro_impresion')->set_datos($datos);
			$xml = $this->dep('cuadro_impresion')->vista_xml();
			$report->set_xml(utf8_e_seguro($xml));
			
			// Si hay que generar mas de una copia se replica el reporte
			for ($i = 0; $i < $cnt_ejemplares; $i++) {
				$report->completar_con_datos();
			}

			// Genero el PDF del Jasper y obtengo la ruta donde se generó
			$report->generar_salida();
			$archivo = $report->get_nombre_archivo_generado();
			$pdfs[] = $archivo;
			$pdfMerger->addPDF($archivo);
		}

		$pathTemp = toba::proyecto()->get_path_temp();
		$archivoTemp = arai_documentos::NOMBRE_ARCHIVO;
		$pathActas = "$pathTemp/$archivoTemp";
		$pdfMerger->merge('file', $pathActas);
		// Elimino los PDFs temporales
		foreach ($pdfs as $pdf) {
			unlink($pdf);
		}

		// Descargo las actas mergeadas
		arai_documentos::downloadFile($pathActas, arai_documentos::CONTENT_DISPOSITION_ATTACHMENT, $archivoTemp, true);
	}

      function datos_alumnos($default_valor, $id_acta, $folio)
        {
        //$datos_folio_acta = toba::consulta_php('co_actas')->get_datos_folio_acta();
        $datos_folio_acta = toba::consulta_php('co_actas')->get_datos_folio_acta($id_acta, $folio);
        $dato_alumno['folio_fisico'] = (isset($datos_folio_acta['folio_fisico'])) ? $datos_folio_acta['folio_fisico'] : $default_valor;
        $dato_alumno['libro'] = (isset($datos_folio_acta['nombre_libro'])) ? $datos_folio_acta['nombre_libro'] : $default_valor;
        $dato_alumno['tomo'] = (isset($datos_folio_acta['nro_tomo'])) ? $datos_folio_acta['nro_tomo'] : $default_valor;
        return $dato_alumno;
    }
      
	//-----------------------------------------------------------------------------------
	//---- Operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function es_acta_regulares($datos)
	{
		return ($datos['origen'] == acta::origen_regulares);
	}

	function es_acta_cerrada($datos)
	{
		return ($datos['estado'] == acta::estado_cerrada);
	}

	function es_acta_anulada($datos)
	{
		return ($datos['estado'] == acta::estado_anulada);
	}

	function es_copia($datos)
	{
		return ($datos['digital'] == comunes::no && $datos['version_impresa'] && $this->es_acta_cerrada($datos));
	}

	function es_rectificativa($datos)
	{
		return ($datos['tipo_acta'] == acta::tipo_rectificativa);
	}

	function es_digital($datos)
	{
		return ($datos['digital'] == comunes::si);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if ($this->s__imprimir) {
			$this->s__imprimir = false;
			echo "
				window.open(vinculador.get_url(null, null, 'vista_jasperreports', null, [{$this->objeto_js}._id])); 
			";
		}
	}

}