<?php
class ci_busqueda_g3entidad extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;
	protected $s__entidad_subtipo;
	protected $s__cuadro;

	function get_datos($filtro='')
	{
		$subtipo = $this->s__entidad_subtipo;
		$clase_co = g3entidad::get_clase_consulta_subtipo($subtipo);
		$metodo = 'get_listado';
		
		switch ($subtipo) {
			case g3entidad::subtipo_grado :
				$filtro .= ($filtro != '') ? ' AND ' : '';
				$filtro .= ' sga_propuestas.propuesta_tipo = '.g3entidad::subtipo_grado;
				break;
			case g3entidad::subtipo_pregrado :
				$filtro .= ($filtro != '') ? ' AND ' : '';
				$filtro .= ' sga_propuestas.propuesta_tipo = '.g3entidad::subtipo_pregrado;
				break;
			case g3entidad::subtipo_posgrado :
				$filtro .= ($filtro != '') ? ' AND ' : '';
				$filtro .= ' sga_propuestas.propuesta_tipo = '.g3entidad::subtipo_posgrado;
				break;
			case g3entidad::subtipo_ciclo_basico :
				$filtro .= ($filtro != '') ? ' AND ' : '';
				$filtro .= ' sga_propuestas.propuesta_tipo = '.g3entidad::subtipo_ciclo_basico;
				break;
			case g3entidad::subtipo_curso_ingreso :
				$filtro .= ($filtro != '') ? ' AND ' : '';
				$filtro .= ' sga_propuestas.propuesta_tipo = '.g3entidad::subtipo_curso_ingreso;
				break;
			case g3entidad::subtipo_postitulo :
				$filtro .= ($filtro != '') ? ' AND ' : '';
				$filtro .= ' sga_propuestas.propuesta_tipo = '.g3entidad::subtipo_postitulo;
				break;
			case g3entidad::subtipo_terciario :
				$filtro .= ($filtro != '') ? ' AND ' : '';
				$filtro .= ' sga_propuestas.propuesta_tipo = '.g3entidad::subtipo_terciario;
				break;
			case g3entidad::subtipo_vocacional :
				$filtro .= ($filtro != '') ? ' AND ' : '';
				$filtro .= ' sga_propuestas.propuesta_tipo = '.g3entidad::subtipo_vocacional;
				break;
			case g3entidad::subtipo_propuesta_curso :
				$filtro .= ($filtro != '') ? ' AND ' : '';
				$filtro .= ' sga_propuestas.propuesta_tipo = '.g3entidad::subtipo_propuesta_curso;
				break;
			case g3entidad::subtipo_convenio :
				$filtro .= ($filtro != '') ? ' AND ' : '';
				$filtro .= ' sga_propuestas.propuesta_tipo = '.g3entidad::subtipo_convenio;
				break;
			case g3entidad::subtipo_mesa_examen_regular :
				$filtro .= ($filtro != '') ? ' AND ' : '';
				$filtro .= ' sga_mesas_examen.mesa_tipo = '.g3entidad::subtipo_mesa_examen_regular;
				break;
			case g3entidad::subtipo_mesa_examen_libre :
				$filtro .= ($filtro != '') ? ' AND ' : '';
				$filtro .= ' sga_mesas_examen.mesa_tipo = '.g3entidad::subtipo_mesa_examen_libre;
				break;
			case g3entidad::subtipo_mesa_examen_regular_libre :
				$filtro .= ($filtro != '') ? ' AND ' : '';
				$filtro .= ' sga_mesas_examen.mesa_tipo = '.g3entidad::subtipo_mesa_examen_regular_libre;
				break;
			case g3entidad::subtipo_mesa_examen_revalida :
				$filtro .= ($filtro != '') ? ' AND ' : '';
				$filtro .= ' sga_mesas_examen.mesa_tipo = '.g3entidad::subtipo_mesa_examen_revalida;
				break;
			case g3entidad::subtipo_raiz :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_modulo, g3entidad::subtipo_raiz, $filtro);
			case g3entidad::subtipo_agrupador :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_modulo, g3entidad::subtipo_agrupador, $filtro);
			case g3entidad::subtipo_materia_generica :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_modulo, g3entidad::subtipo_materia_generica, $filtro);
			case g3entidad::subtipo_orientacion :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_modulo, g3entidad::subtipo_orientacion, $filtro);
			case g3entidad::subtipo_agrupa_orientaciones :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_modulo, g3entidad::subtipo_agrupa_orientaciones, $filtro);
			case g3entidad::subtipo_area_conocimiento :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_modulo, g3entidad::subtipo_area_conocimiento, $filtro);
			case g3entidad::subtipo_area_activ_extracurriculares :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_modulo, g3entidad::subtipo_area_activ_extracurriculares, $filtro);
			case g3entidad::subtipo_competencia :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_modulo, g3entidad::subtipo_competencia, $filtro);
			case g3entidad::subtipo_agrupa_competencias :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_modulo, g3entidad::subtipo_agrupa_competencias, $filtro);
			case g3entidad::subtipo_modulo_correlativas :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_modulo, g3entidad::subtipo_modulo_correlativas, $filtro);
			case g3entidad::subtipo_modulo_certificados :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_modulo, g3entidad::subtipo_modulo_certificados, $filtro);
			case g3entidad::subtipo_materia :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_actividad, g3entidad::subtipo_materia, $filtro);
			case g3entidad::subtipo_activ_extracurricular :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_actividad, g3entidad::subtipo_activ_extracurricular, $filtro);
			case g3entidad::subtipo_tesis :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_actividad, g3entidad::subtipo_tesis, $filtro);
			case g3entidad::subtipo_practica_profesional :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_actividad, g3entidad::subtipo_practica_profesional, $filtro);
			case g3entidad::subtipo_pasantia :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_actividad, g3entidad::subtipo_pasantia, $filtro);
			case g3entidad::subtipo_seminario :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_actividad, g3entidad::subtipo_seminario, $filtro);
			case g3entidad::subtipo_taller :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_actividad, g3entidad::subtipo_taller, $filtro);
			case g3entidad::subtipo_actividad_curso :
				if (! $clase_co) return array();
				return toba::consulta_php($clase_co)->get_listado(g3entidad::tipo_actividad, g3entidad::subtipo_actividad_curso, $filtro);
			case g3entidad::subtipo_plan_estudio_version :
				$metodo = 'get_lista_planes';
		}

		if (! $clase_co) return array();
		return toba::consulta_php($clase_co)->$metodo($filtro);
	}
	
	//---- filtro -----------------------------------------------------------------------
	
	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
		$filtro->columna('entidad_subtipo')->set_condicion_fija('es_igual_a', true);
	}

	function evt__filtro__filtrar($datos)
	{
		$subtipo = $datos['entidad_subtipo']['valor'];
		$tabla = g3entidad::get_tabla_subtipo($subtipo);
		
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro = '';
			if (isset($datos['nombre'])) {
				$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
				$filtro .= $filtro_condicion->get_sql("f_limpiar_acentos($tabla.nombre::varchar)", guarani_texto::limpiar_acentos($datos['nombre']['valor']));
			}
			$this->s__filtro = $filtro;
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
		
		$this->s__entidad_subtipo = $subtipo;
		switch ($subtipo) {
			case g3entidad::subtipo_responsable_academica :				
				$this->s__cuadro = 'cuadro_ra';
				break;
			case g3entidad::subtipo_plan_estudio :
				$this->s__cuadro = 'cuadro_planes';
				break;
			case g3entidad::subtipo_plan_estudio_version :
				$this->s__cuadro = 'cuadro_versiones_planes';
				break;
			case g3entidad::subtipo_certificado :
				$this->s__cuadro = 'cuadro_certificados';
				break;					
			case g3entidad::subtipo_grado :
			case g3entidad::subtipo_pregrado :
			case g3entidad::subtipo_posgrado :
			case g3entidad::subtipo_ciclo_basico :
			case g3entidad::subtipo_curso_ingreso :
			case g3entidad::subtipo_postitulo :
			case g3entidad::subtipo_terciario :
			case g3entidad::subtipo_vocacional :
			case g3entidad::subtipo_propuesta_curso :
			case g3entidad::subtipo_convenio :
				$this->s__cuadro = 'cuadro_tipos_propuestas';
				break;
			case g3entidad::subtipo_materia :
			case g3entidad::subtipo_activ_extracurricular :
			case g3entidad::subtipo_tesis :
			case g3entidad::subtipo_practica_profesional :
			case g3entidad::subtipo_pasantia :
			case g3entidad::subtipo_seminario :
			case g3entidad::subtipo_actividad_curso :
			case g3entidad::subtipo_taller:
				$this->s__cuadro = 'cuadro_actividades';
				break;
			case g3entidad::subtipo_comisiones :
				$this->s__cuadro = 'cuadro_comisiones';
				break;			
			case g3entidad::subtipo_mesas_examen :
			case g3entidad::subtipo_mesa_examen_regular :
			case g3entidad::subtipo_mesa_examen_libre :
			case g3entidad::subtipo_mesa_examen_regular_libre :
			case g3entidad::subtipo_mesa_examen_revalida :
				$this->s__cuadro = 'cuadro_mesas_examen';
				break;
			case g3entidad::subtipo_turnos_examen :
				$this->s__cuadro = 'cuadro_turnos_examen';
				break;
			case g3entidad::subtipo_raiz :
			case g3entidad::subtipo_agrupador :
			case g3entidad::subtipo_materia_generica :
			case g3entidad::subtipo_orientacion : 
			case g3entidad::subtipo_agrupa_orientaciones :
			case g3entidad::subtipo_area_conocimiento :
			case g3entidad::subtipo_area_activ_extracurriculares :
			case g3entidad::subtipo_competencia :
			case g3entidad::subtipo_agrupa_competencias :
			case g3entidad::subtipo_modulo_correlativas :
			case g3entidad::subtipo_modulo_certificados :
				$this->s__cuadro = 'cuadro_modulos';
				break;
		}
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
		unset($this->s__entidad_subtipo);
		unset($this->s__cuadro);
	}
	
	//---- cuadro -----------------------------------------------------------------------
	
	function conf()
	{
		if (! isset($this->s__cuadro)) return true;

		if (isset($this->s__filtro)) {
			$datos = $this->get_datos($this->s__filtro);
		} else {
			$datos = $this->get_datos();
		}

		$this->dep($this->s__cuadro)->set_datos($datos);
		$this->dep($this->s__cuadro)->desactivar_modo_clave_segura();
		$this->pantalla('seleccion')->agregar_dep($this->s__cuadro);
	}
}
?>