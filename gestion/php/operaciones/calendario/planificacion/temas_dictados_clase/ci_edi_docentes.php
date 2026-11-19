<?php
/**
 * 
 * 
 * @package 	Calendario Académico
 * @subpackage 	Planificación
 *
 */
class ci_edi_docentes extends guarani_pers_ci
{
	protected $datos_docentes_temp;
	
	//-----------------------------------------------------------------------------------
	//---- docentes_asociados -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__docentes_asociados(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_docentes_temp)) {
			$datos = $this->datos_docentes_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_docentes_asociados();
		}
		
		foreach ($datos as $key => $dato) {
			$datos_docente = $this->cn()->get_docente($dato['docente']);
			$datos[$key]['legajo'] = $datos_docente['legajo'];
			$datos[$key]['nombre_docente'] = $datos_docente['nombre_persona'];
			$datos[$key]['responsabilidad_nombre'] = $datos_docente['responsabilidad_nombre'];
		}
		
		$form_ml->set_datos($datos);
	}

	function evt__docentes_asociados__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_docentes_asociados($datos);
		} catch (guarani_error $e) {
			$this->datos_docentes_temp = $datos;
			throw $e;
		}
	}

	function evt__docentes_asociados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_docente_asociado($seleccion);
	}
	

	//-----------------------------------------------------------------------------------
	//---- seleccion_docentes -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_docentes(toba_ei_cuadro $cuadro)
	{
		$docentes = $this->cn()->entidad()->get_datos_docentes_asociados();
		$datos = $this->cn()->get_docentes($docentes, true);
		$cuadro->set_datos($datos);
		if (empty($datos)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_docentes');
			$msg_eof = guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural')));
			$cuadro->set_eof_mensaje($msg_eof);
		}
	}

	function evt__seleccion_docentes__seleccion($seleccion)
	{
		$this->seleccion_docentes_temp = $seleccion;
	}

	function evt__seleccion_docentes__agregar()
	{
		if (isset($this->seleccion_docentes_temp)) {
			foreach ($this->seleccion_docentes_temp as $docente) {
				$this->cn()->entidad()->agregar_docente_asociado($docente);
			}
		}
	}

}

?>