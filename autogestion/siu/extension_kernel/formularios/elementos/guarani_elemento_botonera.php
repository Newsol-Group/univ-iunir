<?php

namespace siu\extension_kernel\formularios\elementos;

use SIU\Chulupi\interfaz\componentes\forms\elementos\FormElementoMulti;
use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\util\validador;

use siu\extension_kernel\formularios\elementos\guarani_vista_botonera;
use toba;

class guarani_elemento_botonera extends FormElementoMulti
{

	public static function dias_semana(){
		return array(
			'l' => 'Lunes',
			'm' => 'Martes',
			'x' => 'Miercoles',
			'j' => 'Jueves',
			'v' => 'Viernes',
			's' => 'Sabado',
			'd' => 'Domingo',
//			's' => 'sin_definir'
		);
	}

	public static function turnos(){
		$datos = toba::consulta_php('co_comisiones')->get_listado_turnos_cursadas();
		$turnos = array();

		foreach($datos as $d){
			$turnos[$d['valor']] = $d['descr'];
		}
		return $turnos;
	}

	/**
	 * El parametro obligatorio a definir es
	 * FormElementoConfig::multi_options => static::dias_semana()
	 *
	 */
	function __construct($nombre, $def)
	{
		$def = array_merge(array(
					FormElementoConfig::filtro		=>  validador::TIPO_INT,
					FormElementoConfig::validar_select => true,
					FormElementoConfig::valor_default => array(),),
				$def);
		parent::__construct($nombre, $def);
	}

}
?>
