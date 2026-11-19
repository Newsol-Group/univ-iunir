<?php
class ci_nav_catedras extends ci_navegacion
{
	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('legajo')->set_condicion_fija('contiene');
		$filtro->columna('docente')->set_condicion_fija('contiene');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}
	
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;

                        $filtro = '';

			if (isset($datos['legajo'])) {
				$valor_legajo = $datos['legajo']['valor'];
				$this->dep('filtro')->columna('legajo')->resetear_estado();
			}
			
			if (isset($datos['docente'])) {
				$valor_docente = $datos['docente']['valor'];
				$this->dep('filtro')->columna('docente')->resetear_estado();
			}
			
			if (isset($datos['actividad'])) {
				$valor_actividad = $datos['actividad']['valor'];
				$this->dep('filtro')->columna('actividad')->resetear_estado();
			}
			
			$filtro = $this->dep('filtro')->get_sql_where();
			
			if (isset($valor_legajo)) {

                                $expresion_filtro_sga_docentes_legajo = guarani_ei_filtro::get_expresion('sga_docentes.legajo', $datos['legajo']['valor'], $datos['legajo']['condicion_cs'], $datos['legajo']['condicion_as']);
				$filtro .= "  AND sga_catedras.catedra IN (	SELECT 	sga_catedras_docentes.catedra
															FROM	sga_catedras_docentes,
																	sga_docentes
															WHERE	sga_catedras_docentes.docente = sga_docentes.docente AND 
																	$expresion_filtro_sga_docentes_legajo)";
			}

			if (isset($valor_docente)) {
				$expresion_filtro_mdp_personas_nombres = guarani_ei_filtro::get_expresion('COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)', $datos['docente']['valor'], $datos['docente']['condicion_cs'], $datos['docente']['condicion_as']);
				$expresion_filtro_mdp_personas_apellido = guarani_ei_filtro::get_expresion('COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido)', $datos['docente']['valor'], $datos['docente']['condicion_cs'], $datos['docente']['condicion_as']);
				$filtro .= " AND sga_catedras.catedra IN (	SELECT 	sga_catedras_docentes.catedra
															FROM	sga_catedras_docentes,
																	sga_docentes,
																	mdp_personas
															WHERE	sga_catedras_docentes.docente = sga_docentes.docente AND
																	sga_docentes.persona = mdp_personas.persona	AND
																	($expresion_filtro_mdp_personas_nombres OR
																	$expresion_filtro_mdp_personas_apellido))";
			}

			if (isset($valor_actividad)) {
                            
                                $expresion_filtro_sga_elementos_codigo = guarani_ei_filtro::get_expresion('sga_elementos.codigo', $datos['actividad']['valor'], $datos['actividad']['condicion_cs'], $datos['actividad']['condicion_as']);
                                $expresion_filtro_sga_elementos_nombre = guarani_ei_filtro::get_expresion('sga_elementos.nombre', $datos['actividad']['valor'], $datos['actividad']['condicion_cs'], $datos['actividad']['condicion_as']);
				$filtro .= " AND sga_catedras.catedra IN (	SELECT 	sga_catedras_actividades.catedra
															FROM 	sga_catedras_actividades,
																	sga_elementos
															WHERE	sga_catedras_actividades.elemento = sga_elementos.elemento AND
																	($expresion_filtro_sga_elementos_codigo OR 
																	$expresion_filtro_sga_elementos_nombre))";
			}

			$this->s__filtro = $filtro;
		} else {
			unset($this->s__datos_filtro);
			$this->s__filtro = '';
		}
	}
	
	function evt__cancelar()
	{
		toba::memoria()->limpiar_memoria();
		parent::evt__cancelar();
	}

	function evt__eliminar()
	{
		if ($this->cn()->entidad()->validar_eliminacion()) {
			toba::memoria()->limpiar_memoria();
			parent::evt__eliminar();	
		} else {
			$msg = guarani_mensajes::get('800_dato_no_eliminable', array('Cátedra', 'está definida en una Comisión o Mesa de Examen'));
			toba::notificacion()->error($msg);
		}
		
	}

	function evt__guardar()
	{
		$nombre_repetido = $this->cn()->entidad()->nombre_repetido();
		toba::memoria()->limpiar_memoria();
		parent::evt__guardar();
		if ($nombre_repetido) {
			$mensaje = "Atención: el nombre elegido está siendo utilizado para otras cátedras";
			toba::notificacion()->agregar($mensaje, 'warning');
		}
	}

}