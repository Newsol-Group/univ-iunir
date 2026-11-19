<?php 
/**
 *
 *
 * @package 	Propuestas
 * @subpackage 	Certificados
 * 
 */
class ci_edi_certificado extends ci_edicion_simple
{
	function ini()
	{
		toba_ef_combo::set_maximo_descripcion(150);
	}
	
	function conf()
	{
		if (!$this->cn()->entidad_es_nueva()) {
			$this->dep('formulario')->set_solo_lectura(array('codigo', 'certificado_tipo'));
		} else {
			if ($this->dep('formulario')->existe_ef('estado')) {
				$this->dep('formulario')->desactivar_efs(array('estado'));
			}
		}
	}

	function ajax__analizar_tipo($certificado_tipo, toba_ajax_respuesta $respuesta)
	{
		$es_titulo = toba::consulta_php('co_certificados')->es_certificado_tipo_titulo($certificado_tipo);
		$resultado = array();
		$resultado['mostrar'] = $es_titulo;
		$respuesta->set($resultado);
	}
}
?>