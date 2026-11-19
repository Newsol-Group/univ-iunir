<?php
namespace siu\operaciones\controles;

use siu\guarani;
use siu\extension_kernel\controlador_g3w2;

class controlador extends controlador_g3w2
{
	function modelo()
	{

	}
	
	function accion__index()
	{
		
	}
	
	function accion__test1()
	{
		$ptos_control_a_validar = array(\guarani_punto_de_control::persona_operacion, \guarani_punto_de_control::alumno_operacion);
		$param = array(	'alumno'		=> '2296',
						'persona'		=> '19',
						'fecha'			=> '2013-01-11',
						'plan_version'	=> '1110');	
			$operacion = '800SIUCUR001';			
			 $validador = guarani::validador_puntos_de_control($ptos_control_a_validar, $param, $operacion);
			 $retorno = $validador->controlar();
			 if ($retorno == \guarani_punto_de_control::advertido || $retorno == \guarani_punto_de_control::rechazado) {
				 $mensajes = $this->formatear($validador->get_mensajes());
			 } else {
				 $mensajes = $this->formatear(array(array('mensaje'=> 'pasaron todos los controles', 'tipo' => '' )));
			 }

		$this->render_ajax($mensajes);
	}
	
	function formatear($array)
	{
		$html = '<ul>';
		foreach ($array as $mensaje){
			$html .= "<li class='{$mensaje['tipo']}'>{$mensaje['mensaje']}</li>";
		}
		$html .= '</ul>';

		return $html;
		return var_export($array, true);
	}

}


?>