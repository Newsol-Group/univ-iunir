<?php
/**
 *
 *
 * @package 	Unidades de Gestión
 * @subpackage 	Unidades de Gestión
 * 
 */
class ci_edi_unidades_gestion extends guarani_pers_ci
{
	protected $s__filtro_dependencias;
	protected $s__datos_dependencias;
	
	protected $dependencias_seleccionadas;
	
	protected $datos_ug_temp;
	
	//--------------------------------------------------------------------------
	//---- PANTALLA: datos_principales
	//--------------------------------------------------------------------------
	
	//--------------------------------------------------------------------------
	//---- form_unidad_gestion -------------------------------------------------
	//--------------------------------------------------------------------------
	
	function evt__form_unidad_gestion__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_unidades_gestion($datos);
		} catch ( guarani_error $e ) {
			$this->datos_ug_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function conf__form_unidad_gestion(toba_ei_formulario $form)
	{
		if (isset($this->datos_ug_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_ug_temp);		
		} else {
			$datos = $this->cn()->entidad()->get_datos_unidades_gestion();
			$form->set_datos($datos);
		}
		
		// Si el parámetro 'enc_utiliza_ug_kolla' es SI
		if (encuesta::kolla_utiliza_ug()) {

			// Si falla la conexión con Kolla
			if (!encuesta::conexion_kolla_ok()) {
				$desc = $form->ef('kolla_unidad_gestion')->get_descripcion();
				$desc .= ' Debe configurar el acceso al servicio web de SIU-Kolla para obtener las Unidades de Gestión disponibles.';
				$form->ef('kolla_unidad_gestion')->set_descripcion($desc);
			}
			
		} else { // Si es NO
			
			$form->desactivar_efs('kolla_unidad_gestion');
			
		}

		// Si el parámetro 'bec_utiliza_ug_tehuelche' es SI
		if (beca::tehuelche_utiliza_ug()) {

			// Si falla la conexión con Tehuelche
			if (!beca::conexion_tehuelche_ok()) {
				$desc = $form->ef('tehuelche_unidad_gestion')->get_descripcion();
				$desc .= ' Debe configurar el acceso al servicio web de SIU-Tehuelche para obtener las Unidades de Gestión disponibles.';
				$form->ef('tehuelche_unidad_gestion')->set_descripcion($desc);
			}
			
		} else { // Si es NO
			
			$form->desactivar_efs('tehuelche_unidad_gestion');
			
		}
		
	}
	
	
	//--------------------------------------------------------------------------
	//---- PANTALLA: dependencias
	//--------------------------------------------------------------------------
	
	//--------------------------------------------------------------------------
	//---- form_datos_ug -------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_datos_ug(toba_ei_formulario $form)
	{
		$form->set_datos($this->cn()->entidad()->get_datos_unidades_gestion());
	}

	//--------------------------------------------------------------------------
	//---- cuadro_dependencias -------------------------------------------------
	//--------------------------------------------------------------------------
	
	function conf__cuadro_dependencias(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->cn()->entidad()->get_datos_dependencias());
	}
	
	function evt__cuadro_dependencias__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_dependencia($seleccion);	
	}
	
	//--------------------------------------------------------------------------
	//---- filtro_dependencias -------------------------------------------------
	//--------------------------------------------------------------------------
	
	function conf__filtro_dependencias(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_dependencias)) {
			$filtro->set_datos($this->s__datos_dependencias);
		}		
	}
	
	function evt__filtro_dependencias__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_dependencias = $datos;
			$this->s__filtro_dependencias = $this->dep('filtro_dependencias')->get_sql_where();
		} else {
			unset($this->s__datos_dependencias);
			$this->s__filtro_dependencias = '';
		}
	}
	
	function evt__filtro_dependencias__cancelar()
	{
		unset($this->s__datos_dependencias);
		$this->s__filtro_dependencias = '';
	}	
	
	//--------------------------------------------------------------------------
	//---- cuadro_espacios -----------------------------------------------------
	//--------------------------------------------------------------------------
	
	function conf__cuadro_espacios(toba_ei_cuadro $cuadro)
	{
		// Se obtiene la lista de todos los espacios existentes, salvo aquellos que se encuentran asociados a la unidad de gestión.
		$datos_dependencias = $this->cn()->entidad()->get_datos_dependencias();
		$filtro = (!empty($datos_dependencias)) ? guarani_sql::armar_condicion_not_in($datos_dependencias, 'sga_espacios', 'espacio', 'espacio') : '';
		if (!empty($this->s__filtro_dependencias)) {
			$filtro .= ' AND '.$this->s__filtro_dependencias;
		}
		$datos_cuadro_espacios = toba::consulta_php('co_espacios')->get_listado($filtro);
		$cuadro->set_datos($datos_cuadro_espacios);
	}
	
	// El espacio se agrega al cuadro superior (de dependencias de la unidad de gestion), y se elimina del cuadro inferior (espacios disponibles).
	function evt__cuadro_espacios__agregar()
	{
		if (!empty($this->dependencias_seleccionadas)) {
			foreach ($this->dependencias_seleccionadas as $seleccion) {
				$this->cn()->entidad()->agregar_dependencia_unidad_gestion($seleccion);
			}
		}	
	}
	
	function evt__cuadro_espacios__seleccion($seleccion)
	{
		$this->dependencias_seleccionadas = $seleccion;
	}	
	
}
?>