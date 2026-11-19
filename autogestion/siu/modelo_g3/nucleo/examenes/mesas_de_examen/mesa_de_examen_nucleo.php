<?php
/**
 * Constantes para Mesas de Examen
 *
 * @package		Exámenes
 * @subpackage	Mesas
 * 
 */
class mesa_de_examen_nucleo
{
	// Tipos aplicables a mesas de exámen (evaluaciones, instancias).
	const aplica_mesa = 'M';
	
	// Tipos de cátedras asociables a mesas de exámen.
	const cat_tipo_cursado_examen = 'A';
	const cat_tipo_examen = 'E';
	
	// Valores de campos autocompletar
	const autocompletar_campo_nota = 'nota';
	const autocompletar_campo_obs = 'observaciones';
	const autocompletar_campo_fecha = 'fecha';
	const autocompletar_result_ausente = 'ausente';
	
	const autocompletar_para_todos = 'todos';
	const autocompletar_para_sin_datos = 'sin_datos';
	
	// Criterios de copiado masivo de mesas
	const dia_semana = 1;
	const orden_dia = 2;
	const dia_x_catedra = 3;
	const dia_x_actividad = 4;

	// Opciones dia de mesa no hábil
	const crear_la_mesa_ese_dia = 0;
	const no_crear_la_mesa = 1;
	const crear_la_mesa_el_proximo_habil = 2;
	
	// Instancias posibles para asociar a la mesa.
	const inst_exam_regular = 3;
	const inst_exam_libre = 4;
	const inst_exam_revalida_cursada = 5;
	const inst_exam_revalida_examen = 6;
	const inst_exam_complementario = 7;
	const inst_exam_equivalencia = 8;
	
	// Tipos de cambios masivos a mesas
	const cambio_suspension = 'S';
	const cambio_fecha = 'F';
	const cambio_horario = 'H';
	const cambio_suspension_desc = 'Suspensión';
	const cambio_fecha_desc = 'Cambio de Fecha';
	const cambio_horario_desc = 'Cambio de Horario';
	
	// Límite de caracteres para los textos descriptivos de selecciones en los wizards de las operaciones de exámenes.
	const limite_texto_descr = 100;
	
	// Constantes para los arreglos y encabezados de reportes para impresión de Mesas de Examen (utilizados en Copia y Generación)
	const ind_actividades_mesas = 'actividades_mesas';
	const ind_mesas_llamados = 'mesas_llamados';
	const ind_mesas = 'mesas';
	const ind_encab_turno = 'turno_examen';
	const ind_encab_anio = 'anio_academico';
	const ind_encab_instancias = 'instancias';
	
	const mostrar_observaciones = 'mostrar_obs';
	const mostrar_cant = 'mostrar_cant_mesas';

	// Nombres default Mesas
	static protected $nombres_default = array();

	const max_mesas_x_elemento = 54;
	
	// Código de operación para "Modificar Mesas de Examen".
	const cod_op_modificar_mesas = '800SIUEXA029';

	// Estados de la mesas de examén
	const mesa_estado_suspendido = 'B';
	const mesa_estado_activo = 'A';

	// Constantes para generación masiva
	// Mesas por cátedra
	const no_considerar_catedras = 1;
	const no_considerar_catedras_desc = 'No se consideran cátedras';
	const una_mesa_por_catedra = 2;
	const una_mesa_por_catedra_desc = 'Crear una mesa por cada cátedra';
	const una_mesa_por_catedra_si_existe = 3;
	const una_mesa_por_catedra_si_existe_desc = 'Crear una mesa por cada cátedra. Si la actividad no tiene cátedra, se genera una mesa sin cátedra';
	// Criterios de generación
	const no_generar_mesa_si_existe = 'N';
	const no_generar_mesa_si_existe_desc = 'No generar mesa si ya se encuentra creada (según selección)';
	const generar_mesa_siempre = 'S';
	const generar_mesa_siempre_desc = 'Generar mesa siempre (aún si ya se encuentra creada)';
	// Fecha y hora de la mesa
	const fecha_inicio_llamado = 'D';
	const fecha_inicio_llamado_desc = 'Fecha de inicio del llamado';
	const fecha_definida_actividad = 'A';
	const fecha_definida_actividad_desc = 'Definida en la actividad (default)';
	const fecha_definida_catedra = 'C';
	const fecha_definida_catedra_desc = 'Definida en la cátedra';
	// Docentes
	const docentes_no_asignar = 'N';
	const docentes_no_asignar_desc = 'No asignar';
	const docentes_tribunal = 'T';
	const docentes_tribunal_desc = 'Utilizar tribunal de la actividad';
	const docentes_catedra = 'C';
	const docentes_catedra_desc = 'Utilizar docentes de la cátedra';

	static function set_nombres_default_mesas()
	{
		$i = 0;
		$letras = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'Ñ', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

		// Se crea un arreglo de nombres para mesas de examen, del estilo [A, B, C, .. Z, AA, AB, AC, .. AZ]
		while ($i < self::max_mesas_x_elemento) {
			switch (true) {
				case $i < 27:
					self::$nombres_default[$i] = $letras[$i];
					break;
				default:
					self::$nombres_default[$i] = $letras[0] . $letras[$i - 27];
			}
			++$i;
		}

		/**
		 * En caso de querer nombres para las mesas del tipo [Mesa 1, Mesa 2, ...]
		 * se debe usar el siguiente ciclo para la creación del arreglo nombres_default.
		
		  while ($i < self::max_mesas_x_elemento) {
			self::$nombres_default[$i] = "Mesa $i";
			$i++;
		  }

		 */
	}

	static function get_nombre_default_mesa($nro_mesa)
	{
		return self::$nombres_default[$nro_mesa];
	}

}
?>