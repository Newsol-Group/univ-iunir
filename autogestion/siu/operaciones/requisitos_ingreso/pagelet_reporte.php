<?php

namespace siu\operaciones\requisitos_ingreso;

use SIU\Chulupi\kernel;
use siu\guarani;
use siu\modelo\datos\catalogo;
use siu\modelo\transacciones\solicitar_certificacion;

class pagelet_reporte extends \siu\operaciones\_comun\operaciones\reporte\pagelet_reporte
{
	protected $datos_consulta;

	function get_nombre()
	{
		return 'reporte';
	}

	function configurar()
	{
		
	}

	function get_columnas()
	{
		$titulo = array();
		$titulo['requisito_nombre'] = "Requisito";
		$titulo['f_tope_presentacion'] = "Fecha Límite Presentación";
		return $titulo;
	}

	function get_datos()
	{
		$datos = guarani::persona()->get_requisitos_todos();
		$operacion = kernel::ruteador()->get_id_operacion();
		foreach ($datos['requisitos'] as $key => $requisito) {
			$datos['requisitos'][$key]['url_doc_digital'] = kernel::vinculador()->crear($operacion, 'doc_digital', array($requisito[catalogo::id]));
		}
		$this->data['datos'] = $datos;
		return $this->data['datos'];
	}

	/**
	 * Recupera los datos
	 * @param entero $pagina	Numero de página que se desea recuperar
	 * @param booleano $todo	Recuperar todos los datos
	 * @return array 
	 */
	function get_data_reporte($pagina = 0, $todo = true)
	{
		return $this->get_datos();
	}

	/**
	 * Retorna la cantidad de datos
	 * @return entero
	 */
	protected function get_total_registros()
	{
		return \guarani_arreglos::count($this->get_data_reporte(0, true));
	}

	function get_nombre_archivo()
	{
		return "requisitos_pendientes";
	}
	
	function prepare() 
	{
		parent::prepare();
		$this->data['usa_repositorio_digital'] = kernel::proyecto()->usa_rdi();
		$this->prepare_constantes();
		$this->prepare_mensaje();
	}

	function prepare_constantes()
	{
		$this->data['estado_no_presentado'] = solicitar_certificacion::estado_no_presentado;
		$this->data['estado_pendiente_validacion'] = solicitar_certificacion::estado_pendiente_validacion;
		$this->data['estado_presentado'] = solicitar_certificacion::estado_presentado;
		$this->data['estado_vencido'] = solicitar_certificacion::estado_vencido;
	}

	function prepare_mensaje()
	{
		$this->data['tipo_mensaje'] = '';
		$this->data['mensaje'] = '';
		// Mensaje
		if (kernel::sesion()->esta_seteada('mensaje')) {
			$this->data['tipo_mensaje'] = kernel::sesion()->get('tipo_mensaje');
			$this->data['mensaje'] = kernel::sesion()->get('mensaje');
			kernel::sesion()->borrar('tipo_mensaje');
			kernel::sesion()->borrar('mensaje');
		}
	}
}