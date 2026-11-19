<?php
class pant_detalle extends guarani_ei_pantalla
{
	function generar_layout()
	{		
		/*
		 * Voy a iterar los bloques y buscar sus preguntas.
		 */
		$this->controlador()->ev_info_encuesta()->generar_html();
		$encuesta = $this->controlador()->get_encuesta();		
		$bloques = $this->controlador()->get_bloques();				
		foreach ($bloques as $bloque) {
			echo "<div class='ei-barra-sup ei-filtro-barra-sup ei-barra-sup-sin-botonera'>{$bloque['nombre']}</div>";
			/*
			 * Traigo las preguntas por bloque
			 */
			$preguntas = $this->controlador()->get_preguntas($encuesta['kolla_id_encuesta'], $bloque['bloque'] , $encuesta['kolla_unidad_gestion']);			
			foreach($preguntas as $pregunta) {				
				echo "<fieldset><legend>{$pregunta['nombre']}</legend>";
				$obligatoria = $pregunta['obligatoria'] == comunes::si ? comunes::si_desc : comunes::no_desc;
				echo 'Pregunta Obligatoria: '.$obligatoria.'<br><br>';
				$respuestas = $this->controlador()->get_respuestas($encuesta['kolla_id_habilitacion'], $encuesta['formulario_externo'], $encuesta['kolla_id_encuesta'], $encuesta['elemento_externo'], $bloque['bloque'], $pregunta['pregunta'], $pregunta['pregunta_orden_bloque'], $encuesta['kolla_unidad_gestion']);				
				echo "<table style='border-collapse: collapse;' border='1' width='900px'>";
				$i = 0;
				//Cuenta la cantidad de respuestas por cada opcion de respuesta.
				$cantidad_rtas = $encuesta['cant_respondidas'];				
				foreach ($respuestas as $respuesta) {					
					if ($pregunta['es_libre'] == comunes::no) {
						if ($i == 0) {
							echo '<tr>
									<th>Respuesta</th>
									<th>Porcentaje</th> 
									<th>Seleccionada</th>
								  </tr>';
						}
						echo '<tr>';
							echo '<td>'.$respuesta['respuesta_valor'].'</td>';
							$porcentaje = 0;
							if ($cantidad_rtas == 0) {
								$porcentaje = 0;
							} else {
								$porcentaje = round((($respuesta['elegida_veces'] * 100) / $cantidad_rtas));
							}
							echo '<td>'.$porcentaje.'%</td>';
							echo '<td>'.$respuesta['elegida_veces'].'</td>';
						echo '</tr>';
						$i ++;
					} else {
						echo '<tr>';
							echo '<td>'.$respuesta['respuesta_valor'].'</td>';
						echo '</tr>';
					}
				}
				$i = 0;
				echo "</table>";
				echo "</fieldset>";
			}
		}
	}

}