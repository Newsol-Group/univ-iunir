<?php
namespace siu\operaciones\inscriptos_examenes;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\operaciones\reporte\pagelet_reporte;
use siu\modelo\entidades\docente;

class pagelet_mesa extends pagelet_reporte
{
	private $mesa = null;

	public function get_nombre()
	{
		return 'mesa';
	}

	public function set_mesa($id)
	{
		$this->mesa = $id;
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
		$titulo['legajo'] = array(kernel::traductor()->trans('inscriptos_examenes.legajo'), array('justification' => 'left', 'width' => 25));
		$titulo['nombre_alumno'] = array(kernel::traductor()->trans('inscriptos_examenes.alumno'), array('justification' => 'left', 'width' => 25));
		$titulo['instancia_nombre'] = array(kernel::traductor()->trans('inscriptos_examenes.instancia'), array('justification' => 'left', 'width' => 25));
		$titulo['estado_inscripcion'] = array(kernel::traductor()->trans('inscriptos_examenes.estado'), array('justification' => 'left', 'width' => 25));

                $llamado_mesa = $this->controlador->getLlamadoMesa();
                $visualiza_datos_contacto = docente::visualiza_datos_contacto_por_llamado_mesa($llamado_mesa);
                
                // Si el docente puede visualizar los datos de contacto de los alumnos (email y teléfono)
                if ($visualiza_datos_contacto) {
                    $titulo['email'] = array(kernel::traductor()->trans('inscriptos_examenes.email'), array('justification' => 'left', 'width' => 25));
                    $titulo['telefono'] = array(kernel::traductor()->trans('inscriptos_examenes.telefono'), array('justification' => 'left', 'width' => 25));
                }

		return $titulo;
	}

	public function get_data()
	{
		$nuevo = array();
		$datos = $this->controlador->get_info_mesa_alumnos();
		$i = 1;
		foreach ($datos as $key => $dato) {
			$nuevo[$key]['legajo'] = empty($dato['legajo']) ? 'sin definir' : $dato['legajo'];
			$nuevo[$key]['nombre_alumno'] = empty($dato['nombre_alumno']) ? 'sin definir' : $dato['nombre_alumno'];
			$nuevo[$key]['estado_inscripcion'] = empty($dato['estado_inscripcion']) ? 'sin definir' : $dato['estado_inscripcion'];
			$nuevo[$key]['instancia_nombre'] = empty($dato['instancia_nombre']) ? 'sin definir' : $dato['instancia_nombre'];
			$nuevo[$key]['email'] = empty($dato['email']) ? 'Email: sin definir' : $dato['email'];
			$nuevo[$key]['telefono'] = empty($dato['telefono']) ? 'Telefono: sin definir' : $dato['telefono'];
			$nuevo[$key]['orden'] = $i++;
		}
		return $nuevo;
	}

	public function prepare()
	{
		parent::prepare();

		//$this->data['zona_activa'] = $this->controlador->is_zona_activa();
		$data = array(0 => $this->mesa);

	//	$this->data['url_back'] = kernel::vinculador()->crear('inscriptos_examenes', 'index', $this->controlador->datos_filtro);
		$this->data['url_generar_pdf'] = kernel::vinculador()->crear('inscriptos_examenes', 'generar_pdf_mesa', $data);
		$this->data['url_generar_xls'] = kernel::vinculador()->crear('inscriptos_examenes', 'generar_excel_mesa', $data);

                $llamado_mesa = $this->controlador->getLlamadoMesa();
                $this->data['visualiza_datos_contacto'] = docente::visualiza_datos_contacto_por_llamado_mesa($llamado_mesa);
	}

	public function post_configurar()
	{
		$this->set_pdf_encabezado(kernel::traductor()->trans('inscriptos_examenes'),  "\n" . $this->get_encabezado(), 112);
	}

	private function get_encabezado()
	{
		$enc = ucfirst(kernel::traductor()->trans('nombre_actividad')) . ': ' . $this->data['mesa']['actividad_nombre'] .  "\n";
		$enc .= ucfirst(kernel::traductor()->trans('fecha')) . ': ' . $this->data['mesa']['fecha_examen'] .  "\n";
		$enc .= ucfirst(kernel::traductor()->trans('ubicacion')) . ': ' . $this->data['mesa']['ubicacion_nombre'] .  "\n";
		$enc .= ucfirst(kernel::traductor()->trans('horario')) . ': ' . $this->data['mesa']['hora_inicio_finalizacion'] .  "\n";
//		$enc .= ucfirst(kernel::traductor()->trans('docentes')) . ': ' . $this->data['mesa']['docentes'] . "\n";
//		$enc .= ucfirst(kernel::traductor()->trans('instancias')) . ': ' . $this->data['mesa']['instancias'] . "\n";
//		$enc .= ucfirst(kernel::traductor()->trans('turno')) . ': ' . $this->data['mesa']['turno_examen_nombre'] . "\n";
		$enc .= ucfirst(kernel::traductor()->trans('llamado')) . ': ' . $this->data['mesa']['llamado_nombre'] .  "\n";
		
		return $enc;
	}

	protected function get_generador_pdf()
	{
		//$lineas = (int)ceil(strlen(kernel::traductor()->trans('nombre_horario') . ': ' . ($this->data['comision']['es_subcomision'] ? $this->data['comision']['horario_subcomision'] : $this->data['comision']['horario_comision'])) / 61);
		//		$lineas = (int)ceil(strlen(\SIU\Chulupi\kernel::traductor()->trans('nombre_horario').': '.end(explode(PHP_EOL, $this->encabezado['subtitulo'])))/61);
		//$pdf = new generador_pdf($this->crear_ezpdf(), (6 + $lineas) * 15);
		$pdf = new generador_pdf($this->crear_ezpdf());
		//$pdf->set_margins(125);
		return $pdf;
	}

	protected function procesar_data_pdf($pdf, $data_bruto)
	{
		if (!empty($data_bruto)) {
			parent::procesar_data_pdf($pdf, $data_bruto);
                } else {
			$pdf->no_hay_datos();
		}
	}
	
	public function get_nombre_archivo()
	{
		return "inscriptos_examenes_mesa";
	}
}