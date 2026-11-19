<?php
/**
 * 
 * 
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class cn_ent_copia_comisiones extends cn_procesos_bk
{
	function cargar_atributos_parametros($propuestas)
	{
		$operacion = guarani::get_id_operacion();
		
		// Se eliminan propuestas repetidas (por estar en más de un plan)
		$propuestas_plano = array();
		foreach ($propuestas as $propuesta) {
			if (!in_array($propuesta, $propuestas_plano)) {
				$propuestas_plano[] = $propuesta;
			}
		}
		$propuestas = $propuestas_plano;
		
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
	}
	
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_copia_comisiones_nucleo::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function get_comisiones($clave = null)
	{
		if (is_null($clave)) {
			return $this->tabla('comisiones')->get_filas();
		} else {
			return $this->tabla('comisiones')->get_fila($clave);
		}
	}

	function set_comisiones($datos)
	{
		$this->tabla('comisiones')->procesar_filas($datos);
	}

}
?>