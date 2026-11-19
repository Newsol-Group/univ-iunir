<?php
namespace nsg01\operaciones\reporte_asistencia;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\operaciones\reporte\pagelet_reporte;
use siu\modelo\entidades\docente;

class pagelet_comision extends pagelet_reporte
{
	protected $subcomision = null;
	protected $comision = null;

	public function get_nombre()
	{
		return 'comision';
	}

        public function get_nombre_archivo(){
		return "Reporte de Asistencia de Alumnos";
	}

	/**
	 * @todo: este y la subco es el mismo dato, ahora se usa para marcar si tiene 
	 * o no subcomision segun si tiene valor
	 */
	public function set_subcomision($id)
	{
		$this->subcomision = $id;
	}

	public function set_comision($id)
	{
		$this->comision = $id;
	}

	protected function configurar()
	{
		$this->set_rpp(15);

		// Configuracion pdf
		$this->set_pdf_encabezado(kernel::traductor()->trans("tit_reporte_asistencia"), "", 112);
		$this->set_pdf_fuente("Courier.afm");
		$this->set_pdf_fuente_texto(10);
		$this->set_pdf_fuente_titulo(13);
		$this->set_pdf_fuente_subtitulo(11);
		$this->set_pdf_hoja("a4");
		$this->set_tiene_encabezado(true);
		$this->set_tiene_encabezado_long(true);
	}

	protected function get_data_reporte($inicio = 0, $todo = false)
	{
		return $this->get_data();
	}

	protected function get_total_registros()
	{
		return \guarani_arreglos::count($this->get_data_reporte(0, true));
	}

	public function get_columnas()
	{
		$titulo = array();
		$titulo['orden'] = array("", array('justification' => 'center', 'width' => 70));
		$titulo['legajo'] = array(kernel::traductor()->trans('reporte_asistencia.legajo'), array('justification' => 'left', 'width' => 25));
		$titulo['apellido_nombres'] = array(kernel::traductor()->trans('reporte_asistencia.alumno'), array('justification' => 'left', 'width' => 25));
		$titulo['nro_identificacion'] = array(kernel::traductor()->trans('reporte_asistencia.nro_identificacion'), array('justification' => 'left', 'width' => 25));
		$titulo['total_inasistencias'] = array(kernel::traductor()->trans('reporte_asistencia.total_inasistencias'), array('justification' => 'left', 'width' => 25));
		$titulo['total_justificadas'] = array(kernel::traductor()->trans('reporte_asistencia.total_justificadas'), array('justification' => 'left', 'width' => 25));
		$titulo['libre_descr'] = array(kernel::traductor()->trans('reporte_asistencia.libre_descr'), array('justification' => 'left', 'width' => 25));
		$titulo['total_clases_hasta_hoy'] = array(kernel::traductor()->trans('reporte_asistencia.total_clases_hasta_hoy'), array('justification' => 'left', 'width' => 25));
		$titulo['total_clases_prevista_anio'] = array(kernel::traductor()->trans('reporte_asistencia.total_clases_prevista_anio'), array('justification' => 'left', 'width' => 25));
		$titulo['porc_asistencia_hasta_hoy'] = array(kernel::traductor()->trans('reporte_asistencia.porc_asistencia_hasta_hoy'), array('justification' => 'left', 'width' => 25));
		$titulo['porc_asistencia_previstas_anio'] = array(kernel::traductor()->trans('reporte_asistencia.porc_asistencia_previstas_anio'), array('justification' => 'left', 'width' => 25));
		return $titulo;
	}

	public function get_data()
	{
		if (isset($this->subcomision)) {
			$datos = $this->controlador->get_info_subcomision();
		} else {
			$datos = $this->controlador->get_info_comision();
		}

                $i = 1;

                $msj_sin_definir = kernel::traductor()->trans('reporte_asistencia.sin_definir');

		foreach ($datos as $key => $dato) {
			$datos[$key]['legajo'] = $dato['legajo'] ?? $msj_sin_definir;
			$datos[$key]['apellido_nombres'] = $dato['apellido_nombres'] ?? $msj_sin_definir;
			$datos[$key]['nro_identificacion'] = $dato['nro_identificacion'] ?? $msj_sin_definir;
			$datos[$key]['total_inasistencias'] = $dato['total_inasistencias'] ?? $msj_sin_definir;
			$datos[$key]['total_justificadas'] = $dato['total_justificadas'] ?? $msj_sin_definir;
			$datos[$key]['libre_descr'] = $dato['libre_descr'] ?? $msj_sin_definir;
			$datos[$key]['total_clases_hasta_hoy'] = $dato['total_clases_hasta_hoy'] ?? $msj_sin_definir;
			$datos[$key]['total_clases_previstas_anio'] = $dato['total_clases_previstas_anio'] ?? $msj_sin_definir;
			$datos[$key]['porc_asistencia_hasta_hoy'] = $dato['porc_asistencia_hasta_hoy'] ?? $msj_sin_definir;
			$datos[$key]['porc_asistencia_previstas_anio'] = $dato['porc_asistencia_previstas_anio'] ?? $msj_sin_definir;
			$datos[$key]['orden'] = $i++;
		}

                return $datos;
	}

	public function prepare()
	{
		parent::prepare();

		$this->data['zona_activa'] = $this->controlador->is_zona_activa();

		$data = array(0 => $this->comision);

		$this->data['url']['generar_pdf'] = kernel::vinculador()->crear('reporte_asistencia', 'generar_pdf_comision', $data);
		$this->data['url']['generar_excel'] = kernel::vinculador()->crear('reporte_asistencia', 'generar_excel_comision', $data);

		$this->data['visualiza_datos_contacto'] = docente::visualiza_datos_contacto_por_comision($this->data['comision']['comision']);
	}

	public function post_configurar()
	{
		$this->set_pdf_encabezado(kernel::traductor()->trans('tit_reporte_asistencia'), "\n" . $this->get_encabezado(), 112);
	}

	protected function get_encabezado()
	{
		$enc = ucfirst(kernel::traductor()->trans('nombre_actividad')) . ': ' . $this->data['comision']['actividad_orden'] . "\n";
		$enc .= ucfirst(kernel::traductor()->trans('comision')) . ': ' . $this->data['comision']['comision_nombre'] . "\n";
		$enc .= ucfirst(kernel::traductor()->trans('ubicacion')) . ': ' . $this->data['comision']['ubicacion_nombre'] . "\n";
		$enc .= ucfirst(kernel::traductor()->trans('nombre_tipo_clase')) . ': ' . $this->data['comision']['tipo_clase'] . "\n";
		if ($this->data['comision']['es_subcomision']) {
			$enc .= ucfirst(kernel::traductor()->trans('nombre_horario')) . ': ' . $this->data['comision']['horario_subcomision'];
		} else {
			$enc .= ucfirst(kernel::traductor()->trans('nombre_horario')) . ': ' . $this->data['comision']['horario_comision'];
		}
		return $enc;
	}

	protected function get_generador_pdf()
	{
		$lineas = (int) ceil(strlen(kernel::traductor()->trans('nombre_horario') . ': ' . ($this->data['comision']['es_subcomision'] ? $this->data['comision']['horario_subcomision'] : $this->data['comision']['horario_comision'])) / 61);
		$pdf = new generador_pdf($this->crear_ezpdf(), (6 + $lineas) * 15);
		return $pdf;
	}

	protected function procesar_data_pdf($pdf, $data_bruto)
	{
		if (!empty($data_bruto)) {
			//$pdf->agregar_bajada_linea(8);
			parent::procesar_data_pdf($pdf, $data_bruto);
		} else {
			$pdf->no_hay_datos();
		}
	}

}