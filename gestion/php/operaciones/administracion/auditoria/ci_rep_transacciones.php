<?php
class ci_rep_transacciones extends ci_rep_sin_listado_inicial
{
	function get_datos($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_listado_reporte_transacciones($filtro);
	}

	function get_tabla_principal()
	{
		return 'transacciones';
	}
	
	/**
	*	Permite recuperar el mensaje toba actualmente asociado al eof del cuadro.
	*/	
	function get_msg_eof($tabla=null)
	{
		
		$indx_msg = $this->indx_msg_eof;
		return guarani_mensajes::get($indx_msg, array('transacciones'));
	}	
		//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_filtro_asociado($this->dep('filtro'));
		$cuadro->set_usar_ordenamiento_con_cortes();
		/**
 		 *	Si no está seteado el filtro entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
		 *	No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
		 *	que debe filtrar para obtener datos.
		*/
		$this->msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro)) {
		} else {
			$this->indx_msg_eof= '800_no_transacciones';
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$datos = $this->get_datos($this->s__filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}
	
	//---- filtro -----------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('nro_transaccion')->set_condicion_fija('es_igual_a');
	}
	
	
	function datos_encabezado() 
	{
		return array();
	}
	
	
	

}
?>