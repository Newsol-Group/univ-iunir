<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class pant_resumen extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo "<hr />";
		echo '<p> Se ha actualizado satisfactoriamente el trámite de solicitud de certificación.';

		// -- Formularios a imprimir ---------------------------------------------------
		$formularios = $this->controlador()->cn()->entidad()->get_datos_formularios_a_imprimir();
		if (!empty($formularios)) {
			echo ' Se generaron los siguientes certificados para su impresión:';
		}
		echo '</p>';
		foreach ($formularios as $formulario) {
			if ($formulario['admite_reimpresion'] == comunes::no && !$this->controlador()->cn()->entidad()->es_formulario_agregado($formulario['formulario'])) {
				// Si el formulario no admite reimpresión y ya estaba asociado al trámite NO se genera el link...
				echo "El formulario \"{$formulario['nombre']}\" ya ha sido impreso una vez y no admite reimpresión.<br>";
			} else {
				// Obtengo los datos a pasar a la plantilla
	       		$datos_tramite = $this->controlador()->cn()->entidad()->get_datos_tramite(); 
				$cambio_estado = $this->controlador()->cn()->entidad()->get_datos_cambio_estado();
				$campos = toba::consulta_php('co_circuitos_tramite')->get_campos_por_circuito_estado_accion($datos_tramite['circuito'], $cambio_estado['estado_anterior'], $cambio_estado['accion']);
				
				$datos_plantilla = array();
				foreach ($campos as $campo) {
					$datos_campo = current(toba::consulta_php('co_campos')->get_datos_campo($campo['campo']));
					$datos_plantilla[] = array('nombre' => $campo['campo'], 'valor' => $datos_tramite[$campo['campo']], 'tipo_de_dato' => $datos_campo['tipo_de_dato']);
				}
				$datos_plantilla[] = array('nombre' => 'nro_solicitud', 'tipo_de_dato' => 'E', 'valor' => $datos_tramite['nro_solicitud']);
				/*agrego el parametro nombre_persona_doc_oficiales */
				$param_nombre_persona_doc_oficiales = persona::get_param_nombre_persona_doc_oficiales();		
				$datos_plantilla[] = array('nombre' => 'apellido_y_nombre', 'tipo_de_dato' => 'S', 'valor' => $param_nombre_persona_doc_oficiales);
				/*fin el parametro nombre_persona_doc_oficiales */
				$plantilla = $formulario['plantilla'];
				$plantilla = toba::consulta_php('co_constancias')->get_archivo_plantilla($plantilla);
				
				toba::memoria()->set_dato("$plantilla" . '__parametros', $datos_plantilla);
				$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '32000020', array('plantilla' => $plantilla), array('servicio' => 'vista_jasperreports'));
				echo "<a href='$vinculo'>{$formulario['nombre']}</a> (copias a imprimir: {$formulario['cant_copias']})<br>";
			
			}
		}

	}
}
?>