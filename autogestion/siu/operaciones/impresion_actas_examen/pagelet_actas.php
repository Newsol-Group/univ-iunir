<?php

namespace siu\operaciones\impresion_actas_examen;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\modelo\DecoradorEntidad;

class pagelet_actas extends pagelet
{
	function get_nombre()
	{
		return 'actas';
	}

	function get_actas()
	{
		/* @var $actas \SIU\Chulupi\util\modelo\ProcesadorVista */
		$actas = $this->controlador->codificador_modelo()->info__actas($this->controlador->llamado_mesa_params);
		$actas->link('url_imprimir', 'impresion_actas_examen', 'imprimir_acta');
		$actas->link('url_cerrar', 'impresion_actas_examen', 'cerrar_acta');
		$actas->link('link_edicion', 'admin_actas_examen_fc', 'editar');
		$actas = $actas->get_datos();

		$instancias = $this->controlador->modelo()->info__actas_instancias($this->controlador->llamado_mesa_params);

		foreach ($actas as $key => $acta) {
			$instancias_txt = '';
			$id_acta = $acta['id_acta'];

			foreach ($instancias[$id_acta]['instancias'] as $instancia) {
				$instancias_txt .= $instancia['instancia_nombre'] . '-';
			}
			$instancias_txt = substr($instancias_txt, 0, -1);
			$actas[$key]['instancias'] = $instancias_txt;

			switch ($acta['estado']) {
				case \acta::estado_abierta:
					$actas[$key]['estado_descr'] = kernel::traductor()->trans('estado_acta.abierta');
					break;
				case \acta::estado_anulada:
					$actas[$key]['estado_descr'] = kernel::traductor()->trans('estado_acta.anulada');
					break;
				case \acta::estado_cerrada:
					$actas[$key]['estado_descr'] = kernel::traductor()->trans('estado_acta.cerrada');
					break;
			}

			// Si el docente tiene derechos para editar el acta
			if ($acta['link_edicion'] != DecoradorEntidad::$link_vacio) {
				$acta_cerrada = ($acta['estado'] == \acta::estado_cerrada);

				// El botón editar se deberá habilitar sólo si:
				//  + El acta no está cerrada
				$permite_editar = (!$acta_cerrada);

				// Si no puede editar el acta, se pone link vacío para que deshabilite el botón
				if (!$permite_editar) {
					$actas[$key]['link_edicion'] = DecoradorEntidad::$link_vacio;
				}

				// Se determinan las causas por las cuales no se puede editar el acta de cursada
				if ($acta_cerrada) {
					$actas[$key]['data_btn_editar']['original-title'] = kernel::traductor()->trans('carga_notas_mesa_examen.acta_cerrada');
				}
			}

			// Si el docente tiene derechos para imprimir el acta
			if ($acta['url_imprimir'] != DecoradorEntidad::$link_vacio) {

				$sin_libro_asignado = \acta::solicitar_libro_impresion($id_acta, $acta['cant_alumnos'], true);

				// El botón imprimir se deberá habilitar sólo si:
				//  + No es necesario asignar libro en este paso
				$permite_imprimir = (!$sin_libro_asignado);

				// Si no puede editar el acta, se deja link vacío para que se deshabilite el botón
				if (!$permite_imprimir) {
					$actas[$key]['url_imprimir'] = DecoradorEntidad::$link_vacio;
				}

				// Se determinan las causas por las cuales no se puede imprimir el acta de cursada
				if ($sin_libro_asignado) {
					$actas[$key]['data_btn_imprimir']['original-title'] = kernel::traductor()->trans('carga_notas_mesa_examen.sin_libro_asignado');
				}
			}

			// Si el docente tiene derechos para cerrar el acta
			if ($acta['url_cerrar'] != DecoradorEntidad::$link_vacio) {
				$acta_abierta = ($acta['estado'] == \acta::estado_abierta);
				$porcentaje_carga_suficiente = ($acta['porcentaje_carga'] == 100);
				$sin_libro_asignado = \acta::solicitar_libro_cierre($id_acta, true);

				// El botón cerrar se deberá habilitar sólo si:
				//  + El acta está abierta
				//  + El acta está completa al 100%
				//  + No es necesario asignar libro en este paso
				$permite_cerrar = ($acta_abierta && $porcentaje_carga_suficiente && !$sin_libro_asignado);

				// Si no puede cerrar el acta, se deja el link vacío para que deshabilite el botón
				if (!$permite_cerrar)
					$actas[$key]['url_cerrar'] = DecoradorEntidad::$link_vacio;

				// Se determinan las causas por las cuales no se puede cerrar el acta de cursada
				if (!$acta_abierta) {
					$actas[$key]['data_btn_cerrar']['original-title'] = kernel::traductor()->trans('carga_notas_mesa_examen.acta_no_abierta');
				}
				if (!$porcentaje_carga_suficiente) {
					$actas[$key]['data_btn_cerrar']['original-title'] = kernel::traductor()->trans('carga_notas_mesa_examen.porcentaje_carga_100');
				}
				if ($sin_libro_asignado) {
					$actas[$key]['data_btn_cerrar']['original-title'] = kernel::traductor()->trans('carga_notas_mesa_examen.sin_libro_asignado');
				}
			}
		}
		return $actas;
	}

	function prepare()
	{
		$this->data = array();
		$this->data['acta_estado_cerrada'] = \acta::estado_cerrada;
		$this->data['link_vacio'] = DecoradorEntidad::$link_vacio;
		$this->data['actas'] = $this->get_actas();

		$this->add_var_js('msj_confirm_cierre', kernel::traductor()->trans('800_cerrar_acta'));
		$this->add_var_js('mensaje_aceptar', kernel::traductor()->trans('aceptar'));
                
		$this->data['error_imprimir_acta'] = false;
		if (kernel::sesion()->esta_seteada('error_imprimir_acta')) {
			$this->data['error_imprimir_acta'] = kernel::sesion()->get('error_imprimir_acta');
			kernel::sesion()->borrar('error_imprimir_acta');
		}
	}

}
?>