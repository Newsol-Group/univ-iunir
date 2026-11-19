<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Constancias
 * 
 */
class cn_ent_constancia extends cn_entidad
{
	function cargar($id)
	{
		$this->tabla('autoridades')->persistidor()->activar_perfil_de_datos();
		parent::cargar($id);
	}

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos_constancia($datos);
		$this->tabla('constancia')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		$datos = $this->tabla('constancia')->get();
		if (isset($datos['permite_descargar']) && $datos['permite_descargar']) {
			$datos['permite_descargar'] = 1;
		} else {
			$datos['permite_descargar'] = 0;
		}
		return $datos;
	}

	//-------------------------------------------------------------------
	
	function get_datos_autoridades()
	{
		return $this->tabla('autoridades')->get_filas();
	}
 	
	function set_datos_autoridades($datos)
	{
		$this->validar_datos_autoridades($datos);
		$this->tabla('autoridades')->procesar_filas($datos);
	}
	
	//-------------------------------------------------------------------

	function get_datos_responsables_academicas()
	{
        return $this->tabla('responsables_academicas')->get_filas();
	}

	function set_datos_responsables_academicas($datos)
	{
		$this->tabla('responsables_academicas')->procesar_filas($datos);
	}

	//-------------------------------------------------------------------
	
	function validar_datos_constancia($datos)
	{
		$datos['constancia'] = $this->tabla('constancia')->get_columna('constancia');
		$this->validar_nombre_constancia($datos['nombre'], $datos['constancia']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Determinar si el nombre de la Ubicacion ya existe.
	function validar_nombre_constancia($nombre, $constancia) 
	{
		if (!toba::consulta_php('co_constancias')->validar_nombre_constancia($nombre, $constancia)) {
			$this->set_error('dato_existente', array('nombre', 'Constancia/Certificado'));
		}
	}	
	
	//---------------------------------------------------------------------
	
	function validar_datos_autoridades($datos)
	{
		/**
		 * Si se asociaron Autoridades a la Constancia, se verifica que exista el menos una y sólo una en
		 * calidad de Titular.
		 */
		$this->validar_calidad_autoridades($datos);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
 	  * Determina si las calidades de las Autoridades asociadas a las Constancias son correctas.
      *	@param array 	datos		matriz de arreglos asociativos conteniendo las Autoridades a asociar a la Constancia.
    */
	function validar_calidad_autoridades($datos) 
	{
		$hay_filas = false;
		$existe_titular = false;
		foreach ($datos as $fila => $autoridad) {
			if ($autoridad[apex_ei_analisis_fila] != 'B') {
				$hay_filas = true;
				// El campo en_calidad_de es obligatorio si la autoridad está asociada.
				if (!isset($autoridad['en_calidad_de'])) {
					$autoridad_nombre = $autoridad['autoridad_nombre_completo'];
					$this->set_error('dato_faltante', array('Tipo de Firma para la Autoridad ' . $autoridad_nombre));
				} else {
					// Si la Autoridad está asociada a la Constancia, se verifica la calidad con la que se asoció.
					if ($autoridad['en_calidad_de'] == constancias::autoridad_titular) {
						if ($existe_titular) {
							$this->set_error('800CON_un_titular');
							break;
						} else {
							$existe_titular = true;
						}
					} 
				}
			}
		}
		// Si hay Autoridades asociadas, debe existir al menos un titular.
		if ($hay_filas && !$existe_titular) {
			$this->set_error('800CON_un_titular');			
		}
	}	
}