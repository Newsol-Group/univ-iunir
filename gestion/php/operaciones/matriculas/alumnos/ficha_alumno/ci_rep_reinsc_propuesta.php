<?php
class ci_rep_reinsc_propuesta extends ci_reporte_ficha_alumno
{
	
	protected $s__titulo;    
	
	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
		$this->s__titulo = 'REINSCRIPCIONES A PROPUESTA DEL ALUMNO';
	}
	
	function get_datos($filtro)
	{
		$persona = $this->controlador()->controlador()->get_persona_seleccionada();
		return toba::consulta_php('co_alumnos')->reinscripciones_reporte($persona);
	}
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = $this->get_datos('');
		$cuadro->set_datos($datos);
		if (empty($datos)) 
		{
			$etiqueta = guarani_etiquetas::get_tabla('sga_reinscripciones');
			$msg = guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural')));
			$cuadro->set_eof_mensaje($msg);
			$this->pantalla('pant_inicial')->eliminar_evento('pdf');
		}
	}
	
}
?>