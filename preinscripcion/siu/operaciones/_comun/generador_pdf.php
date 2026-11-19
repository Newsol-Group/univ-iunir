<?php

namespace siu\operaciones\_comun;

use SIU\Chulupi\kernel;
use SIU\Chulupi\nucleo\recursos;
use siu\preinscripcion;
use siu\modelo\util;

class generador_pdf
{
	// Todas las medidas son expresadas en centímetros
	const ALTO_HOJA = 29.6;

	/** @var preinscripcion_PDF */
	protected $pdf;
	protected $margen_arriba = 3.2;
	protected $margen_abajo = 1.7;
	protected $margen_izq = 2.2;
	protected $margen_der = 2.2;
	protected $dim_cuadro_foto = 3.5; // Dimensión del cuadrado para foto
	protected $alto_credencial = 6.5;
	protected $pos_y;
	protected $delta_y;
	protected $cant_comprobantes;
	protected $modo_comprobante;

	function __construct()
	{
		$this->pdf = kernel::localizador()->instanciar('operaciones\\_comun\\preinscripcion_PDF', 'A4');

		$this->modo_comprobante = kernel::proyecto()->modo_impresion_comprobante();
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

			if (kernel::proyecto()->get_parametro('rep_imprime_CBC')) {
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
			if (kernel::proyecto()->get_parametro('rep_imprime_credencial_provisoria')) {
				$this->credencial_provisoria();
			}
		}
	}

	/**
	 * Genera la salida PDF del comprobante.
	 */
	function get_comprobante()
	{
		$this->pdf->ezStream();
	}

	//--------------------------------------------------------------------------

	protected function obtener_propuestas()
	{
		$salida = array();
		$datos = kernel::persona()->datos()->get_datos_inscripciones();
		foreach ($datos as $dato) {
			$salida[] = $dato;
		}
		return $salida;
	}

	protected function obtener_propuestas_por_ra()
	{
		// Se agrupan las propuestas por Responsable Académica
		$aux = array();
		$datos = kernel::persona()->datos()->get_datos_inscripciones();
		foreach ($datos as $dato) {
			$ra = $dato['responsable_academica'];
			if (empty($aux[$ra])) {
				$aux[$ra] = array();
			}
			$aux[$ra][] = $dato;
		}
		
		// Se aplana la estructura para normalizarla con la forma esperada para recorrerla
		$salida = array();
		foreach($aux as $grupo) {
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
			$this->footer();
		}
	}

	protected function header()
	{
		$header = $this->pdf->openObject();

		// Logo
		$archivo_logo = kernel::proyecto()->get_parametro('rep_encabezado_logo');
		if (!empty($archivo_logo)) {
			$path_img = recursos::get_dir_img();
			$desc_archivo = kernel::localizador()->encontrar_img($path_img, $archivo_logo);
			$path = $desc_archivo['path'];

			$this->pdf->addPngFromFile($path, $this->puntos_cm(2), $this->puntos_cm(27.2), 99);
			$margen = 6;
		} else {
			$margen = 2;
		}


		// Texto de encabezado
		$nombre_institucion = kernel::proyecto()->get_parametro('rep_nombre_institucion');
		$nombre_institucion = $this->bold($nombre_institucion);
		$this->pdf->addText($this->puntos_cm($margen), $this->puntos_cm(28.4), 13, $nombre_institucion);

		$texto_aspirante = $this->bold(kernel::traductor()->trans('rep_ficha_aspirante'));
		$this->pdf->addText($this->puntos_cm($margen), $this->puntos_cm(27.7), 12, $texto_aspirante);

		$apellido = kernel::persona()->datos()->get_campo_comprobante('apellido');
		$nombres = kernel::persona()->datos()->get_campo_comprobante('nombres');

		if (strlen($apellido . ', ' . $nombres) < 30) {
			$this->pdf->addText($this->puntos_cm(3.8 + $margen), $this->puntos_cm(27.7), 12, $apellido . ', ' . $nombres);
		} else {
			$this->pdf->addText($this->puntos_cm(3.8 + $margen), $this->puntos_cm(27.7), 10, $apellido . ',');
			$this->pdf->addText($this->puntos_cm(3.8 + $margen), $this->puntos_cm(27.2), 10, $nombres);
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
		$pattern = kernel::traductor()->trans('pagina') . " {PAGENUM} " . kernel::traductor()->trans('de') . " {TOTALPAGENUM}";
		$setNum = $this->pdf->ezStartPageNumbers($this->puntos_cm(18), $this->puntos_cm(1), 8, 'left', $pattern);

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
		$codigo_preinsc = kernel::persona()->datos()->get_codigo_preinscripcion();
		$this->pos_y = $this->puntos_cm(26.5);
		$texto = kernel::traductor()->trans('rep_ficha_nro') . $codigo_preinsc;
		$texto = $this->bold($texto);
		$this->pdf->ezSetY($this->pos_y);
		$new_y = $this->pdf->ezText($texto, 11);

		// Se calcula el Delta Y para futuros posicionamientos
		$this->delta_y = $new_y - $this->pos_y;

		$this->pos_y = $new_y;

		$texto = kernel::traductor()->trans('rep_fecha_ult_modif') . kernel::persona()->datos()->get_campo_comprobante('fecha_ult_modif');
		$texto = $this->bold($texto);
		$this->pdf->ezSetY($this->pos_y);
		$this->pos_y = $this->pdf->ezText($texto, 11);

		$apellido = kernel::persona()->datos()->get_campo_comprobante('apellido');
		if (strlen($apellido) < 20) {
			$texto = $this->bold(kernel::traductor()->trans('apellido') . ': ') . kernel::persona()->datos()->get_campo_comprobante('apellido');
			$this->pdf->ezSetY($this->pos_y + $this->delta_y);
			$this->pos_y = $this->pdf->ezText($texto, 11);
		} else {
			// Apellido largo: en dos líneas
			$texto = kernel::traductor()->trans('apellido') . ': ';
			$texto = $this->bold($texto);
			$this->pdf->ezSetY($this->pos_y + $this->delta_y);
			$this->pos_y = $this->pdf->ezText($texto, 11);

			$texto = kernel::persona()->datos()->get_campo_comprobante('apellido');
			$this->pos_y = $this->pdf->ezText($texto, 10);
		}

		$nombres = kernel::persona()->datos()->get_campo_comprobante('nombres');
		if (strlen($nombres) < 20) {
			$texto = $this->bold(kernel::traductor()->trans('nombres') . ': ') . kernel::persona()->datos()->get_campo_comprobante('nombres');
			$this->pdf->ezSetY($this->pos_y + $this->delta_y);
			$this->pos_y = $this->pdf->ezText($texto, 11);
		} else {
			// Nombre largo: en dos líneas
			$texto = kernel::traductor()->trans('nombres') . ': ';
			$texto = $this->bold($texto);
			$this->pdf->ezSetY($this->pos_y + $this->delta_y);
			$this->pos_y = $this->pdf->ezText($texto, 11);

			$texto = kernel::persona()->datos()->get_campo_comprobante('nombres');
			$this->pos_y = $this->pdf->ezText($texto, 10);
		}

		$texto = kernel::traductor()->trans('rep_nro_lu') . '..........................';
		$texto = $this->bold($texto);
		$this->pdf->ezSetY($this->pos_y + $this->delta_y);
		$this->pos_y = $this->pdf->ezText($texto, 11);

		$texto = kernel::traductor()->trans('rep_nro_lu_leyenda');
		$this->pos_y = $this->pdf->ezText($texto, 7);

		// Código QR
		$outfile_QR = preinscripcion::dir_temp() . '/qr' . kernel::persona()->datos()->get_id_preinscripcion() . '.png';
		\QRcode::png($codigo_preinsc, $outfile_QR, 'QR_ECLEVEL_H', 5, 0);
		$this->pdf->addPngFromFile($outfile_QR, $this->puntos_cm(11.6), $this->puntos_cm(23), 100);
		unlink($outfile_QR);

		// Espacio para la foto
		$this->pdf->rectangle($this->puntos_cm(15.5), $this->puntos_cm(23), $this->puntos_cm($this->dim_cuadro_foto), $this->puntos_cm($this->dim_cuadro_foto));

		// Línea separadora
		$this->pos_y += 1.5 * $this->delta_y;
		$this->linea($this->pos_y);

		$this->pos_y += 0.5 * $this->delta_y;
	}

	protected function turno_presentacion($propuestas)
	{
                // Se obtienen los turnos
		$turnos = $this->get_turnos_presentacion($propuestas);
                
                // Si no hay turno de presentación no imprimo dicha sección
                if (empty($turnos)) {
                    return false;
                }
                
		$texto = $this->italic($this->bold(kernel::traductor()->trans('rep_turno_mensaje')));
		$this->pdf->ezSetY($this->pos_y + $this->delta_y);
		$this->pos_y = $this->pdf->ezText($texto, 11);
		
		$cols = array(
			'responsable_academica_nombre' => $this->bold(kernel::traductor()->trans('turno.ra')),
			'fecha' => $this->bold(kernel::traductor()->trans('turno.fecha')),
			'hora' => $this->bold(kernel::traductor()->trans('turno.hora')),
			'operador' => $this->bold(kernel::traductor()->trans('turno.operador')),
		);

		$opciones = array(
			'showLines' => '1',
			'showHeadings' => '1',
			'shaded' => '1',
			'maxWidth' => $this->puntos_cm(16.2),
			'xOrientation' => 'center',
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
	 * Obtiene el listado de turnos correspondientes a las Responsables Académicas
	 * de las Propuestas recibidas
	 * 
	 * @param array $propuestas
	 * @return array
	 */
	protected function get_turnos_presentacion($propuestas)
	{
		$turnos = kernel::persona()->datos()->get_listado_turnos();
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
		if (kernel::proyecto()->carga_turno_presentacion()) {
			$this->turno_presentacion($datos);
		}
		
		// Título
		$texto = kernel::traductor()->trans('rep_propuestas_a_insc');
		$texto = $this->bold($texto);
		$this->pdf->ezSetY($this->pos_y);
		$this->pos_y = $this->pdf->ezText($texto, 12);

		// Tabla de propuestas
		foreach ($datos as $indice => $inscripcion) {
			foreach ($inscripcion as $key => $value) {
				$datos[$indice][$key] = $value;
			}
		}

		$cols = array(
			'ra_nombre' => $this->bold(kernel::traductor()->trans('responsable_academica')),
			'propuesta_nombre' => $this->bold(kernel::traductor()->trans('propuesta')),
			'ubicacion_nombre' => $this->bold(kernel::traductor()->trans('ubicacion')),
			'modalidad_nombre' => $this->bold(kernel::traductor()->trans('modalidad')),
		);

		if (kernel::proyecto()->get_parametro('rep_imprime_periodo_insc')) {
			$cols['periodo_insc'] = $this->bold(kernel::traductor()->trans('periodo_inscripcion'));
		}
		
		$opciones = [
			'showLines'		=> '1',
			'showHeadings'	=> '1',
			'shaded'		=> '1',
			'maxWidth'		=> $this->puntos_cm(16.2),
			'xOrientation'	=> 'center',
            'cols'			=> [
				'ra_nombre'			=>	['width' => $this->puntos_cm(4.3)],
				'propuesta_nombre'	=>	['width' => $this->puntos_cm(4.5)],
				'ubicacion_nombre'	=>	['width' => $this->puntos_cm(4.3)],
				'modalidad_nombre'	=>	['width' => $this->puntos_cm(3.1)],
			],
		];

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
		$texto = kernel::traductor()->trans('rep_titulo_cbc');
		$texto = $this->bold($texto);
		$this->pdf->ezSetY($this->pos_y);
		$this->pos_y = $this->pdf->ezText($texto, 12);

		$cols = array(
			'cbc_codigo' => $this->bold(kernel::traductor()->trans('cbc_codigo')),
			'cbc_asignaturas' => $this->bold(kernel::traductor()->trans('cbc_asignaturas')),
			'cbc_calificacion' => $this->bold(kernel::traductor()->trans('cbc_calificacion')),
			'cbc_fecha' => $this->bold(kernel::traductor()->trans('cbc_fecha')),
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

		$texto = kernel::traductor()->trans('cbc_leyenda_cuadro');
		$this->pdf->ezSetY($this->pos_y);
		$this->pos_y = $this->pdf->ezText($texto, 9);

		$this->pos_y += $this->delta_y;
	}

	//--------------------------------------------------------------------------

	protected function datos_seccion($seccion)
	{
		if (util::form()->hay_imprimibles_seccion($seccion)) {
			// Línea separadora
			$this->linea($this->pos_y);

			// Título
			$this->pos_y += 0.5 * $this->delta_y;
			$texto = kernel::traductor()->trans($seccion);
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
				'rowGap' => '3',
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
			$campos = util::form()->get_campos_seccion($seccion);

			foreach ($campos as $nombre_agrupado => $campos_seccion) {
				if (util::form()->hay_imprimibles_agrupado($seccion, $nombre_agrupado)) {
					$datos = array();

					// Título
					$this->pdf->setColor(0.2, 0.2, 0.2);
					$this->pos_y += 0.5 * $this->delta_y;
					$texto = '    ' . kernel::traductor()->trans($nombre_agrupado);
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
						if ((util::form()->visible($campo)) && (util::form()->se_imprime($campo))) {
							if ($columna_1) {
								$dato = array();
								$dato['label_1'] = $this->etiqueta($campo);
								$dato['dato_1'] = kernel::persona()->datos()->get_campo_comprobante($campo);
							} else {
								$dato['label_2'] = $this->etiqueta($campo);
								$dato['dato_2'] = kernel::persona()->datos()->get_campo_comprobante($campo);
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
                                        
					$this->pdf->ezSetY($this->pos_y + ($this->delta_y));
					$this->pos_y = $this->pdf->ezTable($datos, $cols, '', $opciones);

					$this->pos_y += 0.5 * $this->delta_y;
				}
			}

			$this->pos_y += 0.5 * $this->delta_y;
		}
	}

	//--------------------------------------------------------------------------

	protected function avisos()
	{
		$avisos = kernel::proyecto()->get_parametro('rep_avisos');

		if (!empty($avisos)) {
			// Línea separadora
			$this->linea($this->pos_y);

			$cols = array(
				'avisos_nro' => '',
				'avisos_texto' => '',
			);

			$opciones = array(
				'showLines' => '0',
				'showHeadings' => '0',
				'shaded' => '0',
				'width' => $this->puntos_cm(16.2),
				'xOrientation' => 'center',
				'fontSize' => 8,
				'cols' => array(
					'avisos_nro' => array('width' => $this->puntos_cm(0.7)),
					'avisos_texto' => array('justification' => 'full'),
				),
			);

			$datos = array();
			$cant = 1;

			foreach ($avisos as $aviso) {
				$datos[] = array(
					'avisos_nro' => $cant . ')',
					'avisos_texto' => trim($aviso),
				);
				$cant++;
			}

			$this->pdf->ezSetY($this->pos_y + (0.5 * $this->delta_y));
			$this->pos_y = $this->pdf->ezTable($datos, $cols, '', $opciones);
		}
	}

	protected function fecha_y_firma()
	{
		$cols = array(
			'fecha' => '',
			'firma' => '',
		);

		$opciones = array(
			'showLines' => '0',
			'showHeadings' => '0',
			'shaded' => '0',
			'width' => $this->puntos_cm(16.2),
			'xOrientation' => 'center',
			'fontSize' => 13,
			'cols' => array(
				'fecha' => array('justification' => 'left'),
				'firma' => array('justification' => 'center', 'width' => $this->puntos_cm(7)),
			),
		);

		$fecha = date('j/n/Y');

		$localidad = kernel::proyecto()->get_parametro('rep_localidad');
		if ($localidad) {
			$fecha = $localidad . ', ' . $fecha;
		}

		$datos = array(array(
				'fecha' => $fecha,
				'firma' => '.........................................................' . $this->bold(kernel::traductor()->trans('rep_firma')),
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

		// Se elimina nro. de página en la última, si se imprime credencial provisoria
		$this->pdf->ezStopPageNumbers(0, 0, 0);

		// Linea punteada
		$this->pdf->setLineStyle(1, 'round', '', array(2, 2));
		$this->pdf->line($this->puntos_cm(0.5), $this->pos_y, $this->puntos_cm(20.5), $this->pos_y);

		// Título
		$this->pos_y += 0.5 * $this->delta_y;
		$texto = kernel::traductor()->trans('rep_titulo_credencial');
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
		$datos[] = array('datos' => $this->bold(kernel::traductor()->trans('rep_nro_lu')) . '..........................');
		$datos[] = array('datos' => $this->bold(kernel::traductor()->trans('rep_apellido_y_nombre')) . ': ' . kernel::persona()->datos()->get_apellido_y_nombres());
		$identificacion = kernel::persona()->datos()->get_campo_comprobante('tipo_documento_desc_abreviada') . ' ' . kernel::persona()->datos()->get_campo_comprobante('nro_documento');
		$datos[] = array('datos' => $this->bold(kernel::traductor()->trans('rep_identificacion')) . ': ' . $identificacion);

		$mail = kernel::persona()->datos()->get_campo_comprobante('e_mail');
		$dato = $this->bold(kernel::traductor()->trans('email')) . ': ' . $mail;
		$celular = kernel::persona()->datos()->get_campo_comprobante('celular_numero');
		if (!empty($celular) && ($celular != '0--15-')) {
			$dato .= '   -   ' . $this->bold(kernel::traductor()->trans('rep_telefono')) . ': ' . $celular;
		}
		$datos[] = array('datos' => $dato);

		$propuestas = kernel::persona()->datos()->get_nombres_propuestas_insc();
		$datos[] = array('datos' => $this->bold(kernel::traductor()->trans('rep_propuestas')) . ': ' . $propuestas);

		$this->pdf->ezSetY($this->pos_y);
		$this->pos_y = $this->pdf->ezTable($datos, $cols, '', $opciones);
	}

	//--------------------------------------------------------------------------
	/**
	 *
	 * Función que pasa las medidas en centímetros a puntos (todas las medidas en PDF 
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
		return '<b>' . $texto . '</b>';
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

		$this->pdf->setFontFamily('Times-Roman', $tmp);
		$this->pdf->selectFont(preinscripcion::dir_fonts() . 'Times-Roman');
	}

	protected function etiqueta($campo)
	{
		$etiqueta = util::form()->get_etiqueta($campo);
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

}