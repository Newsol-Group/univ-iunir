<?php 
/**
 *
 *
 * @package Operaciones Generales
 * @subpackage Busqueda Localidad
 */
class ci_seleccion_localidad_cp extends ci_seleccion_localidad
{
	
	protected $s__datos_localidad;

	function get_datos_cod_postales($localidad)
	{
		return toba::consulta_php('co_mug')->get_codigos_postales($localidad);
	}
	
	function mostrar_codigos_postales($localidad)
	{	$codigos_postales = toba::consulta_php('co_mug')->get_codigos_postales($localidad);

		return guarani_arreglos::count($codigos_postales) > 1;
	}
	
	//---- Cuadro de Localidades ---------------------------------------------------------
	
	/**
	 * Se configura el evento del cuadro de localidades, para cada fila del mismo. Si la localidad asociada a la fila tiene solo un 
	 * codigo postal, entonces se asociará al evento como acción predefinida la respuesta pop up, para que directamente se 
	 * retorne la localidad con su codigo postal. Si la localidad tiene varios codigos postales asociados, entonces no se asociará al
	 * evento ninguna acción predefinida (se realizará una selección normal), para que se pase a la segunda pantalla donde se permitirá
	 * al usuario seleccionar el cod. postal que desea para la localidad.
	 */
	function conf_evt__cuadro_localidades__seleccion($evento,$fila)
	{
		$datos = $this->dep('cuadro_localidades')->get_datos();
		$id_localidad = guarani_texto::extraer_subclave($datos[$fila]['localidad_valor']);			// en localidad valor se almacena el id de localidad y el de cp separados por :.
		if ($this->mostrar_codigos_postales($id_localidad)) {
			$accion = '';
		} else {
			$this->dep('cuadro_localidades')->desactivar_modo_clave_segura();
			$accion = 'P';
		}
		$evento->set_accion_respuesta_popup($accion);
	}

	function evt__cuadro_localidades__seleccion($seleccion)
	{
		$this->s__datos_localidad = current($seleccion);
		$this->set_pantalla('seleccion_cp');	
	}
	
	function conf__cuadro_localidades(toba_ei_cuadro $cuadro)
	{
		parent::set_inclusion_cod_postal(true);
		parent::conf__cuadro_localidades($cuadro);
	}

	//---- Cuadro de Codigos Postales ------------------------------------------------
	
	function conf__cuadro_CP(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		$id_localidad = guarani_texto::extraer_subclave($this->s__datos_localidad);
		$datos = $this->get_datos_cod_postales($id_localidad);
		$cuadro->set_datos($datos);
	}
	
}
?>