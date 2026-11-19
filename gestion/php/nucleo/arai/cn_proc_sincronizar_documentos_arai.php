<?php
/**
 *
 *
 * @package     Araí
 * @subpackage  Documentos
 */
class cn_proc_sincronizar_documentos_arai extends cn_procesos_bk
{

	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_proc_sincronizar_documentos_arai::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function etiquetas($concepto)
	{
		if (! isset($this->etiquetas[$concepto])) {
			$this->etiquetas[$concepto] = guarani_etiquetas::get_concepto($concepto);
		}
		return $this->etiquetas[$concepto];
	}



	function get_actas_sincronizar()
	{
		$actas = toba::consulta_php("co_actas")->get_actas_cerradas_falla_documentos();
		
		foreach ($actas as $clave => $acta) {
			$actas[$clave]['tipo_documento'] = "Acta";
			$actas[$clave]['detalle'] = "Nro. Acta: {$acta['nro_acta']} - Tipo de Acta: {$acta['origen_acta_texto']} - Actividad: {$acta['nombre_actividad']}.";
		}
		
		return $actas;
	}
	
	function get_tramites_equivalencias_sincronizar()
	{
		$equivalencias = toba::consulta_php("co_equivalencias")->get_equivalencias_cerradas_falla_documentos();
		
		foreach ($equivalencias as $clave => $equivalencia) {
			
			$rectifica_a = "";
			if (!empty($equivalencia['rectifica_a'])) {
				$rectifica_a = " - Rectifica a: {$equivalencia['rectifica_a']}";
			}
			
			$legajo = "";
			if (!empty($equivalencia['legajo'])) {
				$legajo = " - Legajo: {$equivalencia['legajo']}";
			}
			
			$equivalencias[$clave]['tipo_documento'] = "Trámite de equivalencia";
			$equivalencias[$clave]['detalle'] = "Nro. trámite: {$equivalencia['equivalencia_tramite']}{$rectifica_a} - Apellido y Nombres: {$equivalencia['alumno_nombre_completo']} - Identificación: {$equivalencia['alumno_identificacion']}{$legajo} - Propuesta: {$equivalencia['propuesta_alumno']} - Nº resolución: {$equivalencia['documento_numero']} - Tipo: {$equivalencia['origen_nombre']} - Institución: {$equivalencia['institucion_nombre']}.";
		}
		
		return $equivalencias;
	}

}