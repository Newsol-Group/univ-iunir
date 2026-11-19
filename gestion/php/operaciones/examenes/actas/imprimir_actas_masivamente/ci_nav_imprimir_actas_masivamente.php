<?php

use Clegginabox\PDFMerger\PDFMerger;

class ci_nav_imprimir_actas_masivamente extends guarani_pers_ci
{
	protected $indx_msg_eof = '';
	protected $s__imprimir = false;
	protected $s__filtro;
	protected $s__datos_filtro;
	protected $s__operacion_a_volver;
	protected $s__celda_a_restaurar;
	protected $s__ids_actas_sel;
	protected $s__volante;
	
	// Arreglo conteniendo los datos de cada una de las actas seleccionadas y su cantidad de alumnos
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
			foreach ($datos['datos'] as $dato) {
				$this->s__ids_actas_sel[]['id_acta'] = $dato['id_acta'];
				$this->s__filtro .= ',' . $dato['id_acta'];
			}
			$this->s__filtro .= ')';
		}
	}

	function conf()
	{
		$this->indx_msg_eof = '800_eof_cuadro';
		$this->dep('filtro')->set_sin_filtrado_inicial();
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
			$descr = 'Sólo pueden imprimirse actas de exámenes para las cuales no haya que seleccionar manualmente el libro de actas para la asignación de folio físico en impresión';
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
			$filtro_instancia = '';
			$filtro_edif = '';
			$filtro_cerrado = '';
			$filtro_propuesta = '';
			if ((isset($datos['impreso']))) {
				$filtro_impreso = $datos['impreso']['condicion'] == 'es_igual_a' ? ' AND ' : ' AND NOT ';
				$filtro_impreso .= ($datos['impreso']['valor'] == comunes::si) ? ' sga_actas.version_impresa <> 0 ' :
						' sga_actas.version_impresa = 0 ';

				$this->dep('filtro')->columna('impreso')->resetear_estado();
			}

			if (isset($datos['instancias_nombre'])) {
				$filtro_instancia = ' AND EXISTS (	SELECT 	* 
													FROM 	sga_mesas_examen_instancias 
													WHERE 	sga_mesas_examen_instancias.mesa_examen = sga_mesas_examen.mesa_examen';
				$filtro_instancia .= $datos['instancias_nombre']['condicion'] == 'es_igual_a' ? " AND sga_mesas_examen_instancias.instancia = '{$datos['instancias_nombre']['valor']}')" : " AND sga_mesas_examen_instancias.instancia <> '{$datos['instancias_nombre']['valor']}')";

				$this->dep('filtro')->columna('instancias_nombre')->resetear_estado();
			}

			if ((isset($datos['edificacion']))) {
				$filtro_edif = $datos['edificacion']['condicion'] == 'es_igual_a' ? ' AND ' : ' AND NOT ';
				$filtro_edif .= ' sga_llamados_mesa.llamado_mesa IN ( SELECT	sga_espacios_asignacion.llamado_mesa 
																		FROM	sga_espacios_asignacion, 
																				sga_espacios
																		WHERE	sga_espacios_asignacion.espacio = sga_espacios.espacio
																				AND sga_espacios_asignacion.llamado_mesa IS NOT NULL 
																				AND sga_espacios.edificacion = ' . $datos['edificacion']['valor'] . '
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
				$this->dep('filtro')->columna('cerrada_por_docente')->resetear_estado();
			}
			if (isset($datos['propuesta'])) {				
				$filtro_propuesta = " AND EXISTS (SELECT 1 FROM sga_mesas_examen_propuestas WHERE sga_mesas_examen_propuestas.mesa_examen = sga_mesas_examen.mesa_examen AND sga_mesas_examen_propuestas.propuesta = {$datos['propuesta']['valor']})";
				$this->dep('filtro')->columna('propuesta')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_impreso $filtro_instancia $filtro_edif $filtro_cerrado $filtro_propuesta";
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
		// Si el perfil de datos del usuario pertenece al dominio "Propuestas" es obligatorio filtrar los conceptos por ubicación.
		if (usuario::tiene_perfil_por_propuesta()) {
			$filtro->set_ubicacion_obligatoria();
		}
		$filtro->columna('codigo')->set_condicion_fija('contiene');
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
			// Se listan los Llamados de Mesas de Examen que estén en Estado Activo (no Suspendido).
			$where = "sga_llamados_mesa.estado = '" . mesa_de_examen::mesa_estado_activo . "'";
			$filtro = $this->get_filtro_condicion() . ' AND ' . $where;
			$datos = toba::consulta_php('co_actas')->get_listado_rep_imprimir_actas_masivo($filtro);
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
		$datos = $this->dep('cuadro')->get_datos();
		$propuestas = aplanar_matriz(toba::consulta_php('co_mesas_de_examen')->get_propuestas_mesa_examen($datos[$fila]['mesa_examen']), 'propuesta');
		if ($datos[$fila]['digital'] == comunes::no &&
			guarani::parametros()->get_valor('exa_carga_manual_libro_examen', $propuestas) == comunes::si && // Carga manual de Libro
			guarani::parametros()->get_valor('exa_forma_asignacion_folio_fisico', $propuestas) == acta::forma_asignacion_folio_fisico_impresion) {
			$evento->anular();
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
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_actas_seleccionadas()
	{
		$filtro_in = guarani_sql::armar_condicion_in($this->s__ids_actas_sel, 'sga_actas', 'id_acta');
		return toba::consulta_php('co_actas')->get_listado_rep_imprimir_actas_masivo($filtro_in);
	}

	function actualizar_versiones_actas()
	{
		// Se recorren las actas seleccionadas y se obtienen datos necesarios
		$i = 0;
		foreach ($this->s__ids_actas_sel as $acta) {
			$id_acta = $acta['id_acta'];
			$filtro = 'sga_actas.id_acta = ' . $id_acta;
			$datos = current(toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($filtro));

			// Parámetros
			$mesa_examen = $datos['mesa_examen'];
			$propuestas = aplanar_matriz(toba::consulta_php('co_mesas_de_examen')->get_propuestas_mesa_examen($mesa_examen), 'propuesta');
			$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);
			
			$this->s__datos_actas[$i]['atributos'][parametro::nivel_propuesta] = $propuestas;
			$this->s__datos_actas[$i]['atributos'][parametro::nivel_ra] = $ras;
			
			$params_sistema = array('exa_orden_detalle_actas' => guarani::parametros()->get_valor('exa_orden_detalle_actas', $ras));
			$datos_alumnos = toba::consulta_php('co_actas')->get_alumnos_acta_examen($id_acta,  $datos['estado'], $params_sistema);

			$this->s__datos_actas[$i]['datos_basicos'] = $datos;
			$this->s__datos_actas[$i]['datos_alumnos'] = $datos_alumnos;

			if ($this->s__datos_actas[$i]['datos_basicos']['digital'] == comunes::no) {
				$forma_asignacion_folio_fisico = guarani::parametros()->get_valor('exa_forma_asignacion_folio_fisico', $propuestas); 	
				$agrega_folios_fisicos_extra = guarani::parametros()->get_valor('exa_agrega_folios_fisicos_extra', $propuestas);
				// Se actualizan datos impresión
				$this->cn()->actualizar_datos_impresion($datos, guarani_arreglos::count($datos_alumnos), comunes::no, $forma_asignacion_folio_fisico, $agrega_folios_fisicos_extra);
			}

			$i++;			
		}
	}
	
	function actualizar_cerrado_docente()
	{
		$i = 0;
		foreach ($this->s__ids_actas_sel as $acta) {
			$id_acta = $acta['id_acta'];
			$filtro = 'sga_actas.id_acta = ' . $id_acta;
			$datos = current(toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($filtro));			
			$this->cn()->actualizar_cerrado_docente($datos);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function vista_jasperreports(toba_vista_jasperreports $report)
	{

		$pdfMerger = new PDFMerger();
		$pdfs = [];
		
		foreach ($this->s__datos_actas as $acta) {
			$datos_acta = $acta['datos_basicos'];
			$datos_alumnos = $acta['datos_alumnos'];

			/**
			 * Descarga desde Araí-Documentos.
			 * @see https://redmine.siu.edu.ar/redmine/issues/30923 Ticket #30923
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
					$mensaje = guarani_mensajes::get('800_fallo_impresion_acta_examen');
					throw guarani::error($mensaje);
				}
			}

			$report = new toba_vista_jasperreports();
			
			// Se establece la plantilla a usar
			$path = guarani::get_path_reporte_jasper(acta::plantilla_examenes);
			$report->set_path_reporte($path);

			//Institucion
			$institucion = current(toba::consulta_php('co_instituciones')->get_institucion_gestionante());
			$actividad = $datos_acta['elemento'];
			$llamado = $datos_acta['llamado_mesa'];
			$datos = toba::consulta_php('co_responsables_academicas')->get_ras_actividad_llamado($actividad, $llamado);
			// Cantidad de Ejemplares
			$cnt_ejemplares = guarani::parametros()->get_valor('exa_actas_examen_abiertas_cnt_ejemplares', $acta['atributos'][parametro::nivel_propuesta]);
			$report->set_parametro('volante', 'S', $this->s__volante);
			$path_logo = guarani::get_path_logo_impresion();
			$report->set_parametro('logo', 'S', $path_logo);
			$report->set_parametro('institucion', 'S', $institucion['nombre']);
			$report->set_parametro('ra_nombre', 'S', $datos['resp_acad_nombre']);
			$str = (is_null($datos_acta['observaciones'])) ? '' : $datos_acta['observaciones'];
			$report->set_parametro('observaciones', 'S', $str);
			$report->set_parametro('nro_acta', 'S', $datos_acta['nro_acta']);
			$report->set_parametro('fecha', 'S', $datos_acta['fecha_formato_visual']);
			$titulo = 'ACTA';
			$rectifica_a = "null";
			if ($this->es_rectificativa($datos_acta)) {
				$rectifica_a = $datos_acta['rectifica_a'];
				$titulo .= ' RECTIFICATIVA DE EXAMEN';
			} else {
				$titulo .= ' DE EXAMEN';
			}
			$report->set_parametro('rectifica_a', 'S', $rectifica_a);
			$report->set_parametro('titulo', 'S', $titulo);
			$report->set_parametro('codigo_nombre_actividad', 'S', $datos_acta['cod_nombre_actividad']);
			$str = (is_null($datos_acta['turno_nombre'])) ? ' - ' : $datos_acta['turno_nombre'];
			$report->set_parametro('turno_examen', 'S', $str);
			$report->set_parametro('mesa', 'S', $datos_acta['mesa_nombre']);
			$str = (is_null($datos_acta['llamado_nombre'])) ? ' - ' : $datos_acta['llamado_nombre'];
			$report->set_parametro('nombre_llamado', 'S', $str);
			$report->set_parametro('anio_academico', 'E', $datos_acta['anio_academico']);
			$report->set_parametro('nombre_ubicacion', 'S', $datos_acta['ubicacion_nombre']);
			$report->set_parametro('version', 'S', $datos_acta['version']);
			$report->set_parametro('acta_digital', 'S', $datos_acta['digital']);
			
			// Docentes y Roles (Maximo 3 por acta)
			// le agrego un parametro para saber que se va a imprimir en un documento oficial y debe resperat el parametro
                        $docentes_actas = toba::consulta_php('co_mesas_de_examen')->get_docentes_mesa($datos_acta['llamado_mesa'],true);
                        $docentes       = "";
                        for ($i = 0; $i < 3; $i++) {
                            $rol            = '';
                            $docente_nombre = "";
                            if (isset($docentes_actas[$i]['docente_nombre'])) {
                                $docente_nombre = $docentes_actas[$i]['docente_nombre'];
                                $rol            = $docentes_actas[$i]['nombre'];
                                $docentes       .= "{$docente_nombre} ({$rol}) ";
                            }
                            $par = 'docente_'.($i + 1);
                            $report->set_parametro($par, 'S', $docente_nombre);
                            $par = 'rol_'.($i + 1);
                            $report->set_parametro($par, 'S', $rol);
                        }
                        $report->set_parametro('docentes', 'S', $docentes);

                        // Se agregan los datos de los alumnos
			// Obtengo los datos de Libro y Tomo para cada linea (alumno) ya que pueden variar para pasarselos al reporte
                        // No hace falta mirar el parametro de firma digital, ya que se trata solamente de actas cerradas,
                        // por lo que se asume que si no vienen datos de cabecera, se imprimió con firma digital.
				$datos = array();
				foreach ($datos_alumnos as $dato_alumno) {
                            
                            $estado_acta = $datos_acta['estado'];
                            
                            /*
                            * Verifico el estado del acta.
                            */            
                            switch ($estado_acta) {
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

			// Si hay que generar mas de una copia replico el reporte
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
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__pdf()
	{
		if (!isset($this->s__ids_actas_sel)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un acta'));
			throw guarani::error($mje);
		} else {
			$this->s__volante=false;
			$this->actualizar_versiones_actas();
			$this->actualizar_cerrado_docente();
			$this->s__imprimir = true;
		}
	}

	function evt__pdf2()
	{
		if (!isset($this->s__ids_actas_sel)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un acta'));
			throw guarani::error($mje);
		} else {
			$this->s__volante=true;
			$this->actualizar_versiones_actas();
			$this->s__imprimir = true;
		}
	}
	function get_alumnos_acta_examen($id_acta = null, $estado = null)
	{
		$params_sistema = array('exa_orden_detalle_actas' => guarani::parametros()->get_valor('exa_orden_detalle_actas'));
		return toba::consulta_php('co_actas')->get_alumnos_acta_examen($id_acta, $estado, $params_sistema);
	}

	//-----------------------------------------------------------------------------------
	//---- Operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------

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

	function es_rectificativa($datos_acta)
	{
		return ($datos_acta['tipo_acta'] == acta::tipo_rectificativa);
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