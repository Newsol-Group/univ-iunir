<?php

class co_cursos
{


	public function get_listado_rest($where = '', $order = '', $limit = '')
	{
		$sql = "SELECT 	int_pv_plataformas.plataforma AS plataforma_id,
						int_pv_plataformas.nombre AS plataforma_nombre,
						int_pv_cursos.curso AS id_curso,
						int_pv_cursos.id_curso_externo,
						array_to_json(array(SELECT comision FROM int_pv_cursos_comisiones WHERE int_pv_cursos_comisiones.curso = int_pv_cursos.curso)) AS comisiones
				FROM int_pv_plataformas
				JOIN int_pv_cursos ON (int_pv_cursos.plataforma = int_pv_plataformas.plataforma)
				WHERE $where
				$order
				$limit";
		
		$rs = guarani::db()->consultar($sql);
		
		foreach ($rs as $key => $curso) {
			$rs[$key]['comisiones'] = json_decode($curso['comisiones']);
			$rs[$key]['plataforma_arr']['id'] = $curso['plataforma_id'];
			$rs[$key]['plataforma_arr']['nombre'] = $curso['plataforma_nombre'];
		}
		
		return $rs;
	}

	public function get_alumnos($id_curso)
	{
		
		$curso = quote($id_curso);
		$where = " AND int_pv_cursos.curso = $curso";

		$sql = "SELECT
							vw_personas.apellido,
				  			vw_personas.nombres,
							sga_insc_cursada.alumno,
							sga_alumnos.legajo,
							vw_personas.apellido_nombres,
							vw_personas.desc_tipo_documento		AS documento_tipo,
							vw_personas.nro_documento			AS documento_nro,
							sga_insc_cursada.estado				AS cod_estado_inscr,
							sga_inscripciones_estados.nombre	AS estado_inscr,
							sga_insc_cursada.fecha_inscripcion	AS fecha,
							f_instancias_insc_cursada(sga_insc_cursada.inscripcion) AS nombre_instancias
				FROM		int_pv_cursos
							JOIN int_pv_cursos_comisiones ON (int_pv_cursos.curso = int_pv_cursos_comisiones.curso)
							JOIN sga_insc_cursada ON (sga_insc_cursada.comision = int_pv_cursos_comisiones.comision)
							JOIN sga_inscripciones_estados ON (sga_insc_cursada.estado = sga_inscripciones_estados.estado)
							JOIN sga_alumnos ON (sga_insc_cursada.alumno = sga_alumnos.alumno)
				    		JOIN vw_personas ON (vw_personas.persona = sga_alumnos.persona)
				WHERE 1 = 1 
				$where
				ORDER BY	vw_personas.apellido,
				    		vw_personas.nombres
			";
		
		$alumnos = guarani::db()->consultar($sql);
		
		foreach ($alumnos as $c => $alumno){
			$alumnos[$c]['inscripcion_arr']['estado'] = $alumno['cod_estado_inscr'];
			$alumnos[$c]['inscripcion_arr']['descripcion'] = $alumno['estado_inscr'];

			unset($alumnos[$c]['cod_estado_inscr']);
			unset($alumnos[$c]['estado_inscr']);
		}
		
		return $alumnos;
	}
}