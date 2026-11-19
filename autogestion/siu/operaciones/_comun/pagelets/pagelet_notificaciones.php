<?php

namespace siu\operaciones\_comun\pagelets;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use \siu\modelo\entidades\parametro;
use siu\modelo\datos\catalogo;

class pagelet_notificaciones extends pagelet
{
	protected $notificaciones = array();

	function ini()
	{
		$this->add_mensaje_js('error_subcomision', kernel::traductor()->trans('notificaciones.errores.subcomision'));
	}

	protected function get_operacion_fisica()
	{
		return '_comun/pagelets';
	}

	function get_nombre()
	{
		return 'notificaciones';
	}

	/* 	protected function get_codigo_response()
	  {
	  if ($this->estado == 'materia_seleccionada_insc') {
	  return 2;
	  }

	  return parent::get_codigo_response();
	  } */
	/**
	 * @todo:
	 * Los dos metodos que siguen hay que cambiarlos
	 * por una inyeccion de datos mas directa...
	 * se estan haciendo dos if iguales en dos lugares distintos
	 */
	function get_js_files()
	{
		$archivos = parent::get_js_files();
//		$archivos[] = kernel::vinculador()->vinculo_recurso("js/jquery_tools_min.js");
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/notificaciones.js");
		return $archivos;
	}

	function get_css_files()
	{
		$archivos = parent::get_css_files();
//		$archivos[] = kernel::vinculador()->vinculo_recurso("css/coda-slider.css");

		return $archivos;
	}

	function agregar_notificacion($msg, $tipo)
	{
		$this->notificaciones[] = array(
			'tipo' => $tipo,
			'mensaje' => $msg
		);
	}

	function prepare()
	{
		// se limpia la memoria para borrar los viejos csrf keys
		$this->memoria()->limpiar();
		//echo "<pre>",print_r($asignaciones,true),"</pre>";
		/* 		$asig=array();
		  foreach($data as $as) {
		  $tmp=array();
		  foreach($as as $k=>$v) {
		  $tmp[] = "'$k': '$v'";
		  }
		  $asig[] = '{'.implode(', ',$tmp).'}';
		  }
		  $this->data['procesar'] = $asig?'true':'false'; */
		$propuestas = kernel::persona()->get_lista_propuestas();
		
		$propuesta_nombre	= "";
		$propuesta_id		= "";
		$ras_nombre			= "";
		
		if (!empty($propuestas)) {
			foreach ($propuestas as $id => $propuesta) {
				if ($id == 0) {
					$propuesta_nombre	.= $propuesta['nombre'];
					$propuesta_id		.= $propuesta['id'];
				} else {
					$propuesta_nombre	.= ", {$propuesta['nombre']}";
					$propuesta_id		.= ", {$propuesta['id']}";
				}
			}
			$ras_id = \toba::consulta_php('co_responsables_academicas', true)->get_propuestas_ras(array($propuesta_id));
			$ras_id = implode(',', $ras_id);
			$ras = \toba::consulta_php('co_responsables_academicas', true)->get_responsables_academicas(" sga_responsables_academicas.responsable_academica in ($ras_id)");
			foreach ($ras as $id => $rasnombre) {
				if ($id == 0) {
					$ras_nombre .= $rasnombre['descr'];
				} else {
					$ras_nombre .= ", {$rasnombre['descr']}";
				}
			}
		}
		$institucion = catalogo::consultar('unidad_academica', 'info_institucion');
		//perosnas
		$this->data['persona']['apellido'] = kernel::persona()->get_solo_apellido();
		$this->data['persona']['nombres'] = kernel::persona()->get_solo_nombres();
		$this->data['persona']['tipo_documento_desc_abreviada'] = kernel::persona()->get_tipo_documento_desc_abreviada();
		$this->data['persona']['nro_documento'] = kernel::persona()->get_nro_documento();
		$this->data['persona']['propuestas'] = $propuesta_nombre;
		$this->data['persona']['institucion'] = $institucion['nombre'];
		$this->data['persona']['ras_nombre'] = $ras_nombre;
		$this->add_var_js('persona', $this->data['persona']);

		$this->data['banda_horaria_cursado'] = parametro::banda_horaria_cursado();
		$this->add_var_js('banda_horaria_cursado', $this->data['banda_horaria_cursado']);
		$data = \siu\guarani::persona()->cursadas()->info__asignaciones();
		$this->data['asignaciones'] = $data;

		$data_inscripciones = \siu\guarani::persona()->examenes()->get_inscripciones();
		$this->data['info_examenes'] = $data_inscripciones;
		$this->add_var_js('asignaciones', $this->data['asignaciones']);

		$op_actual = kernel::ruteador()->get_id_operacion();
		$this->add_var_js('url_refresh', kernel::vinculador()->crear($op_actual, 'info_notificaciones'));
		$this->data['notificaciones'] = $this->notificaciones;
	}

}
?>