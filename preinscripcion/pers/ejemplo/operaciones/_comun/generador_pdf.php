<?php
namespace ejemplo\operaciones\_comun;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\generador_pdf as generador_pdf_base;
use siu\preinscripcion;

class generador_pdf extends generador_pdf_base
{
	/**
	 * Como ejemplo de personalización:
	 *  + Se eliminan del reporte (se deja comentado):
	 *		- CBC
	 *		- Avisos
	 *		- Fecha y firma 
	 *		- Credencial provisoria
	 *  + Se cambia el orden de la sección "Datos de estudios": se imprime luego de "Datos personales"
	 */
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

//			if (kernel::proyecto()->get_parametro('rep_imprime_CBC')) {
//				$this->CBC();
//			}

			$this->datos_seccion('datos_principales');
			$this->datos_seccion('datos_personales');
			$this->datos_seccion('financiamiento');
			$this->datos_seccion('datos_laborales');
			$this->datos_seccion('datos_familiares');
			$this->datos_seccion('datos_estudios');
			$this->datos_seccion('tecnologia');
			$this->datos_seccion('deportes');
			$this->datos_seccion('idiomas');

//			$this->avisos();
//			$this->fecha_y_firma();
//			if (kernel::proyecto()->get_parametro('rep_imprime_credencial_provisoria')) {
//				$this->credencial_provisoria();
//			}
		}
	}
	
	//--------------------------------------------------------------------------
	
	/**
	 * Datos que se imprimen:
	 *  + Código de preinscripción
	 *  + Fecha última modificiación
	 *  + Apellido y nombre
	 *  + Espacio para completar nro. LU
	 *  + Espacio para foto
	 * 
	 *  - Se elimina código QR (se deja comentado) 
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

//		// Código QR
//		$outfile_QR = preinscripcion::dir_temp() . '/qr' . kernel::persona()->datos()->get_id_preinscripcion() . '.png';
//		\QRcode::png($codigo_preinsc, $outfile_QR, 'QR_ECLEVEL_H', 5, 0);
//		$this->pdf->addPngFromFile($outfile_QR, $this->puntos_cm(11.6), $this->puntos_cm(23), 100);
//		unlink($outfile_QR);

		// Espacio para la foto
		$this->pdf->rectangle($this->puntos_cm(15.5), $this->puntos_cm(23), $this->puntos_cm($this->dim_cuadro_foto), $this->puntos_cm($this->dim_cuadro_foto));

		// Línea separadora
		$this->pos_y += 1.5 * $this->delta_y;
		$this->linea($this->pos_y);

		$this->pos_y += 0.5 * $this->delta_y;
	}
	
}
?>