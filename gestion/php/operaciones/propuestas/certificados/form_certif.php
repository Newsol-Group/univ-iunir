<?php 
/**
 *
 *
 * @package 	Propuestas
 * @subpackage 	Certificados
 * 
 */
class form_certif extends guarani_pers_ei_formulario
{

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------

		var efs_titulo = new Array(	'titulo_nivel', 'fecha_resolucion_ministerio',	'duracion_en_meses', 'duracion_en_anios',
									'total_horas', 'fecha_resolucion_ministerio', 'nro_resolucion_ministerio',
									'acreditado_coneau', 'categorizacion_coneau', 'tiempo_trabajo_final');

		{$this->objeto_js}.evt__certificado_tipo__procesar = function(es_inicial)
		{
			for (var ef in efs_titulo) {
				this.ef(efs_titulo[ef]).ocultar(false);
			}
			if (this.ef('certificado_tipo').tiene_estado()) {
				var certificado_tipo = this.ef('certificado_tipo').get_estado();
				this.controlador.ajax('analizar_tipo', certificado_tipo, this, this.visualizar_campos_dinamicos);
			}
		}

		{$this->objeto_js}.visualizar_campos_dinamicos = function(datos)
		{
			if (datos['mostrar']) {
				for (var ef in efs_titulo) {
					this.ef(efs_titulo[ef]).mostrar(true, false);
				}
			}
		}
		";
	}
}
?>