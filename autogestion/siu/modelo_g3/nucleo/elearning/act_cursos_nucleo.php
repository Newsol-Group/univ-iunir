<?php

use SIUToba\rest\lib\rest_error;

class act_cursos_nucleo
{
	
	public function crear_curso($data)
	{
		$this->comprobar_plataforma($data['plataforma']);
		
		$sql = "INSERT INTO int_pv_cursos (id_curso_externo, nombre, plataforma) VALUES ({$data['id_curso_externo']}, 'No aplica', {$data['plataforma']})";
		$resultado = guarani::db()->ejecutar($sql);
		$curso = toba::db()->ultimo_insert_id('int_pv_cursos_seq');
		return [$resultado, $curso];
	}

	function modificar_curso($id_curso, $data)
	{
		if (isset($data['plataforma'])) {
			$this->comprobar_plataforma($data['plataforma']);	
		}
		
		$existente = $this->get_curso($id_curso);
		if (!$existente) {
			throw new rest_error(404, "El curso '$id_curso' no existe");
		}
		
		$existente = $existente[0];
		
		$id_curso = $existente['curso'];
		unset($existente['curso']);

		$sql = sql_array_a_update('int_pv_cursos', $data, array('curso' => $id_curso));
		
		return guarani::db()->ejecutar($sql) == 1;
	}

	public function eliminar_curso($id_curso)
	{
		$existente = $this->get_curso($id_curso);
		if (!$existente) {
			throw new rest_error(404, "El curso '$id_curso' no existe");
		}

		$id_curso = quote($existente[0]['curso']);
		guarani::db()->ejecutar("DELETE FROM int_pv_cursos_comisiones WHERE curso = $id_curso");
		return guarani::db()->ejecutar("DELETE FROM int_pv_cursos WHERE curso = $id_curso");
	}

	public function asociar_comision($id_curso, $id_comision)
	{
		$existente = $this->get_curso($id_curso, true);
		if (!$existente) {
			throw new rest_error(404, "El curso '$id_curso' no existe");
		}
		
		foreach ($existente as $fila) {
			if ($fila['comision'] == $id_comision) {
				return;
			} //ya existe
		}

		$this->comprobar_comision($id_comision);

		$curso = quote($existente[0]['curso']);
		$comision = quote($id_comision);
		$sql = "INSERT INTO int_pv_cursos_comisiones(curso, comision)
		   		VALUES ($curso, $comision);";

		guarani::db()->ejecutar($sql);
	}


	/**
	 * Retorna 0 si la comision no estaba asociada, 1 si estaba asociada.
	 */
	public function desasociar_comision($id_curso, $id_comision)
	{
		$existente = $this->get_curso($id_curso);
		if (!$existente) {
			throw new rest_error(404, "El curso '$id_curso' no existe");
		}

		$curso = quote($existente[0]['curso']);
		$comision = quote($id_comision);
		$sql = "DELETE FROM int_pv_cursos_comisiones
				WHERE curso = $curso AND comision = $comision";
		return guarani::db()->ejecutar($sql);
	}


	protected function get_curso($id_curso, $comision = false)
	{
		$id_curso = quote($id_curso);
		$select = '';
		$join = '';
		if ($comision) {
			$join = "LEFT JOIN int_pv_cursos_comisiones ON (int_pv_cursos.curso = int_pv_cursos_comisiones.curso)";
			$select = "int_pv_cursos_comisiones.comision,";
		}
		$sql = "SELECT $select
					 int_pv_cursos.curso, int_pv_cursos.id_curso_externo, int_pv_cursos.nombre, int_pv_cursos.plataforma
				FROM int_pv_cursos
				$join
				WHERE int_pv_cursos.curso = $id_curso";
		
		$existente = guarani::db()->consultar($sql);

		return $existente;
	}

	protected function comprobar_comision($comision, $activas = true)
	{
		$comision = quote($comision);
		$where = '';
		if ($activas) {
			$where  = " AND sga_periodos_lectivos.fecha_fin_dictado >= current_date";
		}

		$sql = "SELECT
					 sga_comisiones.comision
				FROM
					sga_comisiones
					INNER JOIN sga_periodos_lectivos ON (sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo)

				WHERE sga_comisiones.comision = $comision
					$where ";
		$existente = guarani::db()->consultar($sql);

		if (empty($existente)) {
			throw new rest_error(404, "La comisión $comision no existe o no está activa. No se puede asociar");
		}
		
		return $existente;
	}
	
	protected function comprobar_plataforma($plataforma, $activas = true)
	{
		$plataforma = quote($plataforma);
		$where = '';
		if ($activas) {
			$where  = " AND activa = TRUE";
		}

		$sql = "SELECT EXISTS(SELECT 1 FROM int_pv_plataformas WHERE plataforma = $plataforma $where) AS existe";
		$rs = guarani::db()->consultar_fila($sql);

		if (!$rs['existe']) {
			throw new rest_error(404, "La plataforma $plataforma no existe o no está activa.");
		}
		
		return true;
	}
	
}