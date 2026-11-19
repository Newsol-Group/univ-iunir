<?php

namespace siu\extension_kernel\formularios\elementos;


use SIU\Chulupi\interfaz\componentes\forms\FormElemento;
use SIU\Chulupi\interfaz\componentes\forms\VistaElemento;

/**
 * Class decorador_boton_derecha
 * * Es un decordador este elemento, emula el comportamiento de cualquier VistaElemento, y lo decora con
 * un boton a la derecha (basado en bootstrap actualmente).
 * @package siu\extension_kernel\formularios\elementos
 */
class decorador_boton_derecha implements VistaElemento{

	protected $decorado;

	protected $html_boton;
	protected $id;
	protected $class = '';


	/**
	 * @param VistaElemento $vista
	 */
	function __construct(VistaElemento $vista)
	{
		$this->decorado = $vista;
	}

	function set_class($class){
		$this->class = $class;
	}

	/**
	 * Dejo pasar todo menos el render.
	 */
	function __call($name, $arguments)
	{
		return call_user_func_array(array($this->decorado, $name), $arguments);
	}


	public function set_boton($inner_html, $id_btn){
		$this->html_boton = $inner_html;
		$this->id = $id_btn;
	}

	public function render(FormElemento $elemento){
		return "<div class='{$this->class}'>".
		"<button id='{$this->id}' class='btn input-append-btn' type='button'>{$this->html_boton}</button>
			<span class='input-append-element'>".
		$this->decorado->render($elemento).
		//."<button id='{$this->id}' class='btn' type='button'>{$this->html_boton}</button>
		"</span></div>";

//		return "<div class='input-append {$this->class}'>".
//			  $this->decorado->render($elemento)
//				."<button id='{$this->id}' class='btn' type='button'>{$this->html_boton}</button>
//			</div>";

	}
	public function renderiza_label()
	{
		return false;
	}
}