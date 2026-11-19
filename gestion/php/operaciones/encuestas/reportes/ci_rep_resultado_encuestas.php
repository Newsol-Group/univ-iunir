<?php
class ci_rep_resultado_encuestas extends ci_rep_sin_listado_inicial
{
	protected $s__id;		
	protected $s__ev_info_encuesta;
			
	function conf()
	{
		
	}
	
	function get_datos($filtro)
	{
		return $this->cn()->get_listado($filtro);
	}
	
	function get_tabla_principal()
	{
		return 'gde_habilitaciones';
	}

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('docente')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{		
		if (isset($datos)) {
			$this->s__datos = $datos;			
			$filtro_docentes = '';
			if (isset($datos['docente'])) {
				$filtro_docentes .= " AND
									(	gde_encuestas.subtipo = '".encuesta::subtipo_docente."' AND gde_habilitaciones.tipo IN (".encuesta::tipo_docente.", ".encuesta::tipo_docente_academica.", ".encuesta::tipo_pares.") AND gde_items.docente = {$datos['docente']['valor']}
										OR
										gde_encuestas.subtipo = '".encuesta::subtipo_academica."' AND gde_habilitaciones.tipo IN (".encuesta::tipo_academica.", ".encuesta::tipo_docente_academica.") AND gde_items.docente IS NULL AND gde_items.comision = gde_formularios.comision AND EXISTS (	SELECT	1
																																																																									FROM	sga_docentes_comision 
																																																																									WHERE	sga_docentes_comision.comision = gde_formularios.comision AND
																																																																											sga_docentes_comision.docente = {$datos['docente']['valor']})
									)";
				$this->dep('filtro')->columna('docente')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_docentes";
		} else {
			unset($this->s__datos);
			$this->s__filtro = "";
		}
	}
	
	
	function ev_info_encuesta()
	{
		if (!isset($this->s__ev_info_encuesta)) {
			$this->s__ev_info_encuesta = guarani::ev_info_datos();
			$this->s__ev_info_encuesta->set_columnas_a_mostrar(array('encuesta_titulo', 'apellido_nombre_docente', 'actividad_nombre', 'comision_nombre', 'periodo_lectivo_nombre', 'cant_encuestados', 'cant_respondidas'));			
			$this->s__ev_info_encuesta->set_etiqueta('encuesta_titulo', 'Encuesta');
			$this->s__ev_info_encuesta->set_etiqueta('apellido_nombre_docente', 'Docente');			
			$this->s__ev_info_encuesta->set_etiqueta('actividad_nombre', 'Actividad');
			$this->s__ev_info_encuesta->set_etiqueta('comision_nombre', 'Comision');
			$this->s__ev_info_encuesta->set_etiqueta('periodo_lectivo_nombre', 'Periodo Lectivo');
			$this->s__ev_info_encuesta->set_etiqueta('cant_encuestados', 'Encuestados');
			$this->s__ev_info_encuesta->set_etiqueta('cant_respondidas', 'Respondidas');
		}
		return $this->s__ev_info_encuesta;
	}	
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->cn()->cargar_encuesta($seleccion['id'], $seleccion['elemento_externo']);		
		$this->ev_info_encuesta()->set_datos($this->get_encuesta());		
		$this->set_pantalla('pant_detalle');
	}
	
	function evt__volver()
	{
		$this->cn()->reset_variables();
		unset($this->s__ev_info_encuesta);
		$this->set_pantalla('pant_inicial');
	}
	
	function get_encuesta()
	{
		return $this->cn()->get_encuesta();
	}
	
	function get_bloques()
	{
		return $this->cn()->get_bloques();
	}	
	
	function get_preguntas($id_encuesta, $id_bloque, $unidad_gestion)
	{
		return $this->cn()->get_preguntas($id_encuesta, $id_bloque, $unidad_gestion);
	}
	
	function get_respuestas($id_habilitacion, $id_formulario, $id_encuesta, $id_elemento, $id_bloque, $id_pregunta, $pregunta_orden, $unidad_gestion)
	{
		return $this->cn()->get_respuestas($id_habilitacion, $id_formulario, $id_encuesta, $id_elemento, $id_bloque, $id_pregunta, $pregunta_orden, $unidad_gestion);
	}
}
?>