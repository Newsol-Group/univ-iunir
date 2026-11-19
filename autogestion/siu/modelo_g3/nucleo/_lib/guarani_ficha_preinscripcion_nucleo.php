<?php
class guarani_ficha_preinscripcion_nucleo
{
	// Todas las medidas son expresadas en centómetros
	const ALTO_HOJA = 29.6;

	/** @var preinscripcion_PDF */
	protected $pdf;
	protected $margen_arriba = 3.2;
	protected $margen_abajo = 1.7;
	protected $margen_izq = 2.2;
	protected $margen_der = 2.2;
	protected $dim_cuadro_foto = 3.5; // Dimensión del cuadrado para foto
	protected $alto_credencial = 6.5;
	protected $cant_label = 12;
	protected $cant_dato = 10;
	protected $pos_y;
	protected $delta_y;
	protected $cant_comprobantes;
	protected $modo_comprobante;
	protected $util;
	protected $id;
	protected $fuente;
	protected $origen;
	protected $datos;

	function __construct($id, $fuente, $origen)
	{
		$this->id = $id;
		$this->fuente = $fuente;
		$this->origen = $origen;
		$this->pdf = new preinscripcion_PDF('A4');
		$this->util = new util_formulario_preinscripcion($this->id, $this->fuente, $this->origen);

		//Si el origen es preinscripcion
		if ($origen == preinscripcion::origen_preinscripcion) {
			$where = "id_preinscripcion = " . $this->id;
			$datos = current(toba::consulta_php('co_preinscripciones')->get_listado($where, $this->fuente));
			//Formatea los campos como fecha y los que tiene que buscar en tablas.
			$datos = $this->get_datos_preinscripcion_formateados($datos);
		} else {
			//Si el origen es 3w.
			$datos = $this->get_datos_3w();
		}
		$this->datos = $datos;

		//$this->modo_comprobante =  preinscripcion::modo_comp_PROPUESTA;

		$this->modo_comprobante = guarani_configuracion::instancia()->get_valor_config('pre_modo_impresion_comprobante');
		if ($this->modo_comprobante == preinscripcion::modo_comp_PROPUESTA) {
			$propuestas = $this->obtener_propuestas();
			$this->cant_comprobantes = count($propuestas);
		} elseif ($this->modo_comprobante == preinscripcion::modo_comp_RA) {
			$propuestas = $this->obtener_propuestas_por_ra();
			$this->cant_comprobantes = count($propuestas);
		} else {
			$propuestas = $this->obtener_propuestas();
			$this->cant_comprobantes = 1;
		}

		$this->inicializar_pagina();
		for ($i = 0; $i < $this->cant_comprobantes; $i++) {
			if ($i > 0) {
				// Se genera una nueva página
				$this->pdf->ezNewPage();
			}

			$this->encabezado();

			if ($this->modo_comprobante == preinscripcion::modo_comp_PROPUESTA) {
				// Un comprobante por propuesta
				$datos = array();
				$datos[] = $propuestas[$i];
				$this->propuestas($datos);
			} elseif ($this->modo_comprobante == preinscripcion::modo_comp_RA) {
				// Un comprobante por RA
				$this->propuestas($propuestas[$i]);
			} else {
				// Un comprobante por aspirante
				$this->propuestas($propuestas);
			}

			if (guarani_configuracion::instancia()->get_valor_config('pre_imprime_CBC')) {
				$this->CBC();
			}
			$this->datos_seccion('datos_adicionales');
			$this->datos_seccion('datos_principales');
			$this->datos_seccion('datos_personales');
			$this->datos_seccion('financiamiento');
			$this->datos_seccion('datos_laborales');
			$this->datos_seccion('datos_familiares');
			$this->datos_seccion('datos_estudios');
			$this->datos_seccion('tecnologia');
			$this->datos_seccion('deportes');
			$this->datos_seccion('idiomas');
			$this->datos_seccion('datos_salud');
			$this->datos_seccion('discapacidad');

			$this->avisos();
			$this->fecha_y_firma();

			if (guarani_configuracion::instancia()->get_valor_config('pre_imprime_credencial_provisoria')) {
				$this->credencial_provisoria();
			}
		}
	}

	/**
	 * Genera la salida PDF del comprobante.
	 */
	function get_comprobante()
	{
		ob_clean(); //Limpia el buffer en la salida del php
		$this->pdf->ezStream();
	}

	//--------------------------------------------------------------------------

	protected function obtener_propuestas()
	{
		$salida = array();
		$datos = [];
		if ($this->origen == preinscripcion::origen_preinscripcion) {
			$datos = toba::consulta_php('co_preinscripciones')->get_propuestas_insc_preinscripcion($this->id, $this->fuente);
		} else {
			$datos = toba::consulta_php('co_preinscripciones')->get_propuestas_insc_autogestion($this->id);
		}
		foreach ($datos as $dato) {
			$salida[] = $dato;
		}
		return $salida;
	}

	protected function obtener_propuestas_por_ra()
	{
		// Se agrupan las propuestas por Responsable Acadámica
		$aux = array();
		$datos = [];
		if ($this->origen == preinscripcion::origen_preinscripcion) {
			$datos = toba::consulta_php('co_preinscripciones')->get_propuestas_insc_preinscripcion($this->id, $this->fuente);
		} else {
			$datos = toba::consulta_php('co_preinscripciones')->get_propuestas_insc_autogestion($this->id);
		}
		foreach ($datos as $dato) {
			$ra = $dato['responsable_academica'];
			if (empty($aux[$ra])) {
				$aux[$ra] = array();
			}
			$aux[$ra][] = $dato;
		}

		// Se aplana la estructura para normalizarla con la forma esperada para recorrerla
		$salida = array();
		foreach ($aux as $grupo) {
			$salida[] = $grupo;
		}

		return $salida;
	}

	protected function inicializar_pagina()
	{
		$this->pdf->ezSetCmMargins($this->margen_arriba, $this->margen_abajo, $this->margen_izq, $this->margen_der);
		$this->inicializar_fuentes();
		$this->header();
		if ($this->cant_comprobantes == 1) {
			
		}
		$this->footer();
	}

	protected function header()
	{
		$header = $this->pdf->openObject();

		// Logo
		//Verificar de imprimir logo            
		if (comunes::es_interfaz_gestion()) {
			$archivo_logo = 'img/logo2.png';
			$path_img = toba::proyecto()->get_www()['path'];
			$desc_archivo = $path_img . $archivo_logo;
			$path = $desc_archivo;
		} else {
			$path = \SIU\Chulupi\kernel::localizador()->encontrar_path_archivo('www/img', 'logo2.png');
		}
		$this->pdf->addPngFromFile($path, $this->puntos_cm(2), $this->puntos_cm(27.2), 99);
		$margen = 6;

		// Texto de encabezado
		$inst = institucion::get_datos_gestionante();
		$nombre_institucion = $inst['nombre'];
		$nombre_institucion = $this->bold($this->utf8_encode($nombre_institucion));
		$this->pdf->addText($this->puntos_cm($margen), $this->puntos_cm(28.4), 13, $nombre_institucion);

		$texto_aspirante = $this->bold(preinscripcion::rep_ficha_aspirante);
		$this->pdf->addText($this->puntos_cm($margen), $this->puntos_cm(27.7), 12, $texto_aspirante);

		$apellido = $this->datos['apellido'];
		$nombres = $this->datos['nombres'];

		if (strlen($apellido . ', ' . $nombres) < 30) {
			$this->pdf->addText($this->puntos_cm(4.1 + $margen), $this->puntos_cm(27.7), 12, $this->utf8_encode($apellido) . ', ' . $this->utf8_encode($nombres));
		} else {
			$this->pdf->addText($this->puntos_cm(4.1 + $margen), $this->puntos_cm(27.7), 10, $this->utf8_encode($apellido) . ',');
			$this->pdf->addText($this->puntos_cm(4.1 + $margen), $this->puntos_cm(27.2), 10, $this->utf8_encode($nombres));
		}

		// Línea separadora
		$this->linea($this->puntos_cm(27));

		$this->pdf->closeObject();
		$this->pdf->addObject($header, "all");
	}

	protected function footer()
	{
		$footer = $this->pdf->openObject();

		// Nro. de página
		$pattern = $this->utf8_encode("Página {PAGENUM} de {TOTALPAGENUM}");
		$this->pdf->ezStartPageNumbers(500, 20, 8, 'rigth', $pattern, 1);

		$this->pdf->closeObject();
		$this->pdf->addObject($footer, "all");
	}

	//--------------------------------------------------------------------------
	/**
	 * Datos que se imprimen:
	 *  - Código de preinscripción
	 *  - Fecha última modificiación
	 *  - Apellido y nombre
	 *  - Espacio para completar nro. LU
	 *  - Código QR de la preinscripción
	 *  - Espacio para foto
	 */
	protected function encabezado()
	{
		// Datos básicos de la ficha
                $codigo_preinsc = '';
                // Si se imprime desde Preinscripción
                if ($this->origen == preinscripcion::origen_preinscripcion) {
                    $codigo_preinsc = $this->datos['id_preinscripcion'] . '/' . $this->datos['version_impresa'];
                }
		$texto = '';
		$this->pos_y = $this->puntos_cm(26.5);
		if ($this->origen == preinscripcion::origen_preinscripcion) {
			$texto = preinscripcion::rep_ficha_aspirante . $codigo_preinsc;
			$texto = $this->bold($texto);
		}
		$this->pdf->ezSetY($this->pos_y);
		$new_y = $this->pdf->ezText($texto, 11);
		// Se calcula el Delta Y para futuros posicionamientos
		$this->delta_y = $new_y - $this->pos_y;
		$this->pos_y = $new_y;

		$texto = preinscripcion::rep_fecha_ult_modif . $this->datos['fecha_ult_modif'];
		$texto = $this->bold($texto);
		$this->pdf->ezSetY($this->pos_y);
		$this->pos_y = $this->pdf->ezText($texto, 11);

		//$apellido = kernel::persona()->datos()->get_campo_comprobante('apellido');
		$apellido = $this->datos['apellido'];
		if (strlen($apellido) < 20) {
			$texto = $this->bold('Apellido: ' . $apellido);
			$this->pdf->ezSetY($this->pos_y + $this->delta_y);
			$this->pos_y = $this->pdf->ezText($texto, 11);
		} else {
			// Apellido largo: en dos líneas
			$texto = 'Apellido: ';
			$texto = $this->bold($texto);
			$this->pdf->ezSetY($this->pos_y + $this->delta_y);
			$this->pos_y = $this->pdf->ezText($texto, 11);

			$texto = $apellido;
			$this->pos_y = $this->pdf->ezText($texto, 10);
		}

		$nombres = $this->datos['nombres'];
		if (strlen($nombres) < 20) {
			$texto = $this->bold('Nombres: ' . $nombres);
			$this->pdf->ezSetY($this->pos_y + $this->delta_y);
			$this->pos_y = $this->pdf->ezText($texto, 11);
		} else {
			// Nombre largo: en dos líneas
			$texto = 'Nombres: ';
			$texto = $this->bold($texto);
			$this->pdf->ezSetY($this->pos_y + $this->delta_y);
			$this->pos_y = $this->pdf->ezText($texto, 11);

			$texto = $nombres;
			$this->pos_y = $this->pdf->ezText($texto, 10);
		}

		$texto = preinscripcion::rep_nro_lu . '..........................';
		$texto = $this->bold($texto);
		$this->pdf->ezSetY($this->pos_y + $this->delta_y);
		$this->pos_y = $this->pdf->ezText($texto, 11);

		$texto = preinscripcion::rep_nro_lu_leyenda;
		$this->pos_y = $this->pdf->ezText($texto, 7);

		//Se imprime el QR solo si viene de preinscripcion
		if ($this->origen == preinscripcion::origen_preinscripcion) {
			// Código QR
			$outfile_QR = toba::proyecto()->get_path_temp() . '/qr' . $this->id . '.png';
			QRcode::png($codigo_preinsc, $outfile_QR, 'QR_ECLEVEL_H', 5, 0);
			$this->pdf->addPngFromFile($outfile_QR, $this->puntos_cm(11.6), $this->puntos_cm(23), 100);
			unlink($outfile_QR);
		}

		// Espacio para la foto
		$this->pdf->rectangle($this->puntos_cm(15.5), $this->puntos_cm(23), $this->puntos_cm($this->dim_cuadro_foto), $this->puntos_cm($this->dim_cuadro_foto));

		// Línea separadora
		$this->pos_y += 1.5 * $this->delta_y;
		$this->linea($this->pos_y);

		$this->pos_y += 0.5 * $this->delta_y;
	}

	protected function turno_presentacion($propuestas)
	{
		$texto = $this->italic($this->bold(preinscripcion::rep_turno_mensaje));
		$this->pdf->ezSetY($this->pos_y + $this->delta_y);
		$this->pos_y = $this->pdf->ezText($texto, 11);
		
		// Se obtienen los turnos
		$turnos = $this->get_turnos_presentacion($propuestas);
                
		$cols = array(
			'responsable_academica_nombre' => $this->une_string($this->bold(preinscripcion::turno_ra), $this->cant_label),
			'fecha' => $this->bold(preinscripcion::turno_fecha),
			'hora' => $this->bold(preinscripcion::turno_hora),
			'operador' => $this->bold(preinscripcion::turno_operador),
		);

		$opciones = array(
			'showLines' => '1',
			'showHeadings' => '1',
			'shaded' => '0',
			'width' => $this->puntos_cm(16.2),
			//'xOrientation' => 'center',
			'fontSize' => 13,
			'cols' => array(
				'responsable_academica_nombre' => array('justification' => 'left'),
				'fecha' => array('justification' => 'left'),
				'hora' => array('justification' => 'left'),
				'operador' => array('justification' => 'left'),
			),
		);

		$this->pos_y += $this->delta_y;
		$this->pdf->ezSetY($this->pos_y);
		$this->pdf->setLineStyle(1);
		$this->pos_y = $this->pdf->ezTable($turnos, $cols, '', $opciones);

		// Línea separadora
		$this->pos_y += 1.5 * $this->delta_y;
		$this->linea($this->pos_y);

		$this->pos_y += 0.5 * $this->delta_y;
            }

	/**
	 * Obtiene el listado de turnos correspondientes a las Responsables Acadámicas
	 * de las Propuestas recibidas
	 * 
	 * @param array $propuestas
	 * @return array
	 */
	protected function get_turnos_presentacion($propuestas)
	{

		if ($this->origen == preinscripcion::origen_preinscripcion) {
			$ras = toba::consulta_php('co_preinscripciones')->get_responsables_academicas_inc_preinscripcion($this->id, $this->fuente);
			$campo = 'id_preinscripcion';
		} else {
			$ras = toba::consulta_php('co_preinscripciones')->get_responsables_academicas_inc_autogestion($this->id);
			$campo = 'persona';
		}

		$salida = array();
		foreach ($ras as $ra) {
			$datos_ra = toba::consulta_php('co_responsables_academicas')->get_datos_resp_academica($ra['responsable_academica']);
			if (!empty($datos_ra)) {
				$turno = toba::consulta_php('co_turnos_preinscripcion')->get_turno($campo, $this->id, $ra['responsable_academica']);
				if (!empty($turno['fecha'])) {
					$pre_turno = $turno['pre_turno'];
					$turno_fecha = $turno['fecha'];
					$turno_hora = $turno['hora'];
					$turno_operador = $turno['operador'];
					$asignado = true;
				} else {
					$pre_turno = null;
					$turno_fecha = '-- No asignado --';
					$turno_hora = '-- No asignado --';
					$turno_operador = '-- No asignado --';
					$asignado = false;
				}
				$turnos[] = array(
					'pre_turno' => $pre_turno,
					'responsable_academica_plana' => $ra['responsable_academica'],
					'responsable_academica' => $ra['responsable_academica'],
					'responsable_academica_nombre' => $this->utf8_encode($datos_ra['nombre']),
					'fecha' => $turno_fecha,
					'hora' => $turno_hora,
					'operador' => $turno_operador,
					'asignado' => $asignado,
				);
			}
		}
		$ras = array_unique(aplanar_matriz($propuestas, 'responsable_academica'));
		$salida = array();
		foreach ($turnos as $turno) {
			if (in_array($turno['responsable_academica_plana'], $ras)) {
				$salida[] = $turno;
			}
		}
		return $salida;
	}

	protected function propuestas($datos)
	{
		// Si carga turno de presentación, se muestra en la ficha antes de las propuestas
		if ($this->origen == preinscripcion::origen_preinscripcion && guarani_configuracion::instancia()->get_valor_config('pre_turno_presentacion')) {
			$this->turno_presentacion($datos);
		}
                if ($this->origen == preinscripcion::origen_autogestion) {
                    $this->turno_presentacion($datos);
                    
                }
		// Título
		$texto = preinscripcion::rep_propuestas_a_insc;
		$texto = $this->bold($texto);
		$this->pdf->ezSetY($this->pos_y);
		$this->pos_y = $this->pdf->ezText($texto, 12);

		// Tabla de propuestas
		foreach ($datos as $indice => $inscripcion) {
			foreach ($inscripcion as $key => $value) {
				$datos[$indice][$key] = $this->utf8_encode($this->une_string($value, 12));
			}
		}

		$cols = array(
			'ra_nombre' => $this->une_string($this->bold('Responsable Acádemica'), 12),
			'propuesta_nombre' => $this->une_string($this->bold('Propuesta'), 12),
			'ubicacion_nombre' => $this->une_string($this->bold('Ubicación'), 12),
			'modalidad_nombre' => $this->une_string($this->bold('Modalidad'), 12),
		);

		if (guarani_configuracion::instancia()->get_valor_config('pre_imprime_periodo_insc')) {
			$cols['periodo_insc'] = $this->bold('Per. Insc.');
		}

		$opciones = array(
			'showHeadings' => '1',
			'shaded' => '0',
			'width' => $this->puntos_cm(16.2),
			//'xOrientation' => 'center',
			'fontSize' => 13,
			'cols' => array(
				'ra_nombre' => array('justification' => 'left'),
				'propuesta_nombre' => array('justification' => 'left'),
				'ubicacion_nombre' => array('justification' => 'left'),
				'modalidad_nombre' => array('justification' => 'left'),
			),
		);

		$this->pos_y += $this->delta_y;
		$this->pdf->ezSetY($this->pos_y);
		$this->pdf->setLineStyle(1);
		$this->pos_y = $this->pdf->ezTable($datos, $cols, '', $opciones);

		// Se resetean los colores y se actualiza el puntero de altura
		$this->pdf->setColor(0, 0, 0);
		$this->pdf->setStrokeColor(0, 0, 0);

		$this->pos_y += $this->delta_y;
	}

	protected function CBC()
	{
		// Línea separadora
		$this->linea($this->pos_y);

		// Título
		$this->pos_y += 0.5 * $this->delta_y;
		$texto = preinscripcion::rep_titulo_cbc;
		$texto = $this->bold($texto);
		$this->pdf->ezSetY($this->pos_y);
		$this->pos_y = $this->pdf->ezText($texto, 12);

		$cols = array(
			'cbc_codigo' => $this->bold(preinscripcion::cbc_codigo),
			'cbc_asignaturas' => $this->bold(preinscripcion::cbc_asignaturas),
			'cbc_calificacion' => $this->bold(preinscripcion::cbc_calificacion),
			'cbc_fecha' => $this->bold(preinscripcion::cbc_fecha),
		);

		$opciones = array(
			'showLines' => '2',
			'showHeadings' => '1',
			'shaded' => '0',
			'width' => $this->puntos_cm(16.2),
			'xOrientation' => 'center',
			'rowGap' => '4',
			'cols' => array(
				'cbc_codigo' => array('width' => $this->puntos_cm(2)),
				'cbc_asignaturas' => array('width' => $this->puntos_cm(8)),
			),
		);

		$datos = array();
		for ($i = 0; $i < 7; $i++) {
			$datos[$i] = array(
				'cbc_codigo' => ' ',
				'cbc_asignaturas' => ' ',
				'cbc_calificacion' => ' ',
				'cbc_fecha' => ' ',
			);
		}

		$this->pdf->ezSetY($this->pos_y + ($this->delta_y));
		$this->pos_y = $this->pdf->ezTable($datos, $cols, '', $opciones);

		// Se resetean los colores y se actualiza el puntero de altura
		$this->pdf->setColor(0, 0, 0);
		$this->pdf->setStrokeColor(0, 0, 0);
		$this->pos_y += $this->delta_y * 0.5;

		$texto = preinscripcion::cbc_leyenda_cuadro;
		$this->pdf->ezSetY($this->pos_y);
		$this->pos_y = $this->pdf->ezText($texto, 9);

		$this->pos_y += $this->delta_y;
	}

	//--------------------------------------------------------------------------

	protected function datos_seccion($seccion)
	{
		if ($this->util->hay_imprimibles_seccion($seccion)) {
			// Línea separadora
			$this->linea($this->pos_y);

			// Título
			$this->pos_y += 0.5 * $this->delta_y;
			$texto = preinscripcion::get_constante('seccion_' . $seccion);
			$texto = $this->bold($texto);
			$this->pdf->ezSetY($this->pos_y);
			$this->pos_y = $this->pdf->ezText($texto, 12);

			$cols = array(
				'label_1' => '',
				'dato_1' => '',
				'label_2' => '',
				'dato_2' => '',
			);

			$opciones = array(
				'showLines' => '0',
				'showHeadings' => '0',
				'shaded' => '0',
				'maxWidth' => $this->puntos_cm(16.2),
				'xOrientation' => 'center',
				'rowGap' => '6',
				'colGap' => '6',
				'fontSize' => '8',
				'cols' => array(
					'label_1' => array('justification' => 'right', 'width' => $this->puntos_cm(3.5)),
					'dato_1' => array('justification' => 'left', 'width' => $this->puntos_cm(4.5)),
					'label_2' => array('justification' => 'right', 'width' => $this->puntos_cm(3.5)),
					'dato_2' => array('justification' => 'left', 'width' => $this->puntos_cm(4.5)),
				),
			);

			// Se obtienen los campos de la sección
			$campos = $this->util->get_campos_seccion($seccion);
			foreach ($campos as $nombre_agrupado => $campos_seccion) {
				if ($this->util->hay_imprimibles_agrupado($seccion, $nombre_agrupado)) {
					$datos = array();

					// Título
					$this->pdf->setColor(0.2, 0.2, 0.2);
					$this->pos_y += 0.5 * $this->delta_y;
					$texto = '    ' . preinscripcion::get_constante('agrupado_' . $nombre_agrupado);
					$texto = $this->italic($this->bold($texto));
					$this->pdf->ezSetY($this->pos_y);
					$this->pos_y = $this->pdf->ezText($texto, 10); //, array('justification' => 'center'));
					// Línea separadora
					$this->pdf->setLineStyle(1);
					$this->pdf->setColor(0.5, 0.5, 0.5);
					$this->pdf->setStrokeColor(0.5, 0.5, 0.5);
					$y = $this->pos_y + $this->delta_y * 0.5;
					$this->pdf->line($this->puntos_cm(2.5), $y, $this->puntos_cm(18.5), $y);

					// Datos
					$this->pdf->setColor(0, 0, 0);
					$columna_1 = true;

					foreach ($campos_seccion as $campo) {
						if (($this->util->visible($campo)) && ($this->util->se_imprime($campo))) {
							if ($columna_1) {
								$dato = array();
								$dato['label_1'] = $this->utf8_encode(($this->datos_tabla($this->etiqueta($campo), $this->cant_label)));
								$dato['dato_1'] = $this->utf8_encode(($this->datos_tabla($this->get_campo_comprobante($campo), $this->cant_dato)));
							} else {
								$dato['label_2'] = $this->utf8_encode(($this->datos_tabla($this->etiqueta($campo), $this->cant_label)));
								$dato['dato_2'] = $this->utf8_encode(($this->datos_tabla($this->get_campo_comprobante($campo), $this->cant_dato)));
								$datos[] = $dato;
							}
							$columna_1 = !$columna_1;
						}
					}
					if (!$columna_1) {
						$dato['label_2'] = ' ';
						$dato['dato_2'] = ' ';
						$datos[] = $dato;
					}
					//var_dump($var)
					$this->pdf->ezSetY($this->pos_y + ($this->delta_y));
					$this->pos_y = $this->pdf->ezTable($datos, $cols, '', $opciones);
					$this->pos_y += 0.5 * $this->delta_y;
				}
			}

			$this->pos_y += 0.5 * $this->delta_y;
		}
	}

	//--------------------------------------------------------------------------

	protected function une_string($string, $cant)
	{
		$arre = array();
		if (is_string($string)) {
			$arre = explode(" ", $string);
                } else {
			$arre = $string; //viene en forma array de llamada recursiva
                }

                
		$newString = "";
                // Si se trata de un array
                if (is_array($arre)) {
                    foreach ($arre as $key => $s) {
                            if (strlen($newString) >= $cant) {
                                    return $newString . "\n" . $this->une_string($arre, $cant);
                            } else {
                                    $newString = $newString . " " . $s;
                                    unset($arre[$key]);
                            }
                    }
                } else {// Si no es un array
                    $newString = $arre;
                }
		return $newString;
	}

	//La funcion consta de cortar el arregle en 12 y 10 caracteres, cuando es mayor llama a la funcion une_string
	//Esta lo que haces es ir diviendo en cantidades igual al string para mostrarlo correctamente y no cortarlo
	protected function datos_tabla($string, $cant)
	{
		if (strlen($string) > $cant)
			return $this->une_string($string, $cant);
		else
			return $string;
	}

	//--------------------------------------------------------------------------

	protected function avisos()
	{
		//$avisos = kernel::proyecto()->get_parametro('rep_avisos');
		$avisos[] = $this->utf8_encode('La presente tiene carácter de DECLARACIÓN JURADA, por lo que deberá ser completada personalmente por el firmante.');

		if (!empty($avisos)) {
			// Línea separadora
			$this->linea($this->pos_y);

			$opciones = array(
				'showLines' => '0',
				'showHeadings' => '0',
				'shaded' => '0',
				'width' => $this->puntos_cm(16.2),
				'xOrientation' => 'center',
				'fontSize' => 8,
				'cols' => array(
					'avisos_texto' => ''
				),
			);
			$cant = 1;

			foreach ($avisos as $aviso) {
				$datos[] = ['avisos_texto' => $cant . ') ' . trim($aviso)];
				$cant++;
			}

			$this->pdf->ezSetY($this->pos_y + (0.5 * $this->delta_y));
			$this->pos_y = $this->pdf->ezTable($datos, '', '', $opciones);
		}
	}

	protected function fecha_y_firma()
	{
		$cols = array(
			'localidad' => '<b>Localidad</b>',
			'fecha' => '<b>Fecha</b>',
			'firma' => '',
		);

		$opciones = array(
			'showLines' => '0',
			'showHeadings' => '0',
			'shaded' => '0',
			'width' => $this->puntos_cm(16.2),
			'xOrientation' => 'center',
			'fontSize' => 12,
			'cols' => array(
				'localidad' => array('justification' => 'left'),
				'fecha' => array('justification' => 'left'),
				'firma' => array('justification' => 'center', 'width' => $this->puntos_cm(7)),
			),
		);

		$fecha = date('j/n/Y');

		//$localidad = kernel::proyecto()->get_parametro('rep_localidad');
		$localidad = institucion::get_datos_gestionante();
		if ($localidad) {
			$localidad = $this->utf8_encode($this->datos_tabla($localidad['localidad_nombre'], $this->cant_dato));
			$fecha = $this->utf8_encode($this->datos_tabla($fecha, $this->cant_dato));
		} else {
			$localidad = "S/N";
			$fecha = $fecha;
		}

		$datos = array
			(array(
				'localidad' => '',
				'fecha' => '',
				'firma' => '',
			), array(
				'localidad' => $localidad,
				'fecha' => $fecha,
				'firma' => '..........................................................',
			), array(
                                'localidad' => '',
				'fecha' => '',
				'firma' => '                        ' . $this->bold(preinscripcion::rep_firma),
			)
		);

		$this->pos_y += 5 * $this->delta_y;
		$this->pdf->ezSetY($this->pos_y);
		$this->pos_y = $this->pdf->ezTable($datos, $cols, '', $opciones);
	}

	protected function credencial_provisoria()
	{
		$y = $this->puntos_cm($this->alto_credencial);

		if ($this->pos_y < $y) {
			// Si no hay lugar para la credencial, se inserta una nueva página
			$this->pdf->ezNewPage();
		}
		$this->pos_y = $y;

		// Se elimina nro. de página en la óltima, si se imprime credencial provisoria
		$this->pdf->ezStopPageNumbers(0, 0, 0);

		// Linea punteada
		$this->pdf->setLineStyle(1, 'round', '', array(2, 2));
		$this->pdf->line($this->puntos_cm(0.5), $this->pos_y, $this->puntos_cm(20.5), $this->pos_y);

		// Título
		$this->pos_y += 0.5 * $this->delta_y;
		$texto = preinscripcion::rep_titulo_credencial;
		$texto = $this->bold($texto);
		$this->pdf->ezSetY($this->pos_y);
		$this->pos_y = $this->pdf->ezText($texto, 12);

		// Espacio para la foto
		$this->pos_y += $this->delta_y;
		$this->pdf->setLineStyle(1, 'square', 'miter', array(100, 1));
		$this->pdf->setColor(0, 0, 0);
		$this->pdf->setStrokeColor(0, 0, 0);
		$this->pdf->rectangle($this->puntos_cm(2.2), $this->pos_y - $this->puntos_cm($this->dim_cuadro_foto), $this->puntos_cm(3.5), $this->puntos_cm(3.5));

		// Datos personales
		$cols = array(
			'datos' => '',
		);

		$opciones = array(
			'showLines' => '0',
			'showHeadings' => '0',
			'shaded' => '0',
			'width' => $this->puntos_cm(13),
			'xPos' => $this->puntos_cm(19.5),
			'xOrientation' => 'left',
			'fontSize' => 11,
			'cols' => array(
				'datos' => array('justification' => 'left'),
			),
		);

		$datos = array();
		$datos[] = array('datos' => $this->bold(preinscripcion::rep_nro_lu) . '..........................');
		$datos[] = array('datos' => $this->bold(preinscripcion::rep_apellido_y_nombre) . ': ' . $this->datos['apellido'] . ', ' . $this->datos['nombres']);
		//$identificacion = kernel::persona()->datos()->get_campo_comprobante('tipo_documento_desc_abreviada') . ' ' . 'Nro Documento';
		$identificacion = 'Nro Documento';
		$datos[] = array('datos' => $this->utf8_encode($this->bold(preinscripcion::rep_identificacion) . ': ' . $identificacion));

		//$mail = kernel::persona()->datos()->get_campo_comprobante('e_mail');
		$mail = 'Email';
		$dato = $this->bold("E-Mail") . ': ' . $mail;
		//$celular = kernel::persona()->datos()->get_campo_comprobante('celular_numero');
		$celular = 'Celular';
		if (!empty($celular) && ($celular != '0--15-')) {
			$dato .= '   -   ' . $this->bold(preinscripcion::rep_telefono) . ': ' . $celular;
		}
		$datos[] = array('datos' => $dato);

		//$propuestas = kernel::persona()->datos()->get_nombres_propuestas_insc();
		$propuestas = 'Propuestas';
		$datos[] = array('datos' => $this->bold(preinscripcion::rep_propuestas) . ': ' . $propuestas);

		$this->pdf->ezSetY($this->pos_y);
		$this->pos_y = $this->pdf->ezTable($datos, $cols, '', $opciones);
	}

	//--------------------------------------------------------------------------
	/**
	 *
	 * Función que pasa las medidas en centómetros a puntos (todas las medidas en PDF 
	 * se expresan en puntos, cada punto es aproximadamente la 1/72 parte de una pulgada).
	 * 
	 * @param $medida en centímetros
	 * @param $resolucion por defecto le asignaremos '72' (72 puntos por pulgada)
	 */
	protected function puntos_cm($medida, $resolucion = 72)
	{
		// 2.54 cm / pulgada
		return ($medida / (2.54)) * $resolucion;
	}

	protected function bold($texto)
	{
		return "<b>" . $texto . "</b>";
	}

	protected function italic($texto)
	{
		return '<i>' . $texto . '</i>';
	}

	protected function inicializar_fuentes()
	{
		$tmp = array(
			'b' => 'Times-Bold.afm',
			'i' => 'Times-Italic.afm',
			'bi' => 'Times-BoldItalic.afm',
			'ib' => 'Times-BoldItalic.afm',
			'bb' => 'Times-Roman.afm'
		);
		//Selecciono la fuente que tiene la librería.
		if (comunes::es_interfaz_gestion()) {
			$this->pdf->selectFont(toba::proyecto()->get_path() . '/vendor/rospdf/pdf-php/src/fonts/Times-Roman.afm');
		} else {
			$this->pdf->selectFont(\siu\bootstrap::get_dir_raiz() . '/vendor/rebuy/ezpdf/src/ezpdf/fonts/Times-Roman.afm');
		}
	}

	protected function etiqueta($campo)
	{
		$etiqueta = $this->util->get_etiqueta($campo);
		return $this->bold($etiqueta);
	}

	/**
	 * Inserta una línea separadora en una altura de la hoja dada
	 * 
	 * @param float	$y		Altura donde insertar la línea (en puntos)
	 * @param array $color	Arreglo R G B representando el color de la línea
	 */
	protected function linea($y, $color = array(0, 0, 0))
	{
		$this->pdf->setLineStyle(1);
		$this->pdf->setColor(0, 0, 0);
		$this->pdf->setStrokeColor(0, 0, 0);
		$this->pdf->line($this->puntos_cm(2), $y, $this->puntos_cm(19.2), $y);
	}

	protected function get_campo_comprobante($campo)
	{
		if (isset($this->datos[$campo]) && !is_null($this->datos[$campo])) {
			$salida = $this->datos[$campo];
			if ($this->origen == preinscripcion::origen_preinscripcion) {
				$campos_si_no = ['tipo_residencia', 'alu_cos_est_ap_fam', 'alu_cos_est_plsoc',
					'alu_cos_est_trab', 'alu_cos_est_beca', 'alu_cos_est_otra', 'tiene_beca_univ',
					'tiene_beca_inter', 'tiene_beca_nacio', 'alu_beca_prov', 'alu_beca_muni', 'alu_beca_otra',
					'alu_beca_tipo_eco', 'alu_beca_tipo_ser', 'alu_beca_tipo_inv', 'alu_beca_eco_tran',
					'alu_beca_eco_efec', 'alu_beca_eco_foto', 'alu_beca_eco_habi', 'alu_beca_eco_come',
					'alu_trab_desjub', 'alu_trab_fami', 'remuneracion', 'alu_est_civil_uh', 'alu_tec_pc_casa',
					'alu_tec_pc_trab', 'alu_tec_pc_univ', 'alu_tec_pc_otro', 'alu_tec_int_casa', 'alu_tec_int_trab',
					'alu_tec_int_univ', 'alu_tec_int_movil', 'alu_tec_int_otro', 'practica_deportes',
					'hace_dep_gim_priv', 'hace_dep_univ', 'hace_dep_partic', 'hace_dep_otros', 'prac_dep_futbol',
					'prac_dep_basquet', 'prac_dep_voley', 'prac_dep_gimnasia', 'prac_dep_tenis', 'prac_dep_natacion',
					'prac_dep_handball', 'prac_dep_otros', 'insc_otra_inst', 'mot_inst_econom', 'mot_inst_prestigio',
					'mot_inst_difusion', 'mot_inst_rec_estud', 'mot_inst_rec_amigos', 'mot_inst_sist_ing',
					'mot_inst_ubicacion', 'mot_inst_otros', 'mot_prop_vocacion', 'mot_prop_sug_fam', 'mot_prop_rec_social',
					'mot_prop_ins_lab', 'mot_prop_perf_prof', 'mot_prop_mejora_ec', 'mot_prop_util_soc', 'mot_prop_otros',
					'adeuda_materias', 'insc_otra_inst', 'es_celiaco', 'es_mayor_25_sin_secundario',
					'tiene_cud', 'disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra',
					'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos',
					'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos',
					'mot_req_apoyo_aula', 'mot_req_otros_apoyos',
					'psi_req_otros_apoyos',
					'otra_req_apoyo_vida_diaria'];
			} elseif ($this->origen == preinscripcion::origen_autogestion) {
				$campos_si_no = ['unido_hecho', 'es_celiaco', 'costeo_estudios_familiar', 'costeo_estudios_plan_social',
					'costeo_estudios_trabajo', 'costeo_estudios_beca', 'costeo_estudios_otro', 'costeo_estudios_descripcion', 'beca_fuente_universidad',
					'beca_fuente_nacional', 'beca_fuente_provincial', 'beca_fuente_municipal', 'beca_fuente_internacional',
					'beca_fuente_otra', 'beca_tipo_economica', 'beca_tipo_servicios', 'beca_tipo_investigacion', 'beca_economica_transporte',
					'beca_economica_efectivo', 'beca_economica_fotocopias', 'beca_economica_habitacional', 'beca_economica_comedor',
					'trabajo_familiar', 'trabajo_con_descuentos_jubilatorios', 'trabajo_recibe_pago', 'tecnologia_pc_casa',
					'tecnologia_pc_trabajo', 'tecnologia_pc_universidad', 'tecnologia_pc_otro_lugar', 'tecnologia_int_casa',
					'tecnologia_int_trabajo', 'tecnologia_int_universidad', 'tecnologia_int_otro_lugar', 'tecnologia_int_movil',
					'deportes', 'deportes_universidad', 'deportes_gimnasio', 'deportes_particular', 'deportes_otro_lugar', 'deportes_basquet',
					'deportes_futbol', 'deportes_gimnasia', 'deportes_handball', 'deportes_natacion', 'deportes_tenis', 'deportes_voley',
					'deportes_otros', 'mot_economico', 'mot_prestigio', 'mot_difusion', 'mot_recomendacion_estudiantes', 'mot_recomendacion_amigos',
					'mot_sistema_ingreso', 'mot_ubicacion', 'mot_otros_i', 'insc_otra_institucion', 'mot_vocacion', 'mot_sugerencia_familiar',
					'adeuda_materias', 'mot_reconocimiento_social', 'mot_insercion_laboral', 'mot_perfil_profesional', 'mot_mejora_economica', 'mot_util_sociedad', 'mot_otros_p',
					'tiene_cud', 'disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra',
					'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos',
					'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos',
					'mot_req_apoyo_aula', 'mot_req_otros_apoyos',
					'psi_req_otros_apoyos',
					'otra_req_apoyo_vida_diaria'];
			}


			if (in_array($campo, $campos_si_no)) {
				//---- Campos de selección Sí / No -----------------------------
				if ($salida == comunes::si) {
					$salida = comunes::si_desc;
				} elseif ($salida == comunes::no) {
					$salida = comunes::no_desc;
				}
			} else {
				//---- Control de datos obtenidos de selección múltiple --------
				switch ($campo) :
					//---- Carreras --------------------------------------------
					case 'turno_preferido' :
						if ($this->origen == preinscripcion::origen_autogestion) {
							$opciones = toba::consulta_php('co_comisiones')->get_listado_turnos_cursadas();
							$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
							$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						} else {
							$opciones = preinscripcion::get_turnos_preferidos();
							$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						}
						break;
					//---- Datos principales -----------------------------------
					case 'sexo' :
						$opciones = toba::consulta_php('co_personas')->get_sexos();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						switch ($salida) {
							case persona::sexo_femenino_araucano :
								$salida = persona::sexo_femenino_descr;
								break;
							case persona::sexo_masculino_araucano :
								$salida = persona::sexo_masculino_descr;
								break;
							case persona::sexo_no_binario_araucano :
								$salida = persona::sexo_no_binario_descr;
								break;

							default: $salida = '--';
								break;
						}

						break;
					case 'ent_emisora_ci' :
						if ($this->origen != preinscripcion::origen_autogestion) {
							$opciones = toba::consulta_php('co_personas')->get_entes_emisores_ci();
							$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
							$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						}
						break;
					case 'pais_emisor_pasap' :
						if ($this->origen != preinscripcion::origen_autogestion) {
							$opciones = toba::consulta_php('co_mug')->get_paises();
							$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
							$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						}
						break;
					case 'tipo_visa' :
						$opciones = toba::consulta_php('co_personas')->get_tipos_visas();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'residencia' :
						$opciones = toba::consulta_php('co_personas')->get_tipos_residencias();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'pertenece_pueblo_originario' :
						$opciones = toba::consulta_php('co_comunes')->get_si_no_no_desea_responder();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'pueblo_originario' :
						if ($this->origen == preinscripcion::origen_preinscripcion) {
							$opciones = toba::consulta_php('co_personas')->get_opciones_pueblos_originarios();
							$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
							$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
							if (($this->datos[$campo] == preinscripcion::pueblo_originario_OTRO) && (!empty($this->datos['pueblo_originario_otro']))) {
								$salida = $this->datos['pueblo_originario_otro'];
							}
						}

						break;
					//---- Datos personales -----------------------------------
					case 'obra_social_alu' :
						$opciones = toba::consulta_php('co_datos_censales')->get_coberturas_salud();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'tipo_res_per_lect' :
						if ($this->origen == preinscripcion::origen_autogestion) {
							$salida = (!empty($salida)) ? $salida : '--';
						} else {
							$opciones = toba::consulta_php('co_datos_censales')->get_tipos_viviendas();
							$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
							$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						}
						break;
					case 'vive_actual_con' :
						$opciones = toba::consulta_php('co_datos_censales')->get_vive_con();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'tipo_allegado' :
						$opciones = toba::consulta_php('co_personas')->get_tipos_allegados();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					//---- Datos laborales -------------------------------------
					case 'existe_trab_alum' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_existen();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_trab_hace' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_hacen();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_trab_ocup' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_ocupaciones();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'hora_sem_trab_alum' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_horas_semanales();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'rel_trab_propuesta' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_carreras();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					/*
					  case 'empresa' :
					  $opciones = util::opciones()->get_empresas();
					  $salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
					  break;

					  case 'sector' :
					  $opciones = util::opciones()->get_sectores();
					  $salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
					  break;

					  case 'subsector' :
					  $opciones = util::opciones()->get_subsectores();
					  $salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
					  break;

					  case 'facilidad_est_emp' :
					  $opciones = util::opciones()->get_facilidad_estudios();
					  $salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
					  break;
					  case 'facilidad_est_inst' :
					  $opciones = util::opciones()->get_facilidad_estudios();
					  $salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
					  break; */

					//---- Datos familiares ------------------------------------
					case 'estado_civil' :
						$opciones = toba::consulta_php('co_datos_censales')->get_estados_civiles();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'cant_hijos_alum' :
						$opciones = toba::consulta_php('co_datos_censales')->get_cantidades_hijos();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'cant_fami_cargo' :
						$opciones = toba::consulta_php('co_datos_censales')->get_cantidades_familia();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'padre_vive' :
						$opciones = toba::consulta_php('co_comunes')->get_vive_novive_desconoce();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'ult_est_cur_padre' :
						$opciones = toba::consulta_php('co_personas')->get_estudios_alcanzados();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'sit_laboral_padre' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_existen();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'padre_trab_hace' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_hacen();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'padre_trab_ocup' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_ocupaciones();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'padre_trab_otro' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_otros();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'madre_vive' :
						$opciones = toba::consulta_php('co_comunes')->get_vive_novive_desconoce();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'ult_est_cur_madre' :
						$opciones = toba::consulta_php('co_personas')->get_estudios_alcanzados();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'sit_laboral_madre' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_existen();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'madre_trab_hace' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_hacen();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'madre_trab_ocup' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_ocupaciones();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'madre_trab_otro' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_otros();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;

					//---- Datos estudios --------------------------------------
					case 'orientacion_recibida' :
						$opciones = toba::consulta_php('co_personas')->get_orientaciones_recibidas();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_otestsup_tipo' :
						$opciones = toba::consulta_php('co_comunes')->get_tipos_estudios_superiores();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_otestsup_esta' :
						$opciones = toba::consulta_php('co_comunes')->get_estado_finalizado_abandonado();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_otestsup_area' :
						$opciones = toba::consulta_php('co_comunes')->get_areas_estudios_sup();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_otestsup_niv' :
						$salida = ($salida == preinscripcion::estudio_nivel_universitario) ? persona::nivel_universitario_descr : persona::nivel_superior_descr;
						break;
					case 'alu_otestsup_razon_abandono' :
						$opciones = toba::consulta_php('co_personas')->get_razones_abandono_estudios();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						if (($this->datos[$campo] == persona::estudio_razon_abandono_otras) && (!empty($this->datos['alu_otestsup_razon_abandono_otros']))) {
							$salida = $this->datos['alu_otestsup_razon_abandono_otros'];
						}
						break;
					//---- Idiomas ---------------------------------------------
					case 'alu_idioma_ingl' :
						$opciones = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_idioma_alem' :
						$opciones = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_idioma_fran' :
						$opciones = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_idioma_ital' :
						$opciones = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_idioma_port' :
						$opciones = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_idioma_chino' :
						$opciones = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_idioma_otro_nivel' :
						$opciones = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					//---- IDIOMA 3w ---------------------------------------------
					case 'nivel_idioma_ingles' :
						$opciones = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'nivel_idioma_aleman' :
						$opciones = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'nivel_idioma_frances' :
						$opciones = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'nivel_idioma_italiano' :
						$opciones = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'nivel_idioma_portugues' :
						$opciones = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'nivel_idioma_chino' :
						$opciones = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'idioma_otro' :
						$opciones = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					//---- Discapacidad ---------------------------------------------
					case 'condicion_discapacidad' :
						$opciones = toba::consulta_php('co_capacidades_especiales')->get_condicion_discapacidad();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'aud_tipo' :
						$opciones = toba::consulta_php('co_capacidades_especiales')->get_dificultades();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'vis_dificultad_para_ver' :
						$opciones = toba::consulta_php('co_capacidades_especiales')->get_dificultades();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'trabajo_existe' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_existen();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'trabajo_hace' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_hacen();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'trabajo_ocupacion' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_ocupaciones();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'trabajo_hora_sem' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_horas_semanales();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'trabajo_carrera' :
						$opciones = toba::consulta_php('co_personas')->get_trabajos_carreras();
						$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					default :
						if (!$salida)
							$salida = "--";
						break;
				endswitch;
			}
			return $salida;
		}
		return "--";
	}

	function adecuar_valores($array, $campo_clave, $campo_descr)
	{
		$aux = [];
		foreach ($array as $reg) {
			$aux[$reg[$campo_clave]] = $reg[$campo_descr];
		}
		return $aux;
	}

	function get_datos_preinscripcion_formateados($datos)
	{
		// Se descompone el número de celular para mostrarlo en el formulario
		if (!empty($datos['celular_numero'])) {
			$datos_cel = explode('-', $datos['celular_numero']);
			$datos['celular_caracteristica'] = trim($datos_cel[1]);
			$datos['celular_valor'] = trim($datos_cel[3]);
		}

		// Formateo de fechas
		if (!empty($datos['fecha_nacimiento'])) {
			$datos['fecha_nacimiento'] = guarani_fecha::convertir_fecha_formato_frontend($datos['fecha_nacimiento']);
		}
		if (!empty($datos['fecha_respre_otorg'])) {
			$datos['fecha_respre_otorg'] = guarani_fecha::convertir_fecha_formato_frontend($datos['fecha_respre_otorg']);
		}
		if (!empty($datos['fecha_respre_venc'])) {
			$datos['fecha_respre_venc'] = guarani_fecha::convertir_fecha_formato_frontend($datos['fecha_respre_venc']);
		}
		if (!empty($datos['otorgamiento_visa'])) {
			$datos['otorgamiento_visa'] = guarani_fecha::convertir_fecha_formato_frontend($datos['otorgamiento_visa']);
		}
		if (!empty($datos['vencimiento_visa'])) {
			$datos['vencimiento_visa'] = guarani_fecha::convertir_fecha_formato_frontend($datos['vencimiento_visa']);
		}
		if (!empty($datos['fecha_ult_modif'])) {
			$datos['fecha_ult_modif'] = guarani_fecha::convertir_fecha_formato_frontend($datos['fecha_ult_modif']);
		}

		// Campos descriptivos que se obtienen de G3
		$paises = toba::consulta_php('co_mug')->get_paises($datos['pais_documento']);
		$datos['pais_documento'] = current($paises)['descr'];
		$datos['pais_documento_desc'] = current($paises)['descr'];

		$tipo_doc = current(toba::consulta_php('co_personas')->get_tipos_documentos($datos['tipo_documento']));
		$datos['tipo_documento'] = $tipo_doc['descr'];
		$datos['tipo_documento_desc'] = $tipo_doc['descr'];
		$datos['tipo_documento_desc_abreviada'] = $tipo_doc['descr_ab'];

		$nacionalidades = current(toba::consulta_php('co_personas')->get_nacionalidades($datos['nacionalidad']));
		$datos['nacionalidad'] = $nacionalidades['descr'];

		if (isset($datos['loc_nacimiento'])) {
			$datos['loc_nacimiento'] = toba::consulta_php('co_mug')->get_localidad($datos['loc_nacimiento']);
		}

		if (isset($datos['loc_per_lect'])) {
			$datos['loc_per_lect_descr'] = toba::consulta_php('co_mug')->get_localidad($datos['loc_per_lect']);
		}

		if (isset($datos['loc_proc'])) {
			$datos['loc_proc_descr'] = toba::consulta_php('co_mug')->get_localidad($datos['loc_proc']);
		}

		if (isset($datos['loc_pers_alleg'])) {
			$datos['loc_pers_alleg_descr'] = toba::consulta_php('co_mug')->get_localidad($datos['loc_pers_alleg']);
		}

		if (isset($datos['colegio_secundario'])) {
			$colegios = toba::consulta_php('co_personas')->get_colegio_secundario($datos['colegio_secundario']);
			$datos['colegio_secundario_descr'] = $colegios['descr'];
		}

		if (isset($datos['titulo_secundario'])) {
			$titulos = toba::consulta_php('co_personas')->get_nombre_titulo($datos['titulo_secundario']);
			$datos['titulo_secundario_descr'] = $titulos['titulo_nombre'];
		}

		return $datos;
	}

	function get_datos_3w()
	{
		//Obtengo el dato censal.        
		$datos = [];
		$dato_censal = toba::consulta_php('co_datos_censales')->get_id_dato_censal($this->id);
		if (isset($dato_censal['dato_censal'])) {
			$dato_censal = $dato_censal['dato_censal'];
			$cabecera_dato_censal = toba::consulta_php('co_datos_censales')->get_cabecera_dato_censal($dato_censal);
			$datos['fecha_ult_modif'] = $cabecera_dato_censal['fecha_actualizacion_formato_visual'];
			//        $datos = array_merge($datos, $datos_persona, $datos_personales);            
			$datos_personales = current(toba::consulta_php('co_datos_censales')->get_datos_personales($dato_censal));
			if ($datos_personales) {
				$opciones = toba::consulta_php('co_datos_censales')->get_cantidades_hijos();
				$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
				$datos['cantidad_hijos'] = (!empty($opciones[$datos_personales['cantidad_hijos']])) ? $opciones[$datos_personales['cantidad_hijos']] : '--';
				unset($datos_personales['cantidad_hijos']);
				$opciones = toba::consulta_php('co_datos_censales')->get_cantidades_familia();
				$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
				$datos['cantidad_familia'] = (!empty($opciones[$datos_personales['cantidad_familia']])) ? $opciones[$datos_personales['cantidad_familia']] : '--';
				unset($datos_personales['cantidad_familia']);
				$opciones = toba::consulta_php('co_datos_censales')->get_coberturas_salud();
				$opciones = $this->adecuar_valores($opciones, 'valor', 'descr');
				$datos['cobertura_salud'] = (!empty($opciones[$datos_personales['cobertura_salud']])) ? $opciones[$datos_personales['cobertura_salud']] : '--';
				unset($datos_personales['cobertura_salud']);
				$datos = array_merge($datos, $datos_personales);
			}
			$datos_economicos = current(toba::consulta_php('co_datos_censales')->get_datos_economicos($dato_censal));
			if ($datos_economicos) {
				$datos = array_merge($datos, $datos_economicos);
			}

			$datos_actividades = current(toba::consulta_php('co_datos_censales')->get_datos_actividades($dato_censal));
			if ($datos_actividades) {
				$datos = array_merge($datos, $datos_actividades);
			}
			$eleccion_institucion = current(toba::consulta_php('co_datos_censales')->get_datos_eleccion_institucion($dato_censal));
			if ($eleccion_institucion) {
				$datos = array_merge($datos, $eleccion_institucion);
			}
			$eleccion_propuesta = current(toba::consulta_php('co_datos_censales')->get_datos_eleccion_propuesta($dato_censal));
			if ($eleccion_propuesta) {
				$datos = array_merge($datos, $eleccion_propuesta);
			}
			$datos_vivienda = current(toba::consulta_php('co_datos_censales')->get_tipos_viviendas($dato_censal));
			if ($datos_vivienda) {
				$datos['tipo_res_per_lect'] = $datos_vivienda['descr'];
			}
			$datos_discapacidad = current(toba::consulta_php('co_datos_censales')->get_datos_discapacidad($dato_censal));
			if ($datos_discapacidad) {
				$datos = array_merge($datos, $datos_discapacidad);
			}
		}
		$datos_persona = toba::consulta_php('co_personas')->get_datos_persona($this->id);
		if ($datos_persona) {
			$datos['fecha_respre_venc'] = $datos_persona['vencimiento_residencia'];
			$datos['fecha_respre_otorg'] = $datos_persona['otorgamiento_residencia'];
			unset($datos_persona['vencimiento_residencia']);
			unset($datos_persona['otorgamiento_residencia']);
			$datos = array_merge($datos, $datos_persona);
		}
		$datos_colegio_secundario = current(toba::consulta_php('co_datos_censales')->get_datos_colegio_secundario($this->id));
		if ($datos_colegio_secundario) {
			$datos['anio_egreso_sec'] = $datos_colegio_secundario['anio_egreso'];
			$datos['nombre_colegio'] = $datos_colegio_secundario['nombre_colegio'];
			$datos['adeuda_materias'] = $datos_colegio_secundario['adeuda_materias'];
			$datos['titulo_sec'] = $datos_colegio_secundario['titulo'];
			unset($datos_colegio_secundario['anio_egreso']);
			unset($datos_colegio_secundario['nombre_colegio']);
			unset($datos_colegio_secundario['adeuda_materias']);
			unset($datos_colegio_secundario['titulo']);
			$datos = array_merge($datos, $datos_colegio_secundario);
		}

		$datos_estudios_superiores = current(toba::consulta_php('co_datos_censales')->get_datos_estudios_superiores($this->id));
		if ($datos_estudios_superiores) {
			$datos['orientacion_recibida'] = $datos_estudios_superiores['nivel'];
			$datos['alu_otestsup_uni'] = $datos_estudios_superiores['lugar'];
			$datos['alu_otestsup_carr'] = $datos_estudios_superiores['propuesta'];
			$datos['alu_otestsup_niv'] = $datos_estudios_superiores['nivel'];
			$datos['alu_otestsup_esta'] = $datos_estudios_superiores['estado'];
			$datos['alu_otestsup_egre'] = $datos_estudios_superiores['anio_egreso'];
			$datos['alu_otestsup_razon_abandono'] = $datos_estudios_superiores['razon_abandono'];
			unset($datos_estudios_superiores['nivel']);
			unset($datos_estudios_superiores['lugar']);
			unset($datos_estudios_superiores['propuesta']);
			unset($datos_estudios_superiores['estado']);
			unset($datos_estudios_superiores['anio_egreso']);
			unset($datos_estudios_superiores['razon_abandono']);
			$datos = array_merge($datos, $datos_estudios_superiores);
		}
		$pueblo_originarios = current(toba::consulta_php('co_datos_censales')->get_datos_pueblo_originario($this->id));
		if ($pueblo_originarios) {
			$datos['pertenece_pueblo_originario'] = $pueblo_originarios['pertenece_pueblo_originario'];
			$datos['pueblo_originario'] = $pueblo_originarios['nombre_pueblo_originario'];
		}


		$allegado = $this->asigno_allegada(array(), '');
		//voy a buscar los datos de otra persona
		$otro = toba::consulta_php('co_personas')->get_allegados_tipo($this->id, persona::allegado_otro, 'otro');
		if ($otro) {
			$datos = array_merge($datos, $otro);
			$allegado = $this->asigno_allegada($otro, 'otro');
		}

		$madre = toba::consulta_php('co_personas')->get_allegados_tipo($this->id, persona::allegado_madre, 'madre');
		if ($madre) {
			$datos = array_merge($datos, $madre);
			$allegado = $this->asigno_allegada($madre, 'madre');
		}
		if (!empty($datos['situacion_madre'])) {
			$datos['madre_vive'] = $datos['situacion_madre'];
		}
		//voy a buscar los datos de los padres.
		$padre = toba::consulta_php('co_personas')->get_allegados_tipo($this->id, persona::allegado_padre, 'padre');
		if ($padre) {
			$datos = array_merge($datos, $padre);
			$allegado = $this->asigno_allegada($padre, 'padre');
		}
		if (!empty($datos['situacion_padre'])) {
			$datos['padre_vive'] = $datos['situacion_padre'];
		}
		$datos = array_merge($datos, $allegado);

		//Contacto
		$contacto = toba::consulta_php('co_personas')->get_contactos_persona_rest($datos['tipo_doc'], $datos['nro_documento']);
		if ($contacto) {
			if (!empty($contacto['email']))
				$datos['e_mail'] = $contacto['email'];
			else
				$datos['e_mail'] = '--';
			if (!empty($contacto['telefono']))
				$datos['celular_numero'] = $contacto['telefono'];
			else
				$datos['celular_numero'] = '--';
		}

		//Los datos que no tenga formateados los busco antes de devolverlos.
		$paises = toba::consulta_php('co_mug')->get_paises($datos['pais_doc']);
		$datos['pais_documento_desc'] = current($paises)['descr'];

		$tipo_doc = current(toba::consulta_php('co_personas')->get_tipos_documentos($datos['tipo_doc']));
		$datos['tipo_documento_desc'] = $tipo_doc['descr'];
		$datos['tipo_documento_desc_abreviada'] = $tipo_doc['descr_ab'];

		$datos['nacionalidad_desc'] = $datos['nacionalidad_valor'];
		$datos['fecha_nacimiento'] = $datos['fecha_nacimiento_formato_visual'];

		/*
		 * Tengo que traer los tipos de documento cuil, pasaporte, CI.
		 */

		$cuil = toba::consulta_php('co_personas')->get_tipo_documento_persona($this->id, persona::doc_CUIL);
		if (isset($cuil['nro_documento'])) {
			$datos['numero_cuil'] = $cuil['nro_documento'];
		}
		//Pasaporte.
		$pasaporte = toba::consulta_php('co_personas')->get_tipo_documento_persona($this->id, persona::doc_pasaporte);
		if (isset($pasaporte['nro_documento'])) {
			$datos['pais_emisor_pasap'] = $pasaporte['pais'];
			$datos['nro_pasap'] = $pasaporte['nro_documento'];
		}

		//El campo ente emisor no esta en ningún lugar de gestión.
		$cedula = toba::consulta_php('co_personas')->get_tipo_documento_persona($this->id, persona::doc_cedula);
		if (isset($cedula['nro_documento'])) {
			$datos['ent_emisora_ci'] = $cedula['pais'];
			$datos['nro_ci'] = $cedula['nro_documento'];
		}
		return $datos;
	}

	function asigno_allegada($obj, $tipo)
	{
		$datos = array();
		if (!empty($obj) && ($tipo)) {
			$datos['apellido_pers_alleg'] = $obj['apellido_' . $tipo];
			$datos['nombre_pers_alleg'] = $obj['nombres_' . $tipo];
			$datos['tipo_allegado'] = substr(strtoupper($tipo), 0, 1);
			$datos['calle_pers_alleg'] = $obj['calle_' . $tipo];
			$datos['nro_pers_alleg'] = $obj['nro_' . $tipo];
			$datos['piso_pers_alleg'] = $obj['piso_' . $tipo];
			$datos['dpto_pers_alleg'] = $obj['dpto_' . $tipo];
			$datos['unidad_pers_alleg'] = $obj['unidad_' . $tipo];
			$datos['loc_pers_alleg'] = $obj['loc_' . $tipo];
			$datos['loc_pers_alleg_descr'] = $obj['loc_' . $tipo . '_descr'];
			$datos['cp_pers_alleg'] = $obj['cp_' . $tipo];
			$datos['barrio_alleg'] = $obj['barrio_' . $tipo];
			$datos['te_pers_alleg'] = $obj['te_' . $tipo];
		} else {
			$datos['apellido_pers_alleg'] = '--';
			$datos['nombre_pers_alleg'] = '--';
			$datos['tipo_allegado'] = '--';
			$datos['calle_pers_alleg'] = '--';
			$datos['nro_pers_alleg'] = '--';
			$datos['piso_pers_alleg'] = '--';
			$datos['dpto_pers_alleg'] = '--';
			$datos['unidad_pers_alleg'] = '--';
			$datos['loc_pers_alleg'] = '--';
			$datos['loc_pers_alleg_descr'] = '--';
			$datos['cp_pers_alleg'] = '--';
			$datos['barrio_alleg'] = '--';
			$datos['te_pers_alleg'] = '--';
		}
		return $datos;
	}

	function utf8_encode($string)
	{
		if (comunes::es_interfaz_gestion()) {
			$string = utf8_encode($string);
		}
		return $string;
	}

}