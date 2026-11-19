<?php

namespace siu\operaciones\evaluaciones;

use SIU\Chulupi\interfaz\pagelet;
use \SIU\Chulupi\kernel;
use siu\operaciones\_comun\operaciones\reporte\generador_excel;
use siu\operaciones\_comun\operaciones\reporte\super_generador_pdf;

class pagelet_lista_notas extends pagelet
{
	function get_nombre()
	{
		return 'lista_notas';
	}

	function prepare()
	{
		//$evaluacion_url = kernel::url()->get_param(0);
		$eval = $this->controlador->params_comision;

		/** @var $evaluaciones ProcesadorVista */
		$evaluaciones = $this->controlador->codificador_modelo()->info__arbol_evaluaciones($eval);

		$evaluaciones->link('url_pdf', 'evaluaciones', 'listar_notas_pdf');
		$evaluaciones->link('url_xls', 'evaluaciones', 'listar_notas_xls');
		$evaluaciones->link('url_cerrar_evaluacion', 'evaluaciones', 'cerrar_evaluacion');
		$evaluaciones->link('url_reabrir_evaluacion', 'evaluaciones', 'reabrir_evaluacion');
		$links = $evaluaciones->get_datos();

		foreach ($links as $link) {
			if ($link['evaluacion'] == $eval['evaluacion']) {
				$this->data['url_generar_pdf'] = $link['url_pdf'];
				$this->data['url_generar_xls'] = $link['url_xls'];
				// si tiene derechos

				if ($this->controlador->tiene_derechos_cerrar_evaluacion()) {
					klog2('tiene_derechos_cerrar_evaluacion', 'tiene_derechos_cerrar_evaluacion');
					$this->data['derecho_cerrar_evaluacion'] = true;
					$this->data['url_cerrar_evaluacion'] = $link['url_cerrar_evaluacion'];
				}
				if ($this->controlador->tiene_derechos_abrir_evaluacion()) {
					klog2('tiene_derechos_abrir_evaluacion', 'tiene_derechos_abrir_evaluacion');
					$this->data['derecho_reabrir_evaluacion'] = true;
					$this->data['url_reabrir_evaluacion'] = $link['url_reabrir_evaluacion'];
				}
			}
		}
		if (kernel::sesion()->esta_seteada('mensaje_error')) {
			$this->data['mensajes'] = kernel::sesion()->get('mensaje_error');
			kernel::sesion()->borrar('mensaje_error');
		}
		$this->data['cabecera'] = $this->controlador->modelo()->info__evaluacion_cabecera($eval);
		$this->data['alumnos'] = $this->controlador->modelo()->info__evaluacion_detalle($eval);
		$this->data['url_back'] = $this->controlador->get_index_url();

		$this->add_var_js('url_escala', kernel::vinculador()->crear('evaluaciones', 'info_escala', $this->data['cabecera']['escala_nota']));
	}

	public function generar_pdf()
	{
		$eval = $this->controlador->params_comision;

		$pdf = new super_generador_pdf();
                $nombre_archivo = $this->get_nombre_archivo().'.pdf';
		$pdf->set_report($nombre_archivo);
		$pdf->set_header_title($this->get_nombre_archivo());
		$pdf->set_header_subtitle($this->get_encabezado($eval));
		$pdf->tabla($this->datos_impresion($eval), $this->get_columnas_impresion());
		$path_archivo = $pdf->generate(false);
                return [$path_archivo, $nombre_archivo];
	}

	public function generar_xls()
	{
		$eval = $this->controlador->params_comision;
		$xls = new generador_excel();
		$xls->set_nombre_archivo($this->get_nombre_archivo());
		$xls->agregar_tabla($this->datos_impresion($eval), $this->get_columnas_impresion(), 'Listado de notas');
		$xls->generar();
	}

	private function get_nombre_archivo()
	{
		return 'Notas de evaluaciones';
	}

	private function get_encabezado($evaluacion)
	{
		$cabecera = $this->controlador->modelo()->info__evaluacion_cabecera($evaluacion);

		return ucfirst(kernel::traductor()->trans('evaluacion')) . ': ' . $cabecera['evaluacion_nombre'] . PHP_EOL
				. ucfirst(kernel::traductor()->trans('escala_notas')) . ': ' . $cabecera['escala_nota_nombre'] . PHP_EOL
				. ucfirst(kernel::traductor()->trans('comision')) . ': ' . $cabecera['comision_nombre'] . PHP_EOL
				. ucfirst(kernel::traductor()->trans('catedra')) . ': ' . $cabecera['catedra_nombre'] . PHP_EOL
				. ucfirst(kernel::traductor()->trans('actividad')) . ': ' . $cabecera['actividad_nombre'] . ' (' . $cabecera['actividad_codigo'] . ')' . PHP_EOL;
	}

	private function datos_impresion($evaluacion)
	{
		return $this->controlador->modelo()->info__evaluacion_detalle($evaluacion);
	}

	private function get_columnas_impresion()
	{
		return array(
			'apellido_nombres_elegido' => ucfirst(kernel::traductor()->trans('nombre_alumno')),
			'legajo' => ucfirst(kernel::traductor()->trans('legajo')),
			'nota_nombre' => ucfirst(kernel::traductor()->trans('nota')),
			'nota' => ucfirst(kernel::traductor()->trans('nota numérica')),
			'resultado_nombre' => ucfirst(kernel::traductor()->trans('resultado')),
			'corregido_por' => ucfirst(kernel::traductor()->trans('corregido_por')),
			'observaciones' => ucfirst(kernel::traductor()->trans('observaciones')),
		);
	}

}