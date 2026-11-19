<?php 

class ci_nav_ambitos_de_resultados extends ci_navegacion
{
	
	//------ Evento Eliminar ----------------------

	function evt__eliminar()
	{
		$datos = $this->cn()->entidad()->get_datos();
		$ambito = $datos['ambito'];
		$asociaciones = array();
		if ($this->cn()->entidad()->tiene_areas_tematicas_asociadas($ambito)) {
			$asociaciones[] = 'Áreas Temáticas ';
		}
		if ($this->cn()->entidad()->tiene_certificados_asociados($ambito)) {
			$asociaciones[] = 'Certificados';
		}
		if ($this->cn()->entidad()->tiene_actividades_asociadas($ambito)) {
			$asociaciones[] = 'Actividades';
		}
		if (guarani_arreglos::count($asociaciones) != 0) {
			$lista_asociaciones = guarani_texto::armar_lista($asociaciones);		
			$msj_asociaciones = guarani_mensajes::get('800_dato_no_eliminable', array('Ámbito de Resultados', "tiene [$lista_asociaciones] asociados"));
			toba::notificacion()->agregar($msj_asociaciones);
		} else {							// Elimina el Ámbito si no tiene ninguna asociación
			parent::evt__eliminar();
		}
	}
	
}

?>