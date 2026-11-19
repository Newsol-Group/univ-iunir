<?php
namespace siu\operaciones\inscriptos_cursadas;

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
		return "Detalle de inscripción a cursada";
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
		$this->set_pdf_encabezado(kernel::traductor()->trans("tit_inscriptos_cursada"), "", 112);
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
		$titulo['legajo'] = array(kernel::traductor()->trans('inscriptos_cursadas.legajo'), array('justification' => 'left', 'width' => 25));
		$titulo['apellido_nombres'] = array(kernel::traductor()->trans('inscriptos_cursadas.alumno'), array('justification' => 'left', 'width' => 25));
		$titulo['estado_inscripcion'] = array(kernel::traductor()->trans('inscriptos_cursadas.estado'), array('justification' => 'left', 'width' => 25));
		$titulo['instancias'] = array(kernel::traductor()->trans('inscriptos_cursadas.instancias'), array('justification' => 'left', 'width' => 25));

                $comision = $this->controlador->getComision();
                $visualiza_datos_contacto = docente::visualiza_datos_contacto_por_comision($comision);
                
                // Si el docente puede visualizar los datos de contacto de los alumnos (email y teléfono)
                if ($visualiza_datos_contacto) {
                    $titulo['email'] = array(kernel::traductor()->trans('inscriptos_cursadas.email'), array('justification' => 'left', 'width' => 25));
                    $titulo['telefono'] = array(kernel::traductor()->trans('inscriptos_cursadas.telefono'), array('justification' => 'left', 'width' => 25));
                }

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

                $msj_sin_definir = kernel::traductor()->trans('inscriptos_cursadas.sin_definir');

		foreach ($datos as $key => $dato) {
			$datos[$key]['legajo'] = $dato['legajo'] ?? $msj_sin_definir;
			$datos[$key]['apellido_nombres'] = $dato['apellido_nombres'] ?? $msj_sin_definir;
			$datos[$key]['estado_inscripcion'] = $dato['estado_inscripcion'] ?? $msj_sin_definir;
			$datos[$key]['instancias'] = $dato['instancias'];
			$datos[$key]['email'] = $dato['email'] ?? $msj_sin_definir;
			$datos[$key]['telefono'] = $dato['telefono'] ?? $msj_sin_definir;
			$datos[$key]['orden'] = $i++;
		}

                return $datos;
	}

	public function prepare()
	{
		parent::prepare();

		$this->data['zona_activa'] = $this->controlador->is_zona_activa();

		$data = array(0 => $this->comision);

		$this->data['url']['generar_pdf'] = kernel::vinculador()->crear('inscriptos_cursadas', 'generar_pdf_comision', $data);
		$this->data['url']['generar_excel'] = kernel::vinculador()->crear('inscriptos_cursadas', 'generar_excel_comision', $data);

		$this->data['visualiza_datos_contacto'] = docente::visualiza_datos_contacto_por_comision($this->data['comision']['comision']);
	}

	public function post_configurar()
	{
		$this->set_pdf_encabezado(kernel::traductor()->trans('tit_inscriptos_cursada'), "\n" . $this->get_encabezado(), 112);
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