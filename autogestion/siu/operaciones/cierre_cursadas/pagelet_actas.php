<?php

namespace siu\operaciones\cierre_cursadas;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\modelo\DecoradorEntidad;

class pagelet_actas extends pagelet
{
	protected $filtro = null;
	protected $subcomision_filtro = null;

	function get_nombre()
	{
		return 'actas';
	}

	function filtrar_comision($seleccion)
	{
		$this->filtro = $seleccion;
	}

	function get_actas_abiertas()
	{
		/* @var $actas \SIU\Chulupi\util\modelo\ProcesadorVista */
		$actas = $this->controlador->codificador_modelo()->info__actas_abiertas($this->filtro);
		// actas_abiertas tiene que devolver un rs_consulta porque es un link externo

		$actas->link('url_cargar_acta', 'notas_cursada_comision', 'edicion');
		$actas->link('url_cerrar_acta', 'cierre_cursadas', 'cerrar_acta');
		$actas->link('url_imprimir', 'cierre_cursadas', 'imprimir_comprobante');

		$actas = $actas->get_datos();
		foreach ($actas as $key => $acta) {
			
			$actas[$key]['data_btn_cerrar'] = [];
			$actas[$key]['data_btn_imprimir'] = [];
			
			// Si el docente tiene derechos para cerrar el acta
			if($acta['url_cerrar_acta'] != DecoradorEntidad::$link_vacio){
			
				$porcentaje_carga_suficiente	= ($acta['porcentaje_carga'] == 100);
				$sin_libro_asignado				= \acta::solicitar_libro_cierre($acta['id_acta']);
				
				$permite_cerrar = ($porcentaje_carga_suficiente && !$sin_libro_asignado);
				
				// Si no puede cerrar el acta le pongo link vacío para que deshabilite el botón
				if(!$permite_cerrar) $actas[$key]['url_cerrar_acta'] = DecoradorEntidad::$link_vacio;
				
				// Determino las causas por las cuales no se puede cerrar el acta de cursada
				if(!$porcentaje_carga_suficiente)	$actas[$key]['data_btn_cerrar']['original-title'] = kernel::traductor()->trans('cierre_actas_comision.porcentaje_carga_100');
				if($sin_libro_asignado)				$actas[$key]['data_btn_cerrar']['original-title'] = kernel::traductor()->trans('cierre_actas_comision.sin_libro_asignado');
			
			}
			
			// Si el docente tiene derechos para imprimir el acta
			if($acta['url_imprimir'] != DecoradorEntidad::$link_vacio){
			
				$sin_libro_asignado	= \acta::solicitar_libro_impresion($acta['id_acta'], $acta['cant_alumnos']);
				
				$permite_imprimir	= !$sin_libro_asignado;
				
				// Si no puede imprimir el acta le pongo link vacío para que deshabilite el botón
				if(!$permite_imprimir) $actas[$key]['url_imprimir'] = DecoradorEntidad::$link_vacio;
				
				// Determino las causas por las cuales no se puede imprimir el acta de cursada
				if($sin_libro_asignado)	$actas[$key]['data_btn_imprimir']['original-title'] = kernel::traductor()->trans('cierre_actas_comision.sin_libro_asignado');
			
			}
			
		}

		return $actas;
	}

	function prepare()
	{
		$this->data = array();
		$this->data['origen_regulares'] = \acta::origen_regulares;
		$this->data['origen_promocionales'] = \acta::origen_promociones;
		$this->data['actas'] = $this->get_actas_abiertas();
		$this->add_var_js('titulo_elija_libro', kernel::traductor()->trans('elija_libro_tomo'));
		$this->add_var_js('boton_cerrar_acta', kernel::traductor()->trans('cerrar_acta'));

		$this->add_mensaje_js('msg_prompt', \guarani_mensajes::get('800_cerrar_acta'));
		$this->add_mensaje_js('aceptar', ucfirst(kernel::traductor()->trans('aceptar')));
		$this->add_mensaje_js('cancelar', ucfirst(kernel::traductor()->trans('cancelar')));

		$this->data['error_imprimir_acta'] = false;
		if (kernel::sesion()->esta_seteada('error_imprimir_acta')) {
			$this->data['error_imprimir_acta'] = kernel::sesion()->get('error_imprimir_acta');
			kernel::sesion()->borrar('error_imprimir_acta');
		}
	}

}