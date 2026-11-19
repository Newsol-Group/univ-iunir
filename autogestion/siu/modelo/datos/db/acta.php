<?php
namespace siu\modelo\datos\db;
use SIU\Chulupi\kernel;

class acta 
{
	
	/**
	 * parametros: id_acta
	 * cache: no
	 * 
	 */
	function get_datos_acta($parametros)
	{
		// Recupera datos del Acta de Examen.
		// actividad, año academico,  ubicacion, fecha examen,  hinicio, hfin, observaciones
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		$sql = "SELECT	vw_mesas_examen.mesa_examen_elemento as actividad,
						vw_mesas_examen.mesa_examen_ubicacion as ubicacion,
						vw_mesas_examen.anio_academico as anio_academico,
						to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as fecha,
						sga_actas.nro_acta, 
						sga_actas.evaluacion,	
						sga_actas.llamado_mesa, 
						sga_actas.origen, 
						sga_actas.tipo_acta, 
						sga_actas.observaciones, 
						sga_actas.estado,
						sga_actas.renglones_folio,
						vw_mesas_examen.mesa_examen_en_turno as mesa_en_turno_examen,
						to_char(cast(vw_mesas_examen.mesa_examen_hora_inicio as time), $formato_hora_visual) as hora_inicio,
						to_char(cast(vw_mesas_examen.mesa_examen_hora_finalizacion as time), $formato_hora_visual) as hora_finalizacion
						
				FROM	sga_actas
				JOIN	sga_llamados_mesa   ON (sga_llamados_mesa.llamado_mesa = sga_actas.llamado_mesa)
				JOIN	vw_mesas_examen     ON (vw_mesas_examen.mesa_examen  = sga_llamados_mesa.mesa_examen)
				WHERE	sga_actas.id_acta = {$parametros['id_acta']}
				";
		
				
		return kernel::db()->consultar_fila($sql);
		
	}
	
	/**
	 * parametros: id_acta
	 * cache: no
	 * 
	 */
	function get_datos_acta_instancias($parametros)
	{
		$sql = "SELECT	sga_actas_instancias.id_acta,
						sga_actas_instancias.instancia,
						sga_actas_instancias.escala_nota
				FROM sga_actas_instancias
				WHERE id_acta = {$parametros['id_acta']}";
		
		return kernel::db()->consultar($sql);
	}
	
	/**
	 * parametros: id_acta
	 * cache: no
	 * 
	 */
	function get_datos_acta_alumnos($parametros)
	{
		$sql = "SELECT  COALESCE(sga_alumnos.legajo, '-') as legajo,
						sga_alumnos.alumno as alumno,
						vw_personas.persona as persona,
						vw_personas.apellido_nombres as apellido_nombre,
						sga_propuestas.nombre_abreviado as propuesta,
						vw_personas.tipo_nro_documento as identificacion,
						sga_instancias.instancia,
						sga_instancias.nombre as instancia_nombre
				FROM	sga_eval_detalle_examenes
				JOIN	sga_alumnos ON (sga_alumnos.alumno = sga_eval_detalle_examenes.alumno)
				JOIN	vw_personas ON (vw_personas.persona = sga_alumnos.persona)
				JOIN	sga_propuestas ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
				JOIN	sga_instancias ON (sga_instancias.instancia = sga_eval_detalle_examenes.instancia)
				WHERE	sga_eval_detalle_examenes.id_acta = {$parametros['id_acta']}";
		
		return kernel::db()->consultar($sql);
		
	}
	
	/**
	 * parametros: id_acta
	 * cache: no
	 * 
	 */
	function get_datos_acta_docentes($parametros)
	{
		// Datos de los docentes asignados a la mesa del acta de examen.
		$sql = "SELECT	sga_docentes.docente,
						vw_personas.apellido_nombres as apellido_nombre,
						sga_docentes.legajo,
						sga_docentes_mesa_llamado.rol
				FROM sga_actas 
				JOIN sga_llamados_mesa ON (sga_llamados_mesa.llamado_mesa = sga_actas.llamado_mesa)
				JOIN sga_docentes_mesa_llamado ON (sga_docentes_mesa_llamado.llamado_mesa = sga_llamados_mesa.llamado_mesa)
				JOIN sga_docentes ON (sga_docentes.docente = sga_docentes_mesa_llamado.docente)
				JOIN vw_personas ON (vw_personas.persona = sga_docentes.persona)
				WHERE sga_actas.id_acta = {$parametros['id_acta']}";
		
		return kernel::db()->consultar($sql);
	}
	

	/**
	 * parametros: term, actividad
	 * param_null: term, actividad
	 * cache: no
	 * 
	 */
	public function get_actividades_actas_fuera_calendario($parametros)
	{
		//@TO-DO Usar constantes
		$where = '';
		if (!empty($parametros['term'])) {
			//Elimino los quotes
			$term_sin_quotes = substr($parametros['term'], 1, -1);
			//Agrego los % %
			$term = kernel::db()->quote_like($term_sin_quotes, '%', '%');
			$where = "AND (f_limpiar_acentos(vw_actividades_plan.nombre) ILIKE $term OR
					      f_limpiar_acentos(vw_actividades_plan.codigo)	ILIKE $term)";
		} else {
			$where = "AND sga_elementos.elemento = {$parametros['actividad']}";
		}
		
		$sql = "SELECT DISTINCT sga_elementos.elemento, 
								sga_elementos.codigo,
								sga_elementos.nombre,
								sga_elementos_atrib.escala_nota_examen

				FROM	vw_actividades_plan
						JOIN sga_planes_versiones	ON (sga_planes_versiones.plan_version = vw_actividades_plan.plan_version)
						JOIN sga_elementos_plan		ON (sga_elementos_plan.elemento_plan = vw_actividades_plan.elemento_plan)
						JOIN sga_elementos			ON (sga_elementos.elemento = vw_actividades_plan.elemento)
						JOIN sga_elementos_atrib	ON (sga_elementos_atrib.elemento  = sga_elementos.elemento)
				
				WHERE	sga_elementos.estado				= 'A' -- Actividades Activas
				AND		sga_elementos.disponible_para		IN ('M', 'T')  -- Disponible para mesas de examen
				AND		sga_elementos_plan.requiere_examen	= 'S' -- actividades que se rinden examen				
				AND		sga_planes_versiones.estado			IN ('A','V')  -- versiones de plan activos
				$where
				ORDER BY 3;
				";
		
		if (!empty($parametros['term'])) {
			return kernel::db()->consultar($sql);
		}
		
		return kernel::db()->consultar_fila($sql);
		
	}
	
	
	/**
	 * parametros: instancia
	 * param_null: instancia
	 * cache: memoria
	 * 
	 */
	public function get_instancias_examen($parametros) 
	{		
		$where = '';
		if (!empty($parametros['instancia'])) {
			$where = " AND sga_instancias.instancia = {$parametros['instancia']} ";
		}
		
		
		$sql = "SELECT		sga_instancias.instancia,
							sga_instancias.nombre as instancia_nombre,
							sga_instancias.orden as instancia_orden
				FROM		sga_instancias
				WHERE		sga_instancias.aplica_a = '" . \mesa_de_examen::aplica_mesa . "'
				$where
				ORDER BY	sga_instancias.orden";
		
		if (!empty($parametros['instancia'])) {
			return kernel::db()->consultar_fila($sql);
		} else {
			return kernel::db()->consultar($sql);
		}
		
	}
	/**
	 * parametros: llamado_mesa
	 * cache: memoria
	 * 
	 */
	public function get_evaluacion($parametros)
	{
		// Recupera la evaluacion de generacion automatica de la mesa de examen en el llamado.
		// Evaluacion que luego se asociará al acta de examen.
		$sql = "SELECT	sga_evaluaciones.evaluacion
				  FROM 	sga_llamados_mesa
				  JOIN 	sga_evaluaciones ON (sga_evaluaciones.entidad = sga_llamados_mesa.entidad)
				  JOIN 	sga_evaluaciones_tipos ON (sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo)
				 WHERE 	sga_llamados_mesa.llamado_mesa = {$parametros['llamado_mesa']}
				   AND	sga_evaluaciones_tipos.aplica_a   = 'M'
				   AND	sga_evaluaciones_tipos.automatica = 'S'
				";
		
		$evaluacion = kernel::db()->consultar_fila($sql);
		return $evaluacion['evaluacion'];
	}
	
	/**
	 * parametros: llamado_mesa
	 * cache: no
	 * 
	 */
	
	public function get_actas_llamado_mesa($parametros)
	{
		$sql = "SELECT 	sga_actas.id_acta as id_acta, 
						sga_actas.nro_acta as nro_acta,
						sga_actas.observaciones as observaciones,
						sga_instancias.instancia as instancia,
						sga_instancias.nombre as instancia_nombre,
						sga_mesas_examen.mesa_en_turno_examen as mesa_en_turno_examen,
						sga_mesas_examen.mesa_examen as mesa_examen,
						sga_llamados_mesa.llamado_mesa as llamado_mesa
				FROM	sga_llamados_mesa
				JOIN	sga_mesas_examen 		ON (sga_mesas_examen.mesa_examen    = sga_llamados_mesa.mesa_examen)
				JOIN    sga_actas               ON (sga_actas.llamado_mesa          = sga_llamados_mesa.llamado_mesa)
				JOIN	sga_actas_instancias 	ON (sga_actas_instancias.id_acta 	= sga_actas.id_acta)
				JOIN	sga_instancias			ON (sga_instancias.instancia 		= sga_actas_instancias.instancia)
				WHERE	sga_llamados_mesa.llamado_mesa = {$parametros['llamado_mesa']}";
		
				
		$info_actas = kernel::db()->consultar($sql);
		
		$resultado = array();
		foreach ($info_actas as $acta){
			$resultado[$acta['id_acta']]['id_acta'] = $acta['id_acta'];
			$resultado[$acta['id_acta']]['nro_acta'] = $acta['nro_acta'];
			$resultado[$acta['id_acta']]['mesa_examen'] = $acta['mesa_examen'];
			$resultado[$acta['id_acta']]['llamado_mesa'] = $acta['llamado_mesa'];
			$resultado[$acta['id_acta']]['mesa_en_turno_examen'] = $acta['mesa_en_turno_examen'];
			$resultado[$acta['id_acta']]['instancias'][$acta['instancia']]['instancia']			= $acta['instancia'];
			$resultado[$acta['id_acta']]['instancias'][$acta['instancia']]['instancia_nombre']	= $acta['instancia_nombre'];
		}
		
		return $resultado;
	}
	
	/**
	 * parametros: id_acta
	 * cache: memoria
	 * 
	 */
	
	public function info_link_acta($parametros)
	{
		$sql = "SELECT 	sga_actas.id_acta as id_acta, 
						sga_actas.nro_acta as nro_acta,
						sga_llamados_mesa.fecha as fecha,
						sga_mesas_examen.elemento as actividad,
						sga_mesas_examen.mesa_examen as mesa_examen,
						sga_actas.observaciones as observaciones,
						sga_instancias.instancia as instancia,
						sga_instancias.nombre as instancia_nombre,
						sga_mesas_examen.mesa_en_turno_examen as mesa_en_turno_examen,
						sga_llamados_mesa.llamado_mesa as llamado_mesa,
						sga_evaluaciones.evaluacion as evaluacion
				FROM	sga_llamados_mesa
				JOIN	sga_mesas_examen 		ON (sga_mesas_examen.mesa_examen	= sga_llamados_mesa.mesa_examen)
				JOIN	sga_evaluaciones		ON (sga_evaluaciones.entidad 		= sga_llamados_mesa.entidad)
				JOIN	sga_actas				ON (sga_actas.llamado_mesa			= sga_llamados_mesa.llamado_mesa)
				JOIN	sga_actas_instancias 	ON (sga_actas_instancias.id_acta 	= sga_actas.id_acta)
				JOIN	sga_instancias			ON (sga_instancias.instancia 		= sga_actas_instancias.instancia)
				WHERE	sga_actas.id_acta = {$parametros['id_acta']}";
		
				
		$info_actas = kernel::db()->consultar($sql);
		
		return $info_actas;
	}
	
	/**
	 * parametros: id_acta, instancia
	 * cache: no
	 * 
	 */
	public function cantidad_alumnos_instancia($parametros)
	{
		$sql = "SELECT count(*) 
				FROM sga_eval_detalle_examenes
				WHERE id_acta = {$parametros['id_acta']}
				AND instancia = {$parametros['instancia']}";
		
		$resultado = kernel::db()->consultar_fila($sql);
		return $resultado;
	}
	
	
	
	
	/**
	 * parametros: nro_acta, evaluacion, llamado_mesa, fecha, instancias, renglones_folio, observaciones_acta, digital
	 * param_null: observaciones_acta
	 * 	 
	 * cache: no
	 */
	public function insertar_acta($parametros)
	{
		if (empty($parametros['observaciones_acta']) ) $parametros['observaciones_acta'] = 'null';

		$sql = "SELECT * FROM f_acta_examen_alta (
						{$parametros['nro_acta']},
						{$parametros['evaluacion']},
						{$parametros['llamado_mesa']},
						{$parametros['fecha']},
						{$parametros['instancias']},
						{$parametros['renglones_folio']},						
						{$parametros['observaciones_acta']},
						{$parametros['digital']});";

		$resultado = kernel::db()->consultar_fila($sql);
		return $resultado;
	}
	

	
	/**
	 * parametros: nro_acta, evaluacion, llamado_mesa, origen, tipo_acta, observaciones, estado, renglones_folio
	 * param_null: observaciones
	 * 
	 * 
	 * cache: no
	 */
	public function insertar_acta_($parametros)
	{
		if (empty($parametros['observaciones']) || $parametros['observaciones'] == kernel::db()->quote('')) {
			$parametros['observaciones'] = 'NULL';
		}
		
		$sql = "INSERT INTO sga_actas (	nro_acta, 
										evaluacion,	
										llamado_mesa, 
										origen, 
										tipo_acta, 
										observaciones, 
										estado,
										renglones_folio)
				VALUES ({$parametros['nro_acta']}, 
						{$parametros['evaluacion']},
						{$parametros['llamado_mesa']},
						{$parametros['origen']},
						{$parametros['tipo_acta']},
						{$parametros['observaciones']},
						{$parametros['estado']},
						{$parametros['renglones_folio']});";
		
		$resultado = kernel::db()->ejecutar($sql);
		if ($resultado > 0) {
			$sql = "SELECT currval('sga_actas_seq') as secuencia";
			$secuencia = kernel::db()->consultar_fila($sql);
			return $secuencia['secuencia'];
		} 
		return $resultado;
	}
	
	/**
	 * parametros: acta, instancia, escala_nota
	 * cache: no
	 * 
	 */
	public function insertar_instancia_acta($parametros)
	{
		$sql = "INSERT INTO sga_mesas_examen_instancias (mesa_examen, instancia, escala_nota)
				VALUES ({$parametros['mesa_examen']}, 
						{$parametros['instancia']}, 
						{$parametros['escala_nota']}
						)";
						
		$resultado = kernel::db()->ejecutar($sql);
		return 	$resultado;			
	}
	
	/**
	 * parametros: acta, mesa_examen
	 * no_quote: acta
	 * cache: no
	 * 
	 */
	public function insertar_instancias_acta($parametros)
	{
		$sql = "INSERT INTO sga_actas_instancias (id_acta, instancia, escala_nota)
				SELECT	{$parametros['acta']}, instancia, escala_nota
				FROM sga_mesas_examen_instancias
				WHERE mesa_examen = {$parametros['mesa_examen']}";
						
		$resultado = kernel::db()->ejecutar($sql);
		return 	$resultado;			
	}
	
	/**
	 * parametros: llamado_mesa, docentes
	 * no_quote: docentes
	 * cache: no
	 * 
	 * docentes = array (docente, rol)
	 */
	public function insertar_docentes($parametros)
	{
		$sql = '';
		foreach ($parametros['docentes'] as $docente) {
			$sql .= "INSERT INTO sga_docentes_mesa_llamado (llamado_mesa, docente, rol) 
					 VALUES ({$parametros['llamado_mesa']}, 
							 {$docente['docente']},
							 {$docente['rol']});
					";
		}
		$resultado = kernel::db()->ejecutar($sql);
		return 	$resultado;		

	}
	
	/**
	 * parametros: mesa_examen, instancias
	 * no_quote: instancias
	 * cache: no
	 * 
	 * docentes = array (instancia, escala_nota)
	 */
	public function modificar_instancias($parametros)
	{
		$sql = '';
		foreach ($parametros['instancias'] as $instancia) {
			$sql .= "	UPDATE sga_mesas_examen_instancias 
						SET escala_nota = {$instancia['escala_nota']}
						WHERE mesa_examen = {$parametros['mesa_examen']}
						AND instancia	= {$instancia['instancia']};
					";
						
			$sql .= "	UPDATE sga_actas_instancias 
						SET escala_nota = {$instancia['escala_nota']}
						WHERE id_acta = {$parametros['id_acta']}
						AND instancia	= {$instancia['instancia']};
					";
						
		}
		
		$resultado = kernel::db()->ejecutar($sql);
		return 	$resultado;			

	}
	
	/**
	 * parametros: mesa_examen, id_acta, instancias
	 * no_quote: instancias
	 * cache: no
	 * 
	 * instancias = array (instancia, escala_nota)
	 */
	public function eliminar_instancias($parametros)
	{
		$sql = '';
		foreach ($parametros['instancias'] as $instancia) {
			$sql .= "DELETE FROM sga_mesas_examen_instancias
					 WHERE instancia		= {$instancia['instancia']}
					 AND   mesa_examen		= {$parametros['mesa_examen']};
					";
					 
			$sql .= "DELETE FROM sga_actas_instancias
					 WHERE instancia		= {$instancia['instancia']}
					 AND   id_acta		= {$parametros['id_acta']};
					";
		}
				
		$resultado = kernel::db()->ejecutar($sql);
		return 	$resultado;			

	}
	
	/**
	 * parametros: mesa_examen, id_acta, instancias
	 * no_quote: instancias
	 * cache: no
	 * 
	 * instancias = array (instancia, escala_nota)
	 */
	public function insertar_instancias($parametros)
	{
		
		$sql = '';
		foreach ($parametros['instancias'] as $instancia) {
			$sql .= "	INSERT INTO sga_mesas_examen_instancias (mesa_examen, instancia, escala_nota)
						VALUES ({$parametros['mesa_examen']},
								{$instancia['instancia']},
								{$instancia['escala_nota']}
								);
					";
						
			$sql .= "	INSERT INTO sga_actas_instancias (id_acta, instancia, escala_nota)
						VALUES ({$parametros['id_acta']},
								{$instancia['instancia']},
								{$instancia['escala_nota']}
								);
					";
						
		}
		
		$resultado = kernel::db()->ejecutar($sql);
		return 	$resultado;			

	}
	
	/**
	 * parametros: llamado_mesa, docentes
	 * no_quote: docentes
	 * cache: no
	 * 
	 * docentes = array (docente, rol)
	 */
	public function modificar_docentes($parametros)
	{
		$sql = '';
		foreach ($parametros['docentes'] as $docente) {
			$sql .= "UPDATE sga_docentes_mesa_llamado
					SET   rol = {$docente['rol']}
					WHERE docente		= {$docente['docente']}
					AND   llamado_mesa	= {$parametros['llamado_mesa']};
					";
		}
				
		$resultado = kernel::db()->ejecutar($sql);
		return 	$resultado;			

	}
	
	/**
	 * parametros: llamado_mesa, docentes
	 * no_quote: docentes
	 * cache: no
	 * 
	 * docentes = array (docente, rol)
	 */
	public function eliminar_docentes($parametros)
	{
		$sql = '';
		foreach ($parametros['docentes'] as $docente) {
			$sql .= "DELETE FROM sga_docentes_mesa_llamado
					 WHERE docente		= {$docente['docente']}
					 AND   llamado_mesa	= {$parametros['llamado_mesa']};
					";
		}
				
		$resultado = kernel::db()->ejecutar($sql);
		return 	$resultado;			

	}
	
	
	/**
	 * parametros: evaluacion, id_acta, alumnos, fecha
	 * no_quote: alumnos
	 * cache: no
	 * 
	 * alumnos = array (alumno, instancia, escala_nota, plan_version)
	 */
	public function insertar_eval_detalle_examenes($parametros)
	{
		$no = kernel::db()->quote(\comunes::no);
		$sql = '';
		foreach ($parametros['alumnos'] as $alumno) {
			$sql .= "INSERT INTO sga_eval_detalle_examenes (evaluacion, id_acta, alumno, instancia, escala_nota, plan_version, fecha, inscripto)
					 VALUES ({$parametros['evaluacion']},
							{$parametros['id_acta']},
							{$alumno['alumno']},
							{$alumno['instancia']},
							{$alumno['escala_nota']},
							{$alumno['plan_version']},
							{$parametros['fecha']},
							$no
							);
					";
		}
		$resultado = kernel::db()->ejecutar($sql);
		return 	$resultado;		

	}
	
	/**
	 * parametros: evaluacion, id_acta, alumnos
	 * no_quote: alumnos
	 * cache: no
	 * 
	 * alumnos = array (alumno, instancia, escala_nota)
	 */
	public function eliminar_eval_detalle_examenes($parametros)
	{
		
		$alumnos= implode(',',aplanar_matriz($parametros['alumnos'], 'alumno'));
		
		$sql = "DELETE FROM sga_eval_detalle_examenes
				WHERE evaluacion = {$parametros['evaluacion']}
				AND	  id_acta = {$parametros['id_acta']}
				AND   alumno in ($alumnos)
				";
				
		$resultado = kernel::db()->ejecutar($sql);
		return 	$resultado;		

	}
	
	/**
	 * parametros: evaluacion, id_acta, alumnos
	 * no_quote: alumnos
	 * cache: no
	 * 
	 * alumnos = array (alumno, instancia, escala_nota)
	 */
	public function modificar_eval_detalle_examenes($parametros)
	{
		$sql = '';
		foreach ($parametros['alumnos'] as $alumno) {
		
			$sql .= "UPDATE sga_eval_detalle_examenes
					SET   instancia = {$alumno['instancia']}, escala_nota = {$alumno['escala_nota']}
					WHERE evaluacion	= {$parametros['evaluacion']}
					AND   id_acta		= {$parametros['id_acta']}
					AND   alumno		= {$alumno['alumno']};
					";
		}
				
		$resultado = kernel::db()->ejecutar($sql);
		return 	$resultado;		

	}
	
	/**
	 * parametros: mesa_examen, id_acta, observaciones, mesa_examen
	 * cache: no
	 * 
	 */
	public function modificar_encabezado_acta_en_turno($parametros)
	{
		
		$sql = "UPDATE sga_mesas_examen
				SET observaciones = {$parametros['observaciones']}
				WHERE mesa_examen = {$parametros['mesa_examen']};
				
				UPDATE sga_actas
				SET    observaciones = {$parametros['observaciones']}
				WHERE  id_acta = {$parametros['id_acta']}
			   ";
			
		$resultado = kernel::db()->ejecutar($sql);
		return 	$resultado;		

	}
	
	/**
	 * parametros: mesa_examen, llamado_mesa, id_acta, ubicacion, observaciones, anio_academico, hora_inicio, hora_finalizacion, fecha, nro_acta
	 * param_null: ubicacion, observaciones, anio_academico, hora_inicio, hora_finalizacion, fecha, nro_acta
	 * 
	 * cache: no
	 * 

	 */
	public function modificar_encabezado_acta($parametros)
	{

        $formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$update_sga_mesas_examen = "";
        $set_sga_mesas_examen = array();
        if(!is_null($parametros['ubicacion'])) $set_sga_mesas_examen[] = "ubicacion = {$parametros['ubicacion']}";
        if(!is_null($parametros['observaciones'])) $set_sga_mesas_examen[] = "observaciones = {$parametros['observaciones']}";
        if(!is_null($parametros['anio_academico'])) $set_sga_mesas_examen[] = "anio_academico = {$parametros['anio_academico']}";

        if(\guarani_arreglos::count($set_sga_mesas_examen) > 0){
            $set_sga_mesas_examen = implode(", ", $set_sga_mesas_examen);

            $update_sga_mesas_examen = "
                UPDATE sga_mesas_examen
				SET $set_sga_mesas_examen
				WHERE mesa_examen = {$parametros['mesa_examen']};
				";
        }

        $update_sga_llamados_mesa = "";
        $set_sga_llamados_mesa = array();
        if(!is_null($parametros['fecha'])) $set_sga_llamados_mesa[] = "fecha = to_date({$parametros['fecha']}, $formato_fecha_visual)";
        if(!is_null($parametros['hora_inicio'])) $set_sga_llamados_mesa[] = "hora_inicio = {$parametros['hora_inicio']}";
        if(!is_null($parametros['hora_finalizacion'])) $set_sga_llamados_mesa[] = "hora_finalizacion = {$parametros['hora_finalizacion']}";

        if(\guarani_arreglos::count($set_sga_llamados_mesa) > 0){
            $set_sga_llamados_mesa = implode(", ", $set_sga_llamados_mesa);

            $update_sga_llamados_mesa = "
                UPDATE sga_llamados_mesa
				SET $set_sga_llamados_mesa
				WHERE llamado_mesa = {$parametros['llamado_mesa']};
				";
        }

        $update_sga_actas = "";
        $set_sga_actas = array();
        if(!is_null($parametros['observaciones'])) $set_sga_actas[] = "observaciones = {$parametros['observaciones']}";
        if(!is_null($parametros['nro_acta'])) $set_sga_actas[] = "nro_acta = {$parametros['nro_acta']}";

        if(\guarani_arreglos::count($set_sga_actas) > 0){
            $set_sga_actas = implode(", ", $set_sga_actas);

            $update_sga_actas = "
                UPDATE sga_actas
				SET    $set_sga_actas
				WHERE  id_acta = {$parametros['id_acta']}
				";
        }

		
		$sql = $update_sga_mesas_examen . $update_sga_llamados_mesa . $update_sga_actas;

        if($sql == "") return 0;

		$resultado = kernel::db()->ejecutar($sql);
		return 	$resultado;		

	}


    /**
     * parametros: id_acta
     * cache: no
     *
     */
    public function incrementar_version_acta($parametros)
    {

        $sql = "UPDATE sga_actas SET version = version + 1 WHERE version = version_impresa AND id_acta = {$parametros['id_acta']}";

        return kernel::db()->ejecutar($sql);

    }
	
}