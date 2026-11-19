<?php

namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class historia_academica extends transaccion_alumno
{
	
	protected $operacion = 'mat000xxxx';
	protected $evento = '2';
	protected $actividad_academica;
	protected $actividad_academica_creditos;

	const CREDITOS = 'Creditos';
	const CREDITOS_GLOBALES = 'CreditosGlo';
	const CREDITOS_MODULO = 'CreditosMod';

	function ordenar_materia($datos)
	{
		$materia_nombre = array();
		foreach ($datos as $key => $fila) {
			$materia_nombre[$key]  = $fila['materia_nombre'];
		}
		array_multisort($materia_nombre, SORT_DESC, $datos);
		return $datos;
	}

	/**
	 * Retorna la historia académica del alumno (aprobaciones, regularidades y en curso) 
	 *
	 * @param type $orden por defecto '' ordena por anio del resultado, pasando como parametro el string 
	 *					  'materia' ordena por nombre de materia
	 * @return type 
	 */
	function get_actividad_academica($orden = '')
	{        
		if (!isset($this->actividad_academica)) {
			$this->actividad_academica = catalogo::consultar('alumno', 'get_actividad_academica', array('alumno' => $this->alumno));
		} 
		
		$datos = $this->actividad_academica;
		
		switch ($orden) {
			case 'materia':
			   $datos = $this->ordenar_materia($datos);
			   break;
	    }
		return $datos;
	}



	/**
	 * Retorna la historia académica del alumno con créditos (aprobaciones, reconocimientos, regularidades y en curso)
	 *
	 * @param type $orden por defecto 'materia' ordena por el nombre de la materia, pasando como parametro el string 'anio' ordena por el año
	 * @return type
	 */
	function get_actividad_academica_con_creditos($orden = 'materia')
	{
		if (!isset($this->actividad_academica_creditos)) {
			$this->actividad_academica_creditos = catalogo::consultar('alumno', 'get_actividad_academica_con_creditos', array(
					'alumno' => $this->alumno,
					'orden' => $orden
				)
			);
		}

		return $this->actividad_academica_creditos;
	}
	
	/**
	 *  Dada una materia retrona los resultados más importantes de esa materia en la historia académica del alumno
	 *  
	 * @param type $materia
	 * @return type 
	 * 
	 */
	function get_info_actividad_academica($materia)
	{
		
		$actividad_academica = $this->get_actividad_academica();
		$datos_materia = array();
		
		foreach ($actividad_academica as $actividad) {

				
				if ($materia == $actividad['materia']) {
					
					// Datos que van para cualquier tipo
					
					$datos_comunes= array();
					$datos_comunes['tipo']					= $actividad['tipo'];
					$datos_comunes['anio']					= $actividad['anio'];
					$datos_comunes['materia_nombre_red']	= $actividad['materia_nombre_red'];
					$datos_comunes['origen']				= $actividad['origen'];
					$datos_comunes['plan_version']			= $actividad['plan_version'];
					$datos_comunes['anio_academico']		= $actividad['anio_academico'];
					$datos_comunes['catedra']				= $actividad['catedra'];
					
					if ($actividad['tipo'] == 'Aprobacion')  { 
						if ($actividad['resultado'] == 'A') {
							
							
							
							$datos_materia = $datos_comunes;
							$datos_materia['fecha_display']			= $actividad['fecha_display'];
							$datos_materia['fecha_orden']			= $actividad['fecha_orden'];
							$datos_materia['nota']					= $actividad['nota'];
							$datos_materia['nota_descripcion']		= $actividad['nota_descripcion'];
							$datos_materia['resultado']				= $actividad['resultado'];
							$datos_materia['resultado_descripcion']	= $actividad['resultado_descripcion'];
							$datos_materia['acta_resolucion']		= $actividad['acta_resolucion'];
							$datos_materia['equivalencia']			= $actividad['equivalencia'];
							
							if ($datos_materia['origen'] == 'Promoción') {
								$datos_materia['comision']				= $actividad['comision']; 
								$datos_materia['comision_nombre']		= $actividad['comision_nombre']; 
								$datos_materia['periodo_lectivo']		= $actividad['periodo_lectivo'];
							}
							
								
							
							break;
						} else {
							continue; // no aprobado
						}
						
					}
		
					if ($actividad['tipo'] == 'Regularidad'){ 
						if ($actividad['resultado'] == 'A' /* && $actividad['VIGENTE'] == '1' */) {
							
							$datos_a_setear = $datos_comunes;
							$datos_a_setear['fecha_display']		= $actividad['fecha_display'];
							$datos_a_setear['fecha_orden']			= $actividad['fecha_orden'];
							$datos_a_setear['nota']					= $actividad['nota'];
							$datos_a_setear['nota_descripcion']		= $actividad['nota_descripcion'];
							$datos_a_setear['resultado']			= $actividad['resultado'];
							$datos_a_setear['resultado_descripcion']= $actividad['resultado_descripcion'];
							$datos_a_setear['acta_resolucion']		= $actividad['acta_resolucion'];
							$datos_a_setear['equivalencia']			= $actividad['equivalencia'];
							$datos_a_setear['comision']				= $actividad['comision']; 
							$datos_a_setear['comision_nombre']		= $actividad['comision_nombre']; 
							$datos_a_setear['periodo_lectivo']		= $actividad['periodo_lectivo']; 
							$datos_a_setear['vigente']				= $actividad['vigente']; 
							$datos_a_setear['cond_regularidad']		= $actividad['cond_regularidad']; 
							$datos_a_setear['fin_vigencia_regul']	= $actividad['fin_vigencia_regul']; 
							$datos_a_setear['turno_cursada']		= $actividad['turno_cursada']; 

							if (! isset($datos_materia['materia'])) {
								$datos_materia = $datos_a_setear;
							} else {
								if ($datos_materia['tipo'] != 'En Curso') {
									if ($datos_materia['fecha_orden'] < $actividad['fecha_orden'] ) {
										$datos_materia = $datos_a_setear;
									}	
								} 
							}
						} 
						continue;
					}
					// si esta en curso piso la cursada
					if ($actividad['tipo'] == 'En Curso') {
						$datos_materia = $datos_comunes;
						$datos_materia['calidad_insc']		= $actividad['calidad_insc'];

					}
				}
				
			}
		
		return $datos_materia;
		
	}
	
		
	function get_detalle_materia_aprobada($acta)
	{
		$datos = catalogo::consultar('alumno','get_detalle_examen',
									array(
								          'persona' => $this->persona,
										  'id_acta' => $acta)
				                   );
		return $datos;
		
	}
	
	function get_detalle_materia_en_curso($comision)
	{
		$datos = catalogo::consultar('alumno','get_detalle_en_curso',
									array(
								          'persona' => $this->persona,
										  'comision' => $comision)
				                   );
		return $datos;
		
	}
	
	
	function get_detalle_materia_cursada($acta)
	{
		
		$datos = catalogo::consultar('alumno','get_detalle_cursada',
									array(
											'id_acta' => $acta,
											'alumno' => $this->alumno
										)
				                   );
		$datos_final = array();
		$datos_final['evaluaciones'] = array();		
		if (isset($datos['comision'])){
			$parametros['comision'] = $datos['comision'];
			$parametros['persona'] = $this->persona;
			$datos_final['evaluaciones'] = catalogo::consultar('alumno','get_datos_parciales', $parametros);
		}
		
		$datos_final['comision'] = $datos;
		return $datos_final;
	}
	
	
	function get_detalle_materia_promocionada($acta)
	{
		$datos = catalogo::consultar('alumno','get_detalle_promo',
									array(
											'id_acta'	=> $acta,
											'alumno' => $this->alumno
										)
				                   );
		$datos_final = array();
		$datos_final['evaluaciones'] = array();
		
		if (isset($datos['comision'])){
			$parametros['comision'] = $datos['comision'];
			$parametros['persona'] = $this->persona;
			$datos_final['evaluaciones'] = catalogo::consultar('alumno','get_datos_parciales', $parametros);
		}
		
		$datos_final['comision'] = $datos;
		return $datos_final;
	}
	
	
	function get_detalle_equivalencia($equivalencia)
	{	
		$datos = catalogo::consultar('alumno','get_detalle_equiv',
									array(
										  'equivalencia' => $equivalencia)
				                   );
		return $datos;
	}

	function get_detalle_creditos($nro_tramite)
	{
		return catalogo::consultar('alumno', 'get_detalle_creditos', array('nro_tramite' => $nro_tramite));
	}
	
	
	function get_resultado_generica($materia_generica, $titulo)
	{
		$datos = catalogo::consultar('alumno', 'get_resultado_generica',
									array('carrera'=>kernel::persona()->get_id_propuesta_activa(),
								          'legajo' => $this->alumno,
										  'plan'   => $this->plan,
										  'titulo' => $titulo,
										  'materia' => $materia_generica)
				                   );
		return $datos;
	}
	
		
	function get_detalles_generica($materia_generica)
	{
		$ret = array();
		$ret['titulo'] = $this->get_info_titulo();
		$ret['resultado'] = $this->get_resultado_generica($materia_generica, $ret['titulo']['TITULO']);
		$ret['optativas'] = $this->get_optativas_generica($materia_generica);
		
		return $ret;
	}

    function get_certificados_obtenidos(){

        $parametros['alumno'] = $this->alumno;
        $certificados_obtenidos = catalogo::consultar('alumno', 'get_certificados_obtenidos', $parametros);
        return $certificados_obtenidos;

    }
}
?>
