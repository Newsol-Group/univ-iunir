<?php
class ci_rep_inscripciones_alumno extends ci_rep_sin_listado_inicial
{
	function get_datos($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_listado_reporte_inscripciones_alumno($filtro);
	}

	function get_tabla_principal()
	{
		return 'vw_comprobantes';
	}
	
	/**
	*    Permite recuperar el mensaje toba actualmente asociado al eof del cuadro.
	*/    

		//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_filtro_asociado($this->dep('filtro'));
		$cuadro->set_usar_ordenamiento_con_cortes();
		/**
			*    Si no está seteado el filtro entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
			*    No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
			*    que debe filtrar para obtener datos.
		*/
		$this->msj_eof = '800_eof_cuadro';
		if (isset($this->s__filtro)) {
			$datos = $this->get_datos($this->s__filtro);
			$cuadro->set_datos($datos);
		if (empty($datos)) {
			$msg = guarani_mensajes::get('800_eof_cuadro', array('Inscripciones'));
			$cuadro->set_eof_mensaje($msg);	
		} elseif (empty($this->s__filtro)) {
			$msg = guarani_mensajes::get('800_eof_cuadro', array('Inscripciones del Alumno con el filtro especificado'));
			$cuadro->set_eof_mensaje($msg);	
		}
			$msg = guarani_mensajes::get('800_eof_cuadro', array('Inscripciones'));
			$this->indx_msg_eof= $msg;
			
		}
	}
	
	//---- filtro -----------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('fecha')->set_condicion_fija('entre');
	}
	
	function datos_encabezado()
	{
		return array();
	}
	
	
	

}