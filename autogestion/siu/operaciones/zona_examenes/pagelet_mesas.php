<?php

namespace siu\operaciones\zona_examenes;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_mesas extends pagelet
{
	function get_nombre()
	{
		return 'mesas';
	}

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos = array_merge($this->get_vista_form()->get_js_files(), $archivos);
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/date-es-AR.js");
		return $archivos;
	}

	function get_lista_mesas()
	{
		$mesas = $this->controlador->codificador_modelo()->info__examenes();
		$mesas->link('LINK', 'zona_examenes', 'home');
		$mesas->link('url_imprimir', 'impresion_actas_examen', 'imprimir_acta');

		$columnas = array('fecha_formato_visual', 'mesa_examen_nombre', 'llamado_nombre', 'ubicacion_nombre', 'espacio_nombre', 'turno_examen_nombre');
		$datos = array();
		foreach ($mesas as $mesa) {
			$actividad_key = $mesa['actividad_descr'];
			foreach ($columnas as $campo) {
				$nuevo[$campo] = isset($mesa[$campo]) ? $mesa[$campo] : null;
			}
			$nuevo['link'] = $mesa['LINK'];
			$nuevo['url_imprimir'] = $mesa['url_imprimir'];
			$datos[$actividad_key][] = $nuevo;
		}
		return $datos;
	}

	public function get_vista_form()
	{
		return $this->controlador->get_vista_form();
	}

	function prepare()
	{
		$this->data = array();
		$this->data['mesas'] = $this->get_lista_mesas();
		if (kernel::persona()->perfil()->get_operacion_activa('admin_actas_examen_fc')) {
			$this->data['link_admin_actas_examen_fc'] = kernel::vinculador()->crear('admin_actas_examen_fc');
		}

		$controlador = kernel::ruteador()->get_id_operacion();
		$this->data['url_limpiar_filtro'] = kernel::vinculador()->crear($controlador, 'limpiar_filtro');

		$this->add_var_js('url_filtrar_examenes', kernel::vinculador()->crear('zona_examenes', 'index'));
		$this->add_mensaje_js('msg_error_fechas', kernel::traductor()->trans('zona_examenes.msg_error_fechas'));
		$this->add_mensaje_js('fecha_desde_invalida', kernel::traductor()->trans('zona_examenes.fecha_desde_invalida'));
		$this->add_mensaje_js('fecha_hasta_invalida', kernel::traductor()->trans('zona_examenes.fecha_hasta_invalida'));
		$this->add_mensaje_js('fecha_hasta_menor_fecha_desde', kernel::traductor()->trans('datos_censales.error_fechas'));
		$this->add_mensaje_js('fecha_invalida', kernel::traductor()->trans('zona_examenes.fecha_invalida'));
	}

}
