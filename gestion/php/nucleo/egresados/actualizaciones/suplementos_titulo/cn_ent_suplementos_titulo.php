<?php
/**
 *
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class cn_ent_suplementos_titulo extends cn_entidad
{
	function get_datos_suplementos($tipo = null)
	{
		if(isset($tipo)) {
			$suplementos = $this->tabla('certificados_otorg_anexo')->get_filas(['tipo_suplemento' => $tipo]);
		} else {
			$suplementos = $this->tabla('certificados_otorg_anexo')->get_filas();
		}
		
        return rs_ordenar_por_columna($suplementos, 'orden');
	}
	
	function procesar_suplementos($datos)
	{
		$this->tabla('certificados_otorg_anexo')->procesar_filas($datos);
	}
}
?>