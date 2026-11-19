<?php
class ci_nav_habilitacion_inscripcion extends ci_navegacion 
{
	protected $index_msg_confirmar_cambio = '800CAL_habilitar_insc_plan';
		
	//-----------------------------------------------------------------------------------
	//---- Cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{		
		$datos = toba::consulta_php('co_planes')->get_lista_planes_activos($this->get_filtro_condicion());
		
		foreach ($datos as $key => $plan) {
			if ($plan['inscripcion_habilitada'] == 'S') {
				$datos[$key]['inscripcion_habilitada_img'] = toba_recurso::imagen_toba('aplicar.png', true);
			} else {
				$datos[$key]['inscripcion_habilitada_img'] = '';
			}
		}
		
        $cuadro->set_datos($datos);
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$plan = $seleccion['plan'];
		$habilitado = $seleccion['inscripcion_habilitada'];
		
		guarani::act('act_planes')->actualizar_habilitacion_inscripcion($plan, $habilitado);
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		
		if ($datos[$fila]['inscripcion_habilitada'] == comunes::si) {
			$etiqueta = 'Deshabilitar';
			$accion = 'deshabilitación';
		} else {
			$etiqueta = 'Habilitar';
			$accion = 'habilitación';
		}
		
		$evento->set_etiqueta($etiqueta);
		$evento->set_msg_confirmacion($this->get_mensaje_confirmar_cambio($accion, $datos[$fila]));	
	}

	function get_mensaje_confirmar_cambio($accion, $datos)
	{
		return guarani_mensajes::get($this->index_msg_confirmar_cambio, array($accion, $datos['propuesta_nombre'], $datos['plan_nombre']));
	}

}
?>