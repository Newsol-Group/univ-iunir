<?php

namespace siu\operaciones\mensajes;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\errores\error_guarani;
use siu\extension_kernel\controlador_g3w2;
use \siu\modelo\datos\catalogo;
use SIU\Chulupi\nucleo\NuevoRequest;
use siu\modelo\transacciones\mensajes;

class controlador extends controlador_g3w2
{
	function get_clase_vista()
	{
		switch ($this->accion) {
			case 'enviar_mensaje':
				return 'vista_envio_mensaje';
		}
		return 'vista_lista_mensajes';
	}

	/**
	 * @return \siu\modelo\transacciones\mensajes
	 */
	function modelo()
	{
		return guarani::mensajes();
	}

	function accion__index()
	{
		$lista_mensajes = $this->vista()->pagelet('lista_mensajes');
		$lista_mensajes->set_estado_info(pagelet_lista_mensajes::E_RECIBIDOS);
	}

	function accion__enviados()
	{
		$lista_mensajes = $this->vista()->pagelet('lista_mensajes');
		$lista_mensajes->set_estado_info(pagelet_lista_mensajes::E_ENVIADOS);
	}

	/*
	 * Sanitiza datos para evitar ataques XSS
	 * @see https://diego.com.es/ataques-xss-cross-site-scripting-en-php
	 * @see https://manuais.iessanclemente.net/index.php/Evitar_ataques_XSS_y_CSRF_con_PHP
	 * @see https://donnierock.com/2013/01/04/evitar-ataques-xss-con-php-input-filter/
	 * @param string $datos Datos a sanitizar.
	 * @return string Datos sanitizados.
	 */
	protected function sanitizar_datos($datos)
	{
		$tags_permitidos = [
                    'p', 'strong', 'em', 'u', 'span',
                    'ol', 'ul', 'li', 'br', 'b', 'i', 'blockquote',
                    'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
                ];
		$atributos_permitidos = ['style'];
		return kernel::seguridad()->sanitizeXSS($datos, $tags_permitidos, $atributos_permitidos);
	}
	
	function accion__leer_mensaje()
	{
		$anuncio = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		$msg = $this->modelo()->evt__leer_mensaje($anuncio);

		// Sanitizo el mensaje para evitar ataques XSS
		$msg = $this->sanitizar_datos($msg);
		
		// Limpio la cache de mensajes para que se actualice la cantidad
		guarani::persona()->limpiar_cache_cantidad_mensajes();
		$datos = guarani::persona()->get_cantidad_mensajes();
		$this->render_ajax_html($msg, array(
			'cant_mensajes' => array(
				'leidos' => $datos['leidos'],
				'total' => $datos['total'],
			),
		));
	}

	function accion__leer_mensaje_enviado()
	{
		$id_anuncio = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		$msg = $this->modelo()->evt__leer_mensaje_enviado($id_anuncio);
		
		// Sanitizo el mensaje para evitar ataques XSS
		$msg = $this->sanitizar_datos($msg);
		
		$datos = guarani::persona()->get_cantidad_mensajes();
		$this->render_ajax_html($msg, array(
			'cant_mensajes' => array(
				'leidos' => $datos['leidos'],
				'total' => $datos['total'],
			),
		));
	}

	function accion__enviar_mensaje()
	{
		if (!kernel::persona()->es_docente())
			throw new error_guarani('error');

		if (kernel::request()->isPost()) {
			try {
				$tipo_anuncio = $this->validate_param('tipo', 'post', validador::TIPO_ALPHA);
				$titulo = trim(kernel::request()->getPost('titulo'));
				$mensaje = trim(kernel::request()->getPost('mensaje'));
				
				// Sanitizo el mensaje para evitar ataques XSS
				$titulo = $this->sanitizar_datos($titulo);
				$mensaje = $this->sanitizar_datos($mensaje);

				if (empty($tipo_anuncio))
					throw new error_guarani('No ha elegido el tipo de destinatario.');
				if (empty($titulo))
					throw new error_guarani('Ingrese un título para el mensaje.');
				if (strlen($titulo) > 100)
					throw new error_guarani('El título no puede tener mas de 100 caracteres.');
				if (empty($mensaje))
					throw new error_guarani('Ingrese un mensaje.');
					
				$formato_fecha = '!d/m/Y';
				$fecha_desde = $this->validate_param('fecha_desde', 'post', validador::TIPO_DATE, array('format' => $formato_fecha));
				$fecha_hasta = $this->validate_param('fecha_hasta', 'post', validador::TIPO_DATE, array('format' => $formato_fecha));

				$f_desde = \DateTime::createFromFormat($formato_fecha, $fecha_desde);
				$f_hasta = \DateTime::createFromFormat($formato_fecha, $fecha_hasta);
				$f_actual = $datetime_fecha_actual = new \DateTime('today');

				if ($f_desde < $f_actual) {
					throw new error_guarani("La fecha desde '$fecha_desde' no puede ser menor a la fecha actual");
				}

				if ($f_desde >= $f_hasta) {
					throw new error_guarani("La fecha desde '$fecha_desde' no puede ser mayor a fecha hasta '$fecha_hasta'");
				}

				$personasDestinatarios = [];
				$dominios = array();
				
				switch ($tipo_anuncio) {
					case 'O':
						$alcance = \mensajes::alumnos_comision;
						$comision = $this->validate_param('comision', 'post', validador::TIPO_ALPHANUM);
						$anio_academico = $this->validate_param('anio_academico', 'post', validador::TIPO_ALPHANUM);
						$subcomision = $this->validate_param('subcomision', 'post', validador::TIPO_ALPHANUM);
						$destinatarios = $this->modelo()->get_destinatarios($anio_academico);

						foreach ($destinatarios['comisiones'] as $dato) {
							if ($dato['comision_id'] == $comision) {
								$destino = $dato['comision'];
								$dominio_array['ubicacion'] = $dato['ubicacion'];
								$dominio_array['anio_academico'] = $dato['anio_academico'];
								$dominio_array['comision'] = $dato['comision'];
								$dominios[] = $dominio_array;

								break;
							}
						}
						
						$personasDestinatariosComision = [];
						// Si no existen subcomisiones envío a toda la comisión
						if ($subcomision == mensajes::TODA_COMISION) {
							$personasDestinatariosComision = catalogo::consultar('mensajes', 'get_personas_destinatarios_comision', ['comision' => $destino]);
						// Si existen subcomisiones y se eligen "Todas" envío a todas las subcomisions del docente
						} elseif ($subcomision == mensajes::TODAS_SUBCOMISIONES) {
							$personasDestinatariosComision = catalogo::consultar('mensajes', 'get_personas_destinatarios_subcomisiones_by_docente', ['docente' => kernel::persona()->get_id_docente()]);
						} else {// Si existen subcomisiones y se elige una, se le envía a los alumnos de la misma
							$personasDestinatariosComision = catalogo::consultar('mensajes', 'get_personas_destinatarios_subcomision', ['subcomision' => $subcomision]);
						}
						
						$personasDestinatarios = array_column($personasDestinatariosComision, 'persona');
						break;
					case 'M':
						$alcance = \mensajes::alumnos_mesa_examen;
						$mesa = $this->validate_param('mesa', 'post', validador::TIPO_ALPHANUM);
						$anio_academico = $this->validate_param('anio_academico', 'post', validador::TIPO_ALPHANUM);
						$destinatarios = $this->modelo()->get_destinatarios($anio_academico);
						$destino = $destinatarios['mesas'][$mesa]['llamado_mesa'];

						$personasDestinatariosMesa = catalogo::consultar('mensajes', 'get_personas_destinatarios_mesa', array('llamado_mesa' => $destino));
						$personasDestinatarios = array_column($personasDestinatariosMesa, 'persona');
						$dominios[] = ['llamado_mesa' => $destino];
						break;
					case 'A':
						$alcance = \mensajes::grupo_alumnos;
						$destino = $this->validate_param('alumno', 'post', validador::TIPO_ALPHANUM);

						$personasDestinatarios = array($destino);
						break;
					default:
						throw new error_guarani('Tipo de anuncio desconocido: ' . $tipo_anuncio . '.');
				}

				$archivos = array();
				if (isset($_FILES['archivos'])) {
					foreach ($_FILES['archivos'] as $key => $file) {
						foreach ($file as $id => $data) {
							if ($_FILES['archivos']['error'][$id] != UPLOAD_ERR_NO_FILE) {
								switch ($key) {
									case 'error':
										if ($data == 1) {
											$max = ini_get('upload_max_filesize');
											if (is_numeric($max))
												$max = ($max / 1048576) . 'MB';
											throw new error_guarani("Se ha superado el límite máximo de tamaño de archivo ($max).");
										}
										elseif ($data != 0)
											throw new error_guarani('Ha ocurrido un error inesperado subiendo el\los archivo(s). Por favor, intente de nuevo.');
										break;
									case 'name':
										$info = pathinfo($data);
										$archivos[$id]['nombre'] = $info['filename'];
										if (empty($info['extension']))
											throw new error_guarani(kernel::traductor()->trans('upload_err_extension_vacia'));
										$archivos[$id]['extension'] = $info['extension'];
										break;
									case 'size':
										$archivos[$id]['tamanio'] = (int) ($data / 1024);
										break;
									case 'tmp_name':
										$archivos[$id]['tmp_name'] = $data;
										break;
									default:
										$archivos[$id]['path'] = $this->get_path_attachment();
										break;
								}
							}
						}
					}
				}

				//guardo los archivos en el disco
				$archivos_nuevos = array();
				if (!empty($archivos)) {

					foreach ($archivos as $archivo) {
						$path_carpeta = $this->get_path_attachment() . '/adjuntos_mensajes/' . uniqid();
						if (!file_exists($path_carpeta)) {
							//Se crea una carpeta única para el archivo a subir
							mkdir($path_carpeta, 0777, true);
						}
						$archivo['path'] = $path_carpeta . '/' . $archivo['nombre'] . '.' . $archivo['extension'];
						// Se mueven los archivos subidos al servidor del directorio temporal PHP al recientemente creado
						move_uploaded_file($archivo['tmp_name'], $archivo['path']);
						$archivos_nuevos[] = $archivo;
					}
				}

				$interfaz_autogestion = \comunes::interfaz_autogestion;
				
				$nombre_docente = kernel::persona()->get_nombre();
				$remitente = kernel::traductor()->trans('mensajes_remitente', ['%remitente%' => $nombre_docente]);
				
				// Se envia mensaje usando el módulo notificador
				$parametros = [
					'titulo' => $titulo,
					'cuerpo' => str_replace(array("\r\n", "\r", "\n", "\\r", "\\n", "\\r\\n"), '<br>', $mensaje),
					'alcance' => $alcance,
					'tipoMensaje' => \notificador::tipo_mensaje_docentes,
					'fechaDesde' => $f_desde->format('Y-m-d'),
					'fechaHasta' => $f_hasta->format('Y-m-d'),
					'personas' => $personasDestinatarios,
					'enviarEmail' => true,
					'interfaz' => $interfaz_autogestion,
					'adjuntos' => $archivos_nuevos,
					'dominios' => $dominios,
					'remitente' => $remitente
				];
				\guarani::notificador()->enviar(\notificador::enviar_mensaje, $parametros);

				$this->add_var('mensaje_guardado', true);

				// Al finalizar de enviar el mesaje se lo redirijia al inicio de la operación ('index'), ahora a enviados
				$operacion = kernel::ruteador()->get_id_operacion();
				kernel::sesion()->set('mensaje_enviado_ok', true);
				$this->nuevo_request($operacion, 'enviados');
			} catch (\Exception $e) {

				// Si es una redirección la relanzo
				if ($e instanceof NuevoRequest) {
					throw $e;
				}

				kernel::log()->add_debug('ERROR', 'Se produjo la siguiente excepción en la transacción mensajes:evt__guardar_mensaje().' . PHP_EOL
						. 'MENSAJE: ' . $e->getMessage() . PHP_EOL
						. 'TRACE: ' . $e->getTraceAsString());
				$this->vista()->pagelet('envio_mensajes')->set_resultado($e->getMessage());
			}
		} else {
			$comision = $this->validate_param('comision', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
			if (!empty($comision))
				$this->add_var('comision', $comision);
		}
	}

	protected function get_path_attachment()
	{
		return kernel::proyecto()->get_dir_attachment();
	}

	function accion__descargar()
	{
		try {
			$adjunto = $this->modelo()->get_datos_adjunto(
					$this->validate_param('m', 'get', validador::TIPO_ALPHANUM), $this->validate_param('a', 'get', validador::TIPO_ALPHANUM)
			);
			$this->descargar_archivo($adjunto['path_archivo']);
		} catch (\siu\errores\error_guarani $e) {
			kernel::log()->add_debug('ERROR', 'Se produjo la siguiente excepción al descargar adjunto.' . PHP_EOL
					. 'MENSAJE: ' . $e->getMessage() . PHP_EOL
					. 'TRACE: ' . $e->getTraceAsString());
			$this->add_var('error', $e->get_mensaje());
			$this->vista()->pagelet('lista_mensajes')->set_estado_info($this->validate_param('e', 'get', validador::TIPO_ALPHANUM));
		}
	}

	/**
	 * @param string $fecha d/m/Y
	 */
	protected function strtotime($fecha)
	{
		$date = date_create_from_format('d/m/Y', $fecha);
		return strtotime(date_format($date, 'Y-m-d'));
	}

	public function accion__buscar_alumnos()
	{
		// url_buscar_alumnos para envio de mensajes por docente
		$json = array();
		$alumnos = catalogo::consultar('alumno', 'get_alumnos_activos_comisiones_mesas_un_docente',
                    [
                    'term' => $this->getAutocompleteTerm(),
                    'docente' => kernel::persona()->get_id_docente()
                    ]
                );
		foreach ($alumnos as $alumno) {
			$json[] = array(
				'id' => $alumno['persona'],
                                'label' => "{$alumno['apellido_nombres_elegido']} ({$alumno['tipo_nro_documento']})"
			);
		}
		$this->render_raw_json($json);
	}

	public function accion__combos()
	{
		$anioAcademico = $_GET['anioAcademico'];
		if (isset($anioAcademico)) {
			$destinatarios = $this->modelo()->get_destinatarios($anioAcademico);
			$opciones = array();
			$opciones['comisiones'] = '<option value="">SELECCIONE COMISIÓN</option>';
			$opciones['mesas'] = '<option value="">SELECCIONE MESA</option>';
			$opciones['divcomisiones'] = '';
			$opciones['divmesas'] = '';
			if (isset($destinatarios['comisiones'])) {
				foreach ($destinatarios['comisiones'] as $destinatario) {
					$opciones['comisiones'] .= '<option value="' . $destinatario['comision_id'] . '">' . $destinatario['actividad_nombre'] . " (" . $destinatario['comision_nombre'] . ")";

					$opciones['divcomisiones'] .= '<li id="O' . $destinatario['comision_id'] . '"> '
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('materia')) . ': </strong> ' . $destinatario['actividad_nombre'] . '</p>'
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('comision')) . ': </strong> ' . $destinatario['comision_nombre'] . '</p>'
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('sede')) . ':</strong> ' . $destinatario['ubicacion_nombre'] . '</p>'
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('año académico')) . ': </strong> ' . $destinatario['anio_academico'] . '</p>'
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('periodo_lectivo')) . ': </strong> ' . $destinatario['periodo_lectivo_nombre'] . '</p>'
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('catedra')) . ': </strong> ' . $destinatario['catedra_nombre'] . '</p>'
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('turno')) . ': </strong> ' . $destinatario['turno_nombre'] . '</p>'
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('cantidad_de_inscriptos')) . ': </strong> ' . $destinatario['cant_inscriptos_comision'] . '</p> </li>';
				}
			}
			//<option value="{{key}}">{{mesas.actividad_nombre}} ({{mesas.mesa_examen_nombre}})- {{mesas.fecha}} </option>
			if (isset($destinatarios['mesas'])) {
				foreach ($destinatarios['mesas'] as $key => $destinatario) {
					$opciones['mesas'] .= '<option value="' . $key . '">' . $destinatario['actividad_nombre'] . ' ( ' . $destinatario['mesa_examen_nombre'] . " ) - "
							. $destinatario['fecha_display'] . "";
					$opciones['divmesas'] .= '<li id="M' . $key . '"> '
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('materia')) . ': </strong> ' . $destinatario['actividad_nombre'] . '</p>'
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('mesa_examen')) . ': </strong> ' . $destinatario['mesa_examen_nombre'] . '</p>'
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('fecha')) . ': </strong> ' . $destinatario['fecha_display'] . '</p>'
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('año académico')) . ': </strong> ' . $destinatario['anio_academico'] . '</p>'
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('turno_examen')) . ': </strong> ' . $destinatario['turno_examen_nombre'] . '</p>'
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('llamado')) . ': </strong> ' . $destinatario['llamado_nombre'] . '</p>'
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('sede')) . ': </strong> ' . $destinatario['ubicacion_nombre'] . '</p>'
							. '<p><strong>' . ucfirst(kernel::traductor()->trans('Cantidad de alumnos')) . ': </strong> ' . $destinatario['cant_inscriptos'] . '</p>';
				}
			}

			$this->render_raw_json($opciones);
		}
	}
	
	
	public function accion__subcomisiones()
	{
		
		$subcomisiones = [];
		$todas = kernel::traductor()->trans('todas');
		$response = [
			'exito' => true,
			'tiene_subcomisiones' => false,
			'subcomisiones' => '<option value="'.mensajes::TODA_COMISION.'">'.$todas.'</option>'
		];
		
		if (kernel::request()->isPost()) {
			
			try {
				
				$anio_academico = $this->validate_param('anio_academico', 'post', validador::TIPO_INT);
				$comision_hash = $this->validate_param('comision_hash', 'post', validador::TIPO_ALPHANUM);
				
				$comision = null;
				
				$destinatarios = $this->modelo()->get_destinatarios($anio_academico);
				foreach ($destinatarios['comisiones'] as $dato) {
					if ($dato['comision_id'] == $comision_hash) {
						$comision = $dato['comision'];
						break;
					}
				}
				
				if (!is_null($comision)) {
					$subcomisiones = $this->modelo()->get_subcomisiones($comision);
					
					if (!empty($subcomisiones)) {
						$response['tiene_subcomisiones'] = true;
						$options = '<option value="'. mensajes::TODAS_SUBCOMISIONES.'">'.$todas.'</option>';
						foreach ($subcomisiones as $subcomision) {
							$options .= '<option value="'.$subcomision['subcomision'].'">'.$subcomision['subcomision_nombre'].' ('.$subcomision['tipo_clase_nombre'].')</option>';
						}
						$response['subcomisiones'] = $options;
					}
					
				}
				
			} catch (\Exception $e) {
				
				$response['exito'] = false;
				
			}
		}
		
		$this->render_json($response);
		
	}

}