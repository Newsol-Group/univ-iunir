<?php
/**
 *
 *
 * @package		Unidades de Gestión
 * @subpackage  Espacios
 * 
 */
class pant_asignaciones_espacios extends guarani_pers_ei_pantalla 
{
    function generar_layout() 
	{
		if($this->existe_dependencia('formulario_asignadas')) {
			$this->dep('formulario_asignadas')->generar_html();
		} else {
			$this->dep('formulario_pendientes')->generar_html();
		}
		
		$datos = $this->controlador()->get_datos_visualizador();
		$hay_datos = (guarani_arreglos::count($datos) > 0) ? 1 : 0;
		
		echo "<div class='timetable'></div>";
	?>
	<script>
		$(function() {
			let hay_datos = <?php echo $hay_datos; ?>;
			
			if(hay_datos) {
				let dia = <?= isset($datos['dia']) ? $datos['dia'] : 0; ?>;
				let mes = <?= isset($datos['mes']) ? $datos['mes'] : 0; ?>;
				let anio = <?= isset($datos['anio']) ? $datos['anio'] : 0; ?>;
				let espacios = <?= isset($datos['espacios']) ? json_encode($datos['espacios']) : 0; ?>;
				let asignaciones = <?= isset($datos['asignaciones']) ? json_encode($datos['asignaciones']) : 0; ?>;
				
				let timetable = new Timetable();
				timetable.setScope(6,23);
				timetable.addLocations(espacios);

				let i;
				for (i = 0; i < asignaciones.length; i++) { 
					let clase_css = asignaciones[i]['tipo'];
					let titulo = asignaciones[i]['elemento_nombre'];
					titulo += "\n"+asignaciones[i]['duracion_detallada'];
					titulo += "\nTotal de alumnos inscriptos: "+asignaciones[i]['cantidad_insc']; 
					if(asignaciones[i]['cantidad_insc'] > asignaciones[i]['capacidad']) {
						titulo += "\nEl espacio no tiene suficiente capacidad.";
						clase_css += "-cupo";
					}
					titulo += "\n"+asignaciones[i]['estado'];
					
					timetable.addEvent(
						asignaciones[i]['elemento_nombre_abrev'], 
						asignaciones[i]['espacio'], 
						new Date(anio,mes,dia,asignaciones[i]['inicio_horas'],asignaciones[i]['inicio_minutos']), 
						new Date(anio,mes,dia,asignaciones[i]['fin_horas'],asignaciones[i]['fin_minutos']), 
						{ 
							class: clase_css, 
							titulo:titulo, 
							data: { 
								actividad: asignaciones[i]['data_elemento'] 
							}
						});
				}

				let renderer = new Timetable.Renderer(timetable);
				renderer.draw('.timetable');
			}
		});
	</script>
	<?php   
	}
}
?>