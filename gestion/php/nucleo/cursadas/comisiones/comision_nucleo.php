<?php
/**
 * Constantes para Comisiones
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class comision_nucleo
{
	// Instancias posibles para asociar a la comision.
	const inst_cursar = 1;
	const inst_promocionar = 2;
	const inst_exam_regular = 3;
	const inst_exam_libre = 4;
	const inst_exam_revalida = 5;
	const inst_exam_complementario = 6;
	const inst_exam_equivalencias = 7;
	const inst_eval_comision = 9;
	const inst_equivalencia = 10;
	
	// Tipos de cátedras asociables a comisiones.
	const cat_tipo_cursado_examen = 'A';
	const cat_tipo_cursado = 'C';
	
	// Valores parámetros default para instancias.
	const porc_asist_cursar = 'asistencia_porcentaje_regulares';
	const porc_asist_promocion = 'asistencia_porcentaje_promociones';
	const registrar_asistencia = 'asistencia_registrar_asistencia';
	const asitencia_por_hs = 'asist_computa_inasis_por_horas';
	const asit_cant_inasist = 'asist_indicar_cantidad_inasistencias';
	
	// Parámetros que indica si se utilizan Subcomisiones.
	const usa_subcomisiones = 'cur_usa_subcomisiones';
	
	// Tipo de asignación de horarios y aulas para comisiones.
	const asignacion_tipo_comision = 1;

	// Nombres default Comisiones
	static protected $nombres_default = array();

	const max_comisiones_x_elemento = 108;
	
	// Valores de tipos de distribución de alumnos en comisiones.
	const dist_entre_com = 1;
	const dist_com_subcom = 2;
	const dist_com_subcom_otra_com = 3;
	const dist_entre_com_desc = 'Entre Comisiones';
	const dist_com_subcom_desc = 'Entre una Comisión y sus Subcomisiones';
	const dist_com_subcom_otra_com_desc = 'Entre una Comisión y Subcomisiones de otra Comisión';
	
	// Valores de campos autocompletar
	const autocompletar_campo_fecha = 'fecha';
	const autocompletar_campo_fecha_regular = 'fecha_regular';
	const autocompletar_campo_fecha_promocion = 'fecha_promocion';
	const autocompletar_campo_condicion = 'cond_regularidad';
	
	// Valores de campos autocompletar para evaluaciones
	const autocompletar_campo_nota = 'nota';
	const autocompletar_campo_nota_cursada = 'nota_cursada';
	const autocompletar_campo_nota_promocion = 'nota_promocion';
	const autocompletar_campo_resultado = 'resultado';
	const autocompletar_campo_resultado_cursada = 'resultado_cursada';
	const autocompletar_campo_resultado_promocion = 'resultado_promocion';
	const autocompletar_campo_corregido_por = 'corregido_por';
	const autocompletar_campo_observaciones = 'observaciones';
	const autocompletar_campo_observacion_cursada = 'observacion_cursada';
	const autocompletar_campo_observacion_promocion = 'observacion_promocion';

	const autocompletar_para_todos_pagina = 'todos_pagina';
	const autocompletar_para_sin_datos_pagina = 'sin_datos_pagina';
	const autocompletar_para_todos = 'todos';
	const autocompletar_para_sin_datos = 'sin_datos';
	
	// Parametros
	const cur_asentar_notas_actas_regulares = 'cur_asentar_notas_actas_regulares';
	const cur_asentar_notas_actas_promocion = 'cur_asentar_notas_actas_promocion';
	const cur_controlar_cupo = 'cur_controlar_cupo';
	const cur_controlar_cupo_por = 'cur_controlar_cupo_por';
	
	const cupo_por_comision = 'comision';
	const cupo_por_aula = 'aula';

	// Tipos de cupo.
	const cupo_tipo_comision = 'C';
	const cupo_tipo_comision_desc = 'Comisión';
	const cupo_tipo_propuesta = 'P';
	const cupo_tipo_propuesta_desc = 'Propuesta';
	
	//Valores para alumnos con/sin subcomision
	const alumno_sin_subcomision = 1;
	const alumno_con_subcomision = 2;
	const alumno_sin_subcomision_desc = 'No inscriptos en subcomisiones';
	const alumno_con_subcomision_desc = 'Inscriptos en subcomisiones de:';

	//Código de inscripción (máximo 10 caracteres)
	const CODIGO_INSCRIPCION_CANT_DIGITOS = 6;
	const CODIGO_INSCRIPCION_REGEXP = "^[a-zA-Z0-9]{".self::CODIGO_INSCRIPCION_CANT_DIGITOS."}$";

	// Código de operación para "Cargar Notas a Comisiones".
	const cod_op_cargar_notas_comisiones = '800SIUCUR013';

	static function set_nombres_default_comisiones()
	{
		$i = 0;
		$letras = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'Ñ', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

		/**
		 * Se crea un arreglo de nombres para comisiones, del estilo [A, B, C, .. Z, AA, AB, AC, .. AZ, BA, ..]
		 */
		while ($i < self::max_comisiones_x_elemento) {
			switch (true) {
				case $i < 27:
					self::$nombres_default[$i] = $letras[$i];
					break;
				case $i > 26 && $i < 54:
					self::$nombres_default[$i] = $letras[0] . $letras[$i - 27];
					break;
				case $i > 53 && $i < 81:
					self::$nombres_default[$i] = $letras[1] . $letras[$i - 54];
					break;
				default:
					self::$nombres_default[$i] = $letras[2] . $letras[$i - 81];
			}
			++$i;
		}

		/*
		 * En caso de querer nombres para las comisiones del tipo [Comisión 1, Comisión 2, ...]
		 * se debe usar el siguiente ciclo para la creación del arreglo nombres_default.
		 
		while ($i < self::max_comisiones_x_elemento) {
			self::$nombres_default[$i] = "Comisión $i";
			++$i;
		}

		 */
	}

	static function get_nombre_default_comision($nro_comision)
	{
		return self::$nombres_default[$nro_comision];
	}

}