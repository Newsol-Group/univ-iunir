<?php
namespace siu\operaciones\reporte_actas;

use \siu\guarani;
use \siu\modelo\datos\catalogo;
use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\kernel;

class controlador extends controlador_g3w2
{
	function modelo()
	{
		return null;
	}

	function get_clase_vista()
	{
		switch ($this->accion)
		{
			case 'detalle':
				return 'vista_detalle';
		}
		return 'vista';
	}

	function accion__index() {}

	function accion__filtrar_actas()
	{
		$pl = $this->instanciar_pagelet();
		$this->render_raw_json(array(
			'cod' => 1,
			'html' => $pl->filtrar(),
		));
	}

	function accion__detalle() {}

	function accion__generar_pdf_listado()
	{
		$pl = $this->instanciar_pagelet();
		$pl->set_mode(pagelet_acta::IMPRIMIR_LISTADO_PDF);
		list($path_archivo, $nombre_archivo) = $pl->prepare();
                $this->descargar_archivo($path_archivo, $nombre_archivo, true);
	}

	function accion__generar_xls_listado()
	{
		$pl = $this->instanciar_pagelet();
		$pl->set_mode(pagelet_acta::IMPRIMIR_LISTADO_XLS);
		$pl->prepare();
	}

	function accion__generar_pdf_detalle()
	{
		$pl = $this->instanciar_pagelet();
		$pl->set_mode(pagelet_acta::IMPRIMIR_DETALLE_PDF);
		list($path_archivo, $nombre_archivo) = $pl->prepare();
                $this->descargar_archivo($path_archivo, $nombre_archivo, true);
	}

	function accion__generar_xls_detalle()
	{
		$pl = $this->instanciar_pagelet();
		$pl->set_mode(pagelet_acta::IMPRIMIR_DETALLE_XLS);
		$pl->prepare();
	}

	private function instanciar_pagelet()
	{
		$pl = pagelet_acta::instanciar('detalle');
		$pl->set_controlador($this);
		return $pl;
	}

	public function accion__filtrar_actividades()
	{
		$json = array();
		$actividades = catalogo::consultar('unidad_academica', 'actividades', array(
			'term' => $this->getAutocompleteTerm(),
			'plan_version' => guarani::persona()->es_perfil_docente() ? null : guarani::persona()->get_id_plan_version_activo()
		));
		$json[] = array('id' => '', 'label' => 'Todas');
		foreach($actividades as $actividad)
		{
			$json[] = array(
				'id' => $actividad['elemento'],
				'label' => $actividad['nombre'].' ('.$actividad['codigo'].')'
			);
		}
		$this->render_raw_json($json);
	}

	public function get_datos_periodo($url_perlec)
	{
		$docente = array('docente' => kernel::persona()->get_id_docente());
		foreach(catalogo::consultar('unidad_academica', 'periodos_lectivos_todos', $docente) as $periodo){
			if ($periodo['url_perlec'] == $url_perlec){
				return $periodo;
			}
		}
		return false;
	}

	public function get_datos_actividad($elemento)
	{
		return catalogo::consultar('unidad_academica', 'actividad', array('actividad' => $elemento));
	}

	public function get_datos_turno($url_turno)
	{
		$docente = array('docente' => kernel::persona()->get_id_docente());
		foreach(catalogo::consultar('unidad_academica', 'turnos_examen_todos', $docente) as $turno)
			if ($turno['url_turno'] == $url_turno)
				return $turno;
		return false;
	}
}