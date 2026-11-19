<?php
namespace siu\operaciones\oferta_comisiones;

use SIU\Chulupi\interfaz\pagelet;
use siu\guarani;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;

class pagelet_reporte
	extends pagelet
{
	public function get_nombre()
	{
		return 'reporte';
	}

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/plugin_autocompletar.js");
		return $archivos;
	}

	function prepare()
	{
		$this->data['oferta'] = $this->controlador->get_datos();
		$filtros = $this->controlador->get_filtros();
		$this->data['filtros'] = $filtros;
		$this->data['filtros']['dias'] = array(
			'Lunes' => 'Lunes',
			'Martes' => 'Martes',
			'Miercoles' => 'Miércoles',
			'Jueves' => 'Jueves',
			'Viernes' => 'Viernes',
			'Sabado' => 'Sábado',
			'Domingo' => 'Domingo',
			'sin_definir' => 'Sin definir'
		);

		$this->add_var_js('url_buscar_docentes', kernel::vinculador()->crear('oferta_comisiones', 'buscar_docentes'));
		$this->add_var_js('url_buscar_actividades', kernel::vinculador()->crear('oferta_comisiones', 'buscar_actividades'));
		
		$this->add_var_js('es_perfil_docente', false);
		// Si es perfil docente
		if (kernel::persona()->es_perfil_docente()) {
			// Mismo lógica que la función "accion__buscar_docentes" de "src/siu/operaciones/oferta_comisiones/controlador.php"
			$this->add_var_js('es_perfil_docente', true);
			$nombre_docente = kernel::persona()->get_solo_nombres();
			$apellido_docente = kernel::persona()->get_solo_apellido();
			$legajo_docente = kernel::persona()->get_legajo_docente();
			$this->add_var_js('docente_autocomplete_id', "$apellido_docente $nombre_docente");
			$this->add_var_js('docente_autocomplete_label', "$apellido_docente, $nombre_docente ($legajo_docente)");
		}
	}
}