<?php

namespace siu\operaciones\_comun\zonas;


use SIU\Chulupi\kernel;
use SIU\Chulupi\util\modelo\AdaptadorModelo;
use SIU\Chulupi\util\modelo\ProcesadorVista;
use SIU\Chulupi\util\modelo\DecoradorEntidad;
use siu\modelo\entidades\fuente_rs_guarani;

abstract class zona
{
	public $datos_detalle;
	protected $params_seleccion;
	protected $codificador_modelo;

	protected $links_navegacion;
	protected $links_combo;

	function __construct($controlador)
	{
		$this->instanciar_pagelet($controlador);
	}

	public function procesar()
	{
		$this->codificador_modelo = $this->instanciar_codificador_modelo();
		$operaciones = $this->get_operaciones_activas();
		$operacion_actual = kernel::ruteador()->get_id_operacion();
		/** @var $entidades ProcesadorVista */
		$entidades = $this->get_datos_navegacion($this->codificador_modelo);

		$this->generar_links($operacion_actual, $operaciones, $entidades);
		$this->datos_detalle = $this->get_datos_detalle($this->get_transaccion(), $this->params_seleccion);
	}

	public function get_template()
	{
		return null; //default.twig
	}

	function get_links_navegacion()
	{
		return $this->links_navegacion;
	}

	function get_links_combo()
	{
		return $this->links_combo;
	}

	function get_datos()
	{
		return $this->datos_detalle;
	}

	public function set_entidad_seleccionada($id_entidad)
	{
		$this->params_seleccion = $id_entidad;
	}

	protected function get_operaciones_activas()
	{
		$operaciones = $this->get_operaciones_zona();
		$status = kernel::persona()->perfil()->get_listado_estado_operaciones();

		foreach ($operaciones as $id => $op) {
			if(!isset($status[$id]) || !$status[$id]){
				unset($operaciones[$id]);
			}
		}
		return $operaciones;
	}

	//-- Metodos de las subclases --

	protected abstract function get_operaciones_zona();

	protected abstract function get_transaccion();

	/**
	 * Operacion para poner en url-back
	 * @return url
	 */
	public abstract function get_link_indice();

	/**
	 * Ejectua la consulta indice de la zona sobre la transaccion creada en get_transaccion() en modo seguro
	 * (con derechos y hasheo)
	 */
	protected abstract function get_datos_navegacion($codificador_modelo);

	protected abstract function get_descripcion_fila($fila);


	/**
	 * @param $transaccion get_transaccion() plano--> no se debe devolver un modelo cofificado/derechos/hasheado
	 * @param $seleccion
	 * @return array Los datos para mostrar en el detalle (en el twig customizado de la zona)
	 */
	protected abstract function get_datos_detalle($transaccion, $seleccion);


	//-- Metodos de las subclases --

	protected function instanciar_codificador_modelo()
	{
		$p = new AdaptadorModelo(
			$this->get_transaccion(),
			new fuente_rs_guarani(),
			'op',
			'accion');
		$p->habilitar_control_entidades();
		return $p;
	}

	protected function generar_links($operacion_actual, $operaciones, ProcesadorVista $entidades)
	{
		$links_combo = array();
		$op_default = $this->get_operacion_default($operaciones);

		//hago los links para todas las entidades a la operacion actual
		$entidades->link('combo_', $operacion_actual, $operaciones[$operacion_actual]['accion']);
		$entidades->link('combo_no_acceso', $op_default, $operaciones[$op_default]['accion']);

		$id_seleccionado = $this->get_id_fila($this->params_seleccion);

		foreach ($entidades as $id => $entidad) {
			$texto = $this->get_descripcion_fila($entidad);
			if ($entidad['combo_'] != DecoradorEntidad::$link_vacio) {
				$url = $entidad['combo_'];
			} else {
				$url = $entidad['combo_no_acceso'];
			}

			$combo = array('texto' => $texto, 'url' => $url);
			if ($entidades->get_id_fila($id) == $id_seleccionado) {
				$this->links_navegacion = $this->generar_links_nav($id, $entidades, $operacion_actual, $operaciones);
				$combo['selected'] = true;

			}

			$links_combo[] = $combo;
		}

		$this->links_combo = $links_combo;
	}

	protected function generar_links_nav($id_fila, ProcesadorVista $entidades, $operacion_actual, $operaciones)
	{
		foreach ($operaciones as $id => $operacion) {
			$nombre_columna_con_link = $id;
			$entidades->link_fila($id_fila, $id, $id, $operacion['accion']);
			$operaciones[$id]['url'] = $entidades[$id_fila][$nombre_columna_con_link];
			if ($id == $operacion_actual) {
				$operaciones[$id]['activo'] = true;
			}
		}
		return $operaciones;
	}

	protected function instanciar_pagelet($controlador)
	{
		$clase = 'operaciones\\_comun\\zonas\\pagelet_zona';
		$pl = kernel::localizador()->instanciar($clase, 'zona');

		$controlador->vista()->add_pagelet($pl);
		$pl->set_zona($this);
	}

	/**
	 * //retorna el primero por defautl
	 * @param $operaciones por si la quiere usar.. las operaciones de la zona
	 * @return string key de la operacion defautl
	 */
	protected function get_operacion_default($operaciones)
	{
		reset($operaciones);
		return key($operaciones);
	}

	/**
	 * retorna el id de la fila
	 * @param $seleccion
	 * @return mixed
	 */
	protected abstract function get_id_fila($seleccion);


}

?>
