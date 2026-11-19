<?php

class guarani_ficha_preinscripcion extends guarani_ficha_preinscripcion_nucleo
{	
protected function propuestas($datos)
	{
		// Si carga turno de presentación, se muestra en la ficha antes de las propuestas
		if ($this->origen == preinscripcion::origen_preinscripcion && guarani_configuracion::instancia()->get_valor_config('pre_turno_presentacion')) {
			$this->turno_presentacion($datos);
		}

 		if ($this->origen == preinscripcion::origen_autogestion) {
					$turnos = siu\guarani::preinscripcion()->get_listado_turnos();
					// Si hay turnos
					if (!empty($turnos)) {
						$this->turno_presentacion($datos);
					}
                    
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
}