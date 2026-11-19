<?php

use SIU\DocumentosCli\Entities\Autorizacion;

/**
 * Constantes para Equivalencias
 *
 * @package Reconocimiento de Actividades
 */
class equivalencia_nucleo
{
	// Tipos de Alcances de la matriz
	const matriz_alcance_general = 'General';
	const matriz_alcance_entre_propuestas = 'Propuesta';
	const matriz_alcance_entre_planes_otra_prop = 'Propuesta-Plan';
	const matriz_alcance_entre_planes_misma_prop = 'Plan';
	const matriz_alcance_entre_versiones = 'Version';
	
	// Tipos de Actividades de un Grupo de Equivalencia
	const grupo_activ_origen = 'O';
	const grupo_activ_destino = 'D';
	
	// Tipos de Equivalencias
	const tipo_regularizada = 'R';
	const tipo_aprobada = 'A';
	const tipo_parcial = 'P';
	const tipo_total = 'T';
	
	// Instancias de Equivalencias
	const equiv_instancia_total = 10;
	const equiv_instancia_regularidad = 11;
	const equiv_instancia_parcial = 12;
	const equiv_instancia_regularidad_correlativas = 14;
	
	// Instancias que aplican a Equivalencias
	const inst_aplica_a_equiv = 'Q';
	
	// Tipos de Origen para Equivalencias Internas.
	const int_origen_examen = 'Examen';
	const int_origen_cursada = 'Cursada';
	const int_origen_promocion = 'Promocion';
	const int_origen_equivalencia = 'Equivalencia';
	const int_origen_resolucion = 'Resolucion';

	// Origenes de Tramites de Equivalencias
	const origen_int_libre = 1;  // Interna Libre 				- Tipo: Interna - Equivalencia Interna Libre. De otra Carrera o Plan de Estudios.
	const origen_int_prop = 2;  // Cambio de Propuesta 			- Tipo: Interna - Equivalencia por cambio de Propuesta.
	const origen_int_plan = 3;  // Cambio de Plan 				- Tipo: Interna - Equivalencia por cambio de plan de estudios de la misma Propuesta
	const origen_int_version = 4; // Cambio de Versión de Plan	- Tipo: Interna - Equivalencia por cambio de versión de plan de estudios de la misma Propuesta
	const origen_ext_libre = 5;  // Externa Libre 				- Tipo: Externa - Equivalencia Externa. Alumno proviene de otra institución
	const origen_ext_pase = 6;  // Pase							- Tipo: Externa	- Equivalencia Externa por pase de una dependencia (Sede/Facultad) de la misma Institución
	const origen_ext_convenio = 7; // Convenio 					- Tipo: Externa	- Equivalencia Externa por Convenio con otra Institución
	const origen_int_cursada_simultanea = 8; // Cursada Simultánea  - Tipo: Interna - Equivalencia por cursado en simultaneo dos o mas propuestas.

	// Estados de Trámites de Equivalencias
	const estado_abierto = 'A';
	const estado_anulado = 'B';
	const estado_cerrado = 'C';
	
	// Tipos de Tramites de Equivalencias
	const tipo_tramite_rectificativo = 'R';
	const tipo_tramite_normal = 'N';
	
	// Estados de Detalles
	const detalle_estado_activo = 'A';
	const detalle_estado_baja = 'B';
	const detalle_estado_invalidado = 'I';
	
	// Valores combo completar equivalencias carga externa masiva
	const autocompletar_campo_nota = 'nota';
	const autocompletar_campo_fecha = 'fecha';
	const autocompletar_campo_acta = 'acta';
	const autocompletar_campo_resultado = 'resultado';
	const autocompletar_campo_libro = 'libro';
	const autocompletar_campo_folio = 'folio';
	
	const autocompletar_para_todos = 'todos';
	const autocompletar_para_sin_datos = 'sin_datos';
	
	// Tipo de Equivalencia
	const equiv_tramite_tipo_interna = 'Interna';
	const equiv_tramite_tipo_externa = 'Externa';
	
	const equiv_utiliza_acta_digital = 'equiv_utiliza_acta_digital';
	
	const titulo_arai_documentos = 'Acta de Equivalencia';
	
	const columnas_a_mostrar_pdf = [
		'equivalencia_tramite', 
		'documento_numero', 
		'fecha_formato_visual', 
		'rectifica_a', 
		'alumno_nombre_completo', 
		'alumno_identificacion', 
		'legajo', 
		'propuesta_alumno', 
		'plan_alumno', 
		'version_alumno', 
		'origen_descr', 
		'estado_nombre', 
		'institucion_nombre'
	];
	
	const etiquetas_pdf = [
		'equivalencia_tramite' => 'Nro. Trámite', 
		'documento_numero' => 'Nro. Resolución', 
		'fecha_formato_visual' => 'Fecha', 
		'rectifica_a' => 'Rectifica a', 
		'alumno_nombre_completo' => 'Apellido y Nombre', 
		'alumno_identificacion' => 'Identificación', 
		'legajo' => 'Legajo', 
		'propuesta_alumno' => 'Propuesta', 
		'plan_alumno' => 'Plan', 
		'version_alumno' => 'Versión', 
		'origen_descr' => 'Tipo de Equivalencia', 
		'estado_nombre' => 'Estado', 
		'institucion_nombre' => 'Institución Externa'
	];
	
	const cuadro_actividades_equivalencia_tramite = '37000482';
	
	/**
	 * Envía un trámite de equivalencia a Araí-Documentos.
	 * @see https://redmine.siu.edu.ar/redmine/issues/31318 Ticket #31318
	 * @see https://redmine.siu.edu.ar/redmine/issues/42326 Ticket #42326
	 * @param int $equivalencia_tramite Id del trámite de equivalencia en la tabla 'sga_equiv_tramite'.
	 * @param array $parametros Parámetros necesarios, consta del siguiente formato:
	 * <code>
		$parametros['utiliza_acta_digital'] = 'S';
		
		// id_tramite de SUDOCU, si no se pasa no se asocia el documento a SUDOCU (EEI)
		$parametros['id_tramite'] = '901e7f46-4e07-42ae-addc-8f75b383aee9';
	 * </code>
	 * @param int[] $ras Array con los ids de las responsables académicas.
	 * @param string|null $archivo Path del archivo PDF del acta, de esta forma no lo genera con Jasper.
	 * @param bool $borrar_archivo Si el archivo debe ser borrado una vez que se lo envió a SIU-Araí Documentos o no.
	 * @param bool $async Si la creación del documento en SIU-Araí es asincrónica o no.
	 * @param bool $msg_error_generico Si muestra un mensaje de error genérico, o el error en concreto.
	 * @throws \Exception Si falla algo en el proceso
	 * @return void.
	 */
	public static function enviar_tramite_equivalencia_arai_documentos($equivalencia_tramite, $parametros, $ras, $archivo = null, $borrar_archivo = true, $async = true, $msg_error_generico = true, $usuario=null)
	{

		$utiliza_acta_digital = ($parametros['utiliza_acta_digital'] == comunes::si);
		
		// Si no se utiliza acta digital salgo
		if (!$utiliza_acta_digital) return false;
			
		$datos_tramite = toba::consulta_php('co_equivalencias')->get_datos_tramite_equivalencia_otorgado($equivalencia_tramite);
		
		// El trámite de equivalencia debe estar cerrado 
		if ($datos_tramite['estado'] != static::estado_cerrado) return false;

		// El trámite debe ser una equivalencia 'Externa'
		if ($datos_tramite['origen_tipo'] != static::equiv_tramite_tipo_externa) return false;
			
		try {

			$data = [];
			$autoridades_firmantes = [];
			$documento_titulo = '';
			$documento_descripcion = '';
			$datos_documento = [];
			$estado_documento_digital = arai_documentos::ESTADO_DOCUMENTO_DIGITAL_PENDIENTE;
			$data['autorizacion']['modelo'] = Autorizacion::MODELO_MULTIPLE_CADENA;
			$data['documento']['tipo'] = arai_documentos::TIPO_DOCUMENTO_ACTA;
			$arai_documentos_class = (\comunes::es_interfaz_autogestion())? \SIU\Chulupi\kernel::localizador()->instanciar('modelo_g3\arai_documentos') : 'arai_documentos';

			// Si el origen viene de los parámetros (resincronización de documentos fallidos)
			if (isset($parametros['origen'])) {
				$origen = $parametros['origen'];
			} else {// sino
				$params['documento_tipo'] = $data['documento']['tipo'];
				$params['id'] = $equivalencia_tramite;
				$params['ras'] = $ras;
				$params['metodo'] = __METHOD__;
				$origen = $arai_documentos_class::get_cuenta_usuario_origen_arai_documentos($params, $usuario);
				$parametros['origen'] = $origen;
			}
                        
                        // Si no se pasa el archivo
			if (is_null($archivo)) {

				try {
					$data['archivo'] = static::generar_pdf($datos_tramite);
				} catch (\Exception $e) {
					$mensaje = guarani_mensajes::get('800_error_generar_pdf', [$e->getMessage()]);
					throw new \Exception($mensaje);
				}
				
			} elseif (!file_exists($archivo)) {
				// Si no existe el archivo
				$mensaje = guarani_mensajes::get('800_pdf_inexistente', [$archivo]);
				throw new \Exception($mensaje);
			} else {
				// Si existe el archivo
				$data['archivo'] = $archivo;
			}
			
			//Seteo titulo del documento y su descripcion
			$documento_titulo = static::get_titulo_documento_arai($datos_tramite);
			$documento_descripcion = static::get_descripcion_documento_arai($datos_tramite);
			
			$data['documento']['titulo'] = $documento_titulo;
			$data['documento']['descripcion'] = $documento_descripcion;
			// Ver Ticket #42326 (https://redmine.siu.edu.ar/redmine/issues/42326)
			$data['documento']['id_tramite'] = ($parametros['id_tramite'] ?? null);
			/*
             * @see https://documentacion.siu.edu.ar/documentos/docs/repo-docs/#request 
             * Este campo se utiliza para almacenar en formato json, los datos que requiera la aplicación que genera el documento.
             */
			$data['documento']['referencia_interna'] = [
				'equivalencia_tramite' => $equivalencia_tramite,
				'sistema' => arai_documentos::ID_INSTALACION,
				'parametros' => $parametros,
				'ras' => $ras
			];
			$data['documento']['identificador'] = $equivalencia_tramite;
            /*
             * @see https://documentacion.siu.edu.ar/documentos/docs/repo-docs/#request 
             * Atributos adicionales del documento.
             */
			$data['documento']['atributos'] = [];

			$autoridades_firmantes = $arai_documentos_class::get_autoridades_firmantes(comunes::si, $ras);
			
			static::validar_autorizantes_asociados_acta($ras);

			$secuencia = 1;

			foreach ($autoridades_firmantes as $autoridad_firmante) {
				$data['autorizantes'][] = [
					'usuario' => $autoridad_firmante,
					'nivel_autorizacion' => Autorizacion::NIVEL_FIRMA,
					'secuencia' => $secuencia
				];
				$secuencia++;
			}

			$data['origen']['id_usuario_sso'] = $origen;
			$data['origen']['cuenta_usuario'] = $origen;
			// Se le debe pasar 'id_instalacion' ya que se muestra en la estampa
			$data['origen']['id_instalacion'] = arai_documentos::ID_INSTALACION_ESTAMPA;

			try {
				
				$documento = $arai_documentos_class::armarDocumento($data, $async);

				// Si la creación es de forma asincrónica
				if ($async) {
					// Si se trata de Autogestión se genera el documento con callback
					if (\comunes::es_interfaz_autogestion()) {
						$arai_documentos_class::generateAndPostAsync($documento);
					} else {// Si se trata de Gestión se genera el documento sin callback
						$arai_documentos_class::postDocumentoAsync($documento);
					}
				} else {// Si la creación es de forma sincrónica
					$datos_documento = $arai_documentos_class::postDocumento($documento);
				}
				
			} catch (\Exception $e) {
				$mensaje = $e->getMessage();
				throw new \Exception(utf8_decode($mensaje));
			}

			if (!$async) {
				guarani::act('act_equivalencias')->actualizar_documento_digital($equivalencia_tramite, $datos_documento['uid_documento']);
				$estado_documento_digital = arai_documentos::ESTADO_DOCUMENTO_DIGITAL_EXITO;
                                // Marco el documento como creado en SIU-Araí
                                guarani::act('act_arai_documentos')->actualizar_estado_documento($datos_documento['uid_documento'], false, arai_documentos::SOLICITUD_AUTORIZADA);
                                // Recupero los movimientos del documento
                                $movimientos_documento = arai_documentos::getMovimientosDocumento($datos_documento['uid_documento']);
                                // Tomo las autorizaciones
                                $autorizaciones = arai_documentos::getAutorizacionesByMovimientos($movimientos_documento);

                                // Registro las autorizaciones del documento
                                if (!empty($autorizaciones)) {
                                    guarani::act('act_arai_documentos')->eliminar_autorizaciones_documento($datos_documento['uid_documento']);
                                    guarani::act('act_arai_documentos')->agregar_autorizaciones_documento($datos_documento['uid_documento'], $autorizaciones);
                                }
			}
			
		} catch (\Exception $e) {

			$estado_documento_digital = arai_documentos::ESTADO_DOCUMENTO_DIGITAL_FALLO;
			$parametros_documento_digital['parametros'] = $parametros;
			$parametros_documento_digital['ras'] = $ras;
			guarani::act('act_equivalencias')->actualizar_parametros_documento_digital($equivalencia_tramite, $parametros_documento_digital);
			toba::logger()->error($e);
			if ($msg_error_generico) {
				$mensaje = guarani_mensajes::get('800_fallo_creacion_documento_arai');
			} else {
				$mensaje = $e->getMessage();
			}
			throw guarani::error($mensaje);
			
		} finally {

                        // Borro el PDF del acta
                        if ($borrar_archivo && isset($data['archivo']) && file_exists($data['archivo'])) {
                            unlink($data['archivo']);
                        }
			
			// Actualizo el estado del documento digital asociado al acta
			guarani::act('act_equivalencias')->actualizar_estado_documento_digital($equivalencia_tramite, $estado_documento_digital);
		}
	}
	
   /**
	* Genera el PDF.
	* @param array $datos_tramite Datos del trámite de equivalencia en la tabla 'sga_equiv_tramite'.
	* @throws \Exception Si falla algo en el proceso
	* @return string Path al PDF.
	*/
	public static function generar_pdf($datos_tramite)
	{
		
		try {
			
			$report = new toba_vista_jasperreports();

			//Creo la plantilla (jrxml) para la creacion del reporte
			$jasper = new guarani_jasper();
			$jasper->set_template_nombre(static::get_template());

			// Parametros 
			$parametro['nombre'] = 'logo';
			$parametro['tipo'] = 'S';
			$pars[] = $parametro;		
			$jasper->set_parametros($pars);
			
			$id = ['componente' => static::cuadro_actividades_equivalencia_tramite, 'proyecto' => toba::proyecto()->get_id()];
			$tipo = 'toba_ei_cuadro';
			$cuadro = toba_constructor::get_runtime($id, $tipo);
			$cuadro->inicializar();
			$detalle_tramite_equivalencia = toba::consulta_php('co_equivalencias')->get_detalle_actividades_tramite_equivalencia($datos_tramite['equivalencia_tramite']);
			$cuadro->set_datos_parent($detalle_tramite_equivalencia);
		
			// Campos 
			$columnas = $cuadro->get_columnas();
			$datos_fields = [];			
			foreach ($columnas as $columna) {
				/*
				 * Si el formateo de la columna es de tipo date, entonces se envia el
				 * tipo D para que utilice esto al imprimir el valor de la columna
				 */			
				$tipo = 'S';
				if(isset($columna['formateo']) && $columna['formateo'] == 'fecha') {
					$tipo = 'D';
				}
				$datos_fields[] = array('nombre' => $columna['clave'], 'titulo' => $columna['titulo'], 'grupo' => $columna['grupo'], 'tipo' => $tipo);
			}		
			$jasper->set_fields($datos_fields);

			//Orientacion (Si cantidad de columnas > 5 se cambia la orientacion a horizontal)
			if (!isset($reporte_vertical)) {
				$reporte_vertical = (guarani_arreglos::count($columnas) < 6);
			}
			$jasper->set_orientacion_vertical($reporte_vertical);

			// Grupos
			$grupos = $cuadro->get_indice_cortes();
			$datos = [];
			if (isset($grupos)) {
				foreach ($grupos as $g => $cols) {
					$cols_aux_id = explode(',', $cols['columnas_id']);
					foreach($cols_aux_id as $field) {
						$campo = trim($field);
						$jasper->add_fields_st(array('nombre' => $campo));
					}
					$cols_aux_desc = explode(',', $cols['columnas_descripcion']);
					foreach($cols_aux_desc as $field) {
						$campo = trim($field);
						$jasper->add_fields_st(array('nombre' => $campo));
					}
					$datos[] = array (
										'nombre'    => $cols['identificador'],
										'campo'     => $cols_aux_id,
										'campo_desc'=> $cols_aux_desc,
										'titulo'    => $cols['descripcion'],
										'pie'       => ($cols['pie_contar_filas'] == '1')
							  );
				}
			}
			$jasper->set_grupos($datos);
			// Titulo 
			$titulo = static::get_titulo();
			$titulo = (empty($titulo)) ? 'Reporte' : $titulo;
			$jasper->set_titulo($titulo);
			// Encabezado
			$jasper->set_ancho_etiquetas_encabezado(static::ancho_etiquetas_encabezado());
			$jasper->set_datos_encabezado(static::datos_encabezado($datos_tramite));
			//Obtengo el archivo jrxml y lo paso como plantilla al reporte
			$archivo = toba::proyecto()->get_path_temp().'/'.static::cuadro_actividades_equivalencia_tramite.'.jrxml';
			$jasper->set_jrxml_archivo($archivo);

			//Reporte
			//Paso la plantilla 
			$report->compilar_reporte($archivo);
			//Obtengo los datos del cuadro y se lo paso al reporte 
			//$xml = static::vista_xml();
			$xml = $cuadro->vista_xml_parent();
			/*
			// Queda comentado esto por si a futuro se decide guardar los datos pasados al reporte, como debug en un archivo
			$archivo = toba::proyecto()->get_path_temp() .'/'. guarani::get_id_operacion() . '_datos.xml';
			//Escribo el xml con los datos
			if (file_put_contents($archivo, utf8_encode($xml)) === false) {
				throw new toba_error("Error al guardar archivo de datos xml", "No es posible escribir en ".$archivo);
			}		
			$report->set_archivo_xml($archivo);
			*/
			$report->set_xml(utf8_e_seguro($xml));

			//Paso los parametros
			//Obtengo el logo a imprimir
			$path_logo = guarani::get_path_logo_impresion();
			$report->set_parametro('logo', 'S', $path_logo);
			
			$report->generar_salida();

            return $report->get_nombre_archivo_generado();
		
		} catch (\Exception $e) {
                
			toba::logger()->error($e);
			throw guarani::error($e->getMessage());
                
        }
	}
	
	public static function get_template()
	{		
		return toba::proyecto()->get_path().'/exportaciones/jasper/template_guarani.jrxml';
	}
	
	public static function get_titulo()
	{		
		return '';
	}
	
	public static function ancho_etiquetas_encabezado()
	{
		return 100;
	}
	
	public static function datos_encabezado($datos_tramite)
	{		
		$datos_encabezado = [
		  1 => 
		  [
			'equivalencia_tramite' => 
			[
			  'etiqueta' => static::etiquetas_pdf['equivalencia_tramite'],
			  'valor' => $datos_tramite['equivalencia_tramite'],
			],
			'documento_numero' => 
			[
			  'etiqueta' => static::etiquetas_pdf['documento_numero'],
			  'valor' => $datos_tramite['documento_numero'],
			],
			'fecha_formato_visual' => 
			[
			  'etiqueta' => static::etiquetas_pdf['fecha_formato_visual'],
			  'valor' => $datos_tramite['fecha_formato_visual'],
			],
		  ],
		  2 => 
		  [
			'alumno_nombre_completo' => 
			[
			  'etiqueta' => static::etiquetas_pdf['alumno_nombre_completo'],
			  'valor' => $datos_tramite['alumno_nombre_completo'],
			],
		  ],
		  3 => 
		  [
			'alumno_identificacion' => 
			[
			  'etiqueta' => static::etiquetas_pdf['alumno_identificacion'],
			  'valor' => $datos_tramite['alumno_identificacion'],
			],
		  ],
		  4 => 
		  [
			'propuesta_alumno' => 
			[
			  'etiqueta' => static::etiquetas_pdf['propuesta_alumno'],
			  'valor' => $datos_tramite['propuesta_alumno'],
			],
		  ],
		  5 => 
		  [
			'plan_alumno' => 
			[
			  'etiqueta' => static::etiquetas_pdf['plan_alumno'],
			  'valor' => $datos_tramite['plan_alumno'],
			],
		  ],
		  6 => 
		  [
			'version_alumno' => 
			[
			  'etiqueta' => static::etiquetas_pdf['version_alumno'],
			  'valor' => $datos_tramite['version_alumno'],
			],
		  ],
		  7 => 
		  [
			'origen_descr' => 
			[
			  'etiqueta' => static::etiquetas_pdf['origen_descr'],
			  'valor' => $datos_tramite['origen_descr'],
			],
		  ],
		  8 => 
		  [
			'institucion_nombre' => 
			[
			  'etiqueta' => static::etiquetas_pdf['institucion_nombre'],
			  'valor' => $datos_tramite['institucion_nombre'],
			],
		  ],
		  9 => 
		  [
			'estado_nombre' => 
			[
			  'etiqueta' => static::etiquetas_pdf['estado_nombre'],
			  'valor' => $datos_tramite['estado_nombre'],
			],
		  ],
		];
		
		// Datos opcionales
		if (!empty($datos_tramite['legajo'])) {
			$datos_encabezado[3]['legajo'] = [
			  'etiqueta' => static::etiquetas_pdf['legajo'],
			  'valor' => $datos_tramite['legajo']
			];
		}
		
		if (!empty($datos_tramite['rectifica_a'])) {
			$datos_encabezado[7]['rectifica_a'] = [
			  'etiqueta' => static::etiquetas_pdf['rectifica_a'],
			  'valor' => $datos_tramite['rectifica_a']
			];
		}
		
		return $datos_encabezado;
	}
	
   /**
	* Valida que existan autorizantes.
    * @see https://redmine.siu.edu.ar/redmine/issues/31318 Ticket #31318
	* @param int[]  $ras	Array con los ids de las responsables académicas.
	* @throws \Exception	Si no existen autorizantes.
	*/
	public static function validar_autorizantes_asociados_acta($ras)
	{
		$arai_documentos_class = (\comunes::es_interfaz_autogestion())? \SIU\Chulupi\kernel::localizador()->instanciar('modelo_g3\arai_documentos') : 'arai_documentos';
		$autoridades_firmantes = $arai_documentos_class::get_autoridades_firmantes(comunes::si, $ras);

		// Si no existen autorizantes lazo una excepción 
		if (empty($autoridades_firmantes)) {
			throw guarani::error(guarani_mensajes::get('800_no_existen_autorizantes'));
		}
	}

       /**
        * Devuelve el título para el documento de Araí.
        * @param array $datos_tramite Datos del trámite.
        * @return string Título para el documento de Araí.
        */
        public static function get_titulo_documento_arai($datos_tramite)
        {
            return static::titulo_arai_documentos. " " .$datos_tramite['documento_numero'];
        }

       /**
        * Devuelve la descripción para el documento de Araí.
        * @param array $datos_tramite Datos del trámite.
        * @return string Descripción para el documento de Araí.
        */
        public static function get_descripcion_documento_arai($datos_tramite)
        {
            return "{$datos_tramite['alumno_nombre_completo']} - {$datos_tramite['propuesta_nombre']}";
        }
}