<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Ubicaciones
 */
class cn_ent_edificacion extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		if (isset($datos['localidad'])) {
			if (stripos($datos['localidad'],':')) {
				$claves = $datos['localidad'];
				$datos['codigo_postal'] = guarani_texto::extraer_subclave($claves,1);
				$datos['localidad'] = guarani_texto::extraer_subclave($claves);				
			}
		} else {
			/**
			 * Al ef localidad se le reseteó el valor, por lo cual el código postal también debe resetearse.
			 * Es necesario resetear el valor en el servidor, ya que aunque se haya reseteado en el cliente
			 * (en la extensión del form), al ser un ef fijo el cambio no es tomado en cuenta.
			 */
			$datos['codigo_postal'] = '';
		}
		$this->validar_datos($datos);
		$this->tabla('edificaciones')->set($datos);
	}

	function get_datos($tabla=null)
	{
		return $this->tabla('edificaciones')->get();
	}

	//-------------------------------------------------------------------

	function validar_datos($datos)
	{
		$datos['edificacion'] = $this->tabla('edificaciones')->get_columna('edificacion');
		$this->validar_nombre_edificacion($datos['nombre'], $datos['edificacion'], $datos['localidad']);
		
		if($this->hay_errores()){
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Determinar si el nombre de la Edificacion ya existe en la localidad.
	function validar_nombre_edificacion($nombre, $edif, $localidad)
	{
		if (!toba::consulta_php('co_edificaciones')->validar_nombre_edificacion($nombre, $edif, $localidad)) {
			$this->set_error('dato_existente',array('nombre', 'Edificación'));
		}
	}
}

?>