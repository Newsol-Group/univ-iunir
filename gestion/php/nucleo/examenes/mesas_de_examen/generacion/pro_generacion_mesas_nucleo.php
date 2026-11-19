<?php

class pro_generacion_mesas_nucleo extends guarani_procesos_bk
{
	//Parámetros
	protected $anio;
	protected $turno;
	protected $ubicacion;
	protected $fecha_mesas;
	protected $docentes;
	protected $criterio_gen;
	protected $mesa_x_catedra;
	protected $ubicacion_nombre;
	protected $planes			= array();
	protected $instancias		= array();
	protected $escalas_notas	= array();
	protected $modalidades		= array();
	protected $actividades		= array();
	protected $cont_mesas 			= 0;
	protected $cont_mesas_con_obs	= 0;
	protected $cant_mesas_x_actividad;
	
	//Constantes utilizadas en los índices de los reportes generados durante el proceso
	const reporte_resultados 			= 1;
	const reporte_mesas		 			= 2;
	const reporte_mesas_con_obs			= 3;
	const reporte_actividades 			= 4;
	const reporte_actividades_sin_mesas	= 5;
	const tipo_obs_sin_catedra 			= 1;
	
	const nombre = 'pro_generacion_mesas';
	
	function set_datos($datos = array())
	{
		$this->anio			= $datos['anio'];
		$this->turno		= $datos['turno'];
		$this->ubicacion	= $datos['ubicacion'];
		$this->fecha_mesas	= $datos['fecha_mesas'];
		$this->docentes		= $datos['docentes'];
		$this->criterio_gen	= $datos['criterio_gen'];
		$this->planes		= $datos['planes'];
		$this->instancias	= $datos['instancias'];
		$this->escalas_notas	= $datos['escalas_notas'];
		$this->modalidades		= $datos['modalidades'];
		$this->actividades		= $datos['actividades'];
		$this->mesa_x_catedra	= $datos['mesa_x_catedra'];
	}
	
	function procesar ()
	{
		//Seteo los nombres para las Mesas
		mesa_de_examen::set_nombres_default_mesas();
		$cont_actividades_sin_mesas	= 0;
		$cont_actividades			= 0;
		$paso = 100 / guarani_arreglos::count($this->actividades);
		$this->ubicacion_nombre = toba::consulta_php('co_ubicaciones')->get_ubicacion_nombre($this->ubicacion);
		
		$array_planes	 	= 'array['.guarani_texto::armar_lista($this->planes).']';
		$array_modalidades 	= 'array['.$this->armar_lista_quoteada($this->modalidades).']';
        
		//Se recorren las Actividades generando sus Mesas
		foreach ($this->actividades as $actividad) {
            // Control de aborto en pro
            $estado = $this->get_estado();
            if ((isset($estado['abortar'])) && ($estado['abortar'])) {
                $this->abortar = true;
                break;
            }
			
			//Inicializo el número de Mesa en la posición siguiente a la última del arreglo de nombres
			$nro_de_mesa = $this->get_posicion_ultima_mesa($actividad['elemento'], $this->turno);
			$catedras = array();
			$cree_mesa = false;
			$this->cant_mesas_x_actividad = 0;
						
            if (!$this->existe_mesa_en_turno($actividad['elemento'], $this->turno)) {

                //Generación de la Mesa de Examen
                $this->generar_mesa_examen($actividad, $nro_de_mesa, $array_planes, $array_modalidades);
                $cree_mesa = true;
                $this->cant_mesas_x_actividad++;
                $nro_de_mesa++;
            }			
			
			if (!$cree_mesa) {				
				//Registro en el reporte de Actividades sin Mesas creadas
				$this->set_log_actividades($actividad['nombre'].' ('.$actividad['codigo'].')', self::reporte_actividades_sin_mesas);
				$cree_mesa = false;
				$cont_actividades_sin_mesas++;
			} else {
				
				//Registro en el reporte de Actividades con Mesas creadas
				$this->set_log_actividades($actividad['nombre'].' ('.$actividad['codigo'].')', self::reporte_actividades);
				$cont_actividades++;
			}

			//Incremento el progreso para reflejarlo en la barra
			$this->log_estado->incrementar_progreso($paso);
			$this->log->guardar();
		}

		//Registro en el reporte de Cantidades
		$this->set_log_cantidades($cont_actividades, $cont_actividades_sin_mesas);
		$this->log->guardar();
	}
	
	function generar_mesa_examen($actividad, $nro_de_mesa, $array_planes, $array_modalidades)
	{
		//Parámetros para la función
		$nombre_mesa = mesa_de_examen::get_nombre_default_mesa($nro_de_mesa);
        $param_catedra = 1;
		$instancias = array();
		$escalas = array();
		$cant_escalas = guarani_arreglos::count($this->escalas_notas);
		for ($i=0;$i<$cant_escalas;$i++) {
			if (isset($this->escalas_notas[$i])) {
				$escalas[] = $this->escalas_notas[$i];
			} elseif (isset($actividad['escala_nota_examen'])) {
				$escalas[] = $actividad['escala_nota_examen'];
			} else {
				continue;
			}
			$instancias[] = $this->instancias[$i];
		}       
		$array_instancias = 'array['.guarani_texto::armar_lista($instancias).']';
		$array_escalas = 'array['.guarani_texto::armar_lista($escalas).']';

		try {
			//Genero la Mesa
			$llamados_generados = guarani::act('act_mesas_de_examen')->generar_mesa(
                    $this->turno, $this->fecha_mesas, $this->mesa_x_catedra, $this->docentes, $nombre_mesa, $actividad['elemento'], 
                    $param_catedra, $this->ubicacion, $array_instancias, $array_escalas, 
                    $array_planes, $array_modalidades);

		} catch (toba_error $e) {
			//Si se produce algún error sigue con la siguiente
		}
		
		//Contabilizo la Mesa
		$this->cont_mesas++;
		$nombre_actividad 	= $actividad['nombre'];
		$codigo_actividad 	= ' ('.$actividad['codigo'].')';
		$catedra_nombre		= toba::consulta_php('co_catedras')->get_nombre($catedra);
		$observaciones		= '';
		
		if ($tipo_observacion == self::tipo_obs_sin_catedra) {
			$observaciones = "La mesa de examen $nombre_mesa de la actividad $nombre_actividad.$codigo_actividad fue creada sin cátedra porque la misma no tiene cátedras o tiene y estan dadas de baja.";
			$this->cont_mesas_con_obs++;
		}
		if ($catedra_nombre == '') {
			$catedra_nombre = 'Sin cátedra';
		}
		
		foreach ($llamados_generados as $llamado) {
			
			$instancias_nombres	= toba::consulta_php('co_mesas_de_examen')->get_string_instancias_mesa_examen($llamado['mesa']);
			
			if (!is_null($tipo_observacion)) {
				
				//Registro en el reporte de Mesas con observaciones
				$this->set_log_mesas($actividad['elemento'], $nombre_actividad.$codigo_actividad, $llamado['mesa'], $nombre_mesa, $catedra_nombre, $this->ubicacion_nombre, current($instancias_nombres), $llamado['nombre_llamado'], $llamado['fecha'], $llamado['hora_inicio'], $llamado['hora_finalizacion'], $observaciones, self::reporte_mesas_con_obs);
			}
			
			//Registro en el reporte de Mesas
			$this->set_log_mesas($actividad['elemento'], $nombre_actividad.$codigo_actividad, $llamado['mesa'], $nombre_mesa, $catedra_nombre, $this->ubicacion_nombre, current($instancias_nombres), $llamado['nombre_llamado'], $llamado['fecha'], $llamado['hora_inicio'], $llamado['hora_finalizacion'], $observaciones, self::reporte_mesas);
		}
	}
	
	function set_log_mesas($elemento, $elemento_nombre, $mesa, $mesa_nombre, $catedra_nombre, $ubicacion_nombre, $instancias, $llamado, $fecha, $hora_inicio, $hora_fin, $observaciones, $reporte)
	{
		$mensaje = array();
		$mensaje['elemento']			= $elemento;
		$mensaje['elemento_descr']		= $elemento_nombre;
		$mensaje['mesa'] 				= $mesa;
		$mensaje['mesa_nombre'] 		= $mesa_nombre;
		$mensaje['catedra_nombre'] 		= $catedra_nombre;
		$mensaje['ubicacion_nombre']	= $ubicacion_nombre;
		$mensaje['instancias']			= $instancias;
		$mensaje['llamado'] 			= $llamado;
		$mensaje['fecha'] 				= $fecha;
		$mensaje['hora_inicio'] 		= $hora_inicio;
		$mensaje['hora_fin'] 			= $hora_fin;
		$mensaje['observaciones']		= $observaciones;
		$this->log->set($mensaje, array($reporte));
	}
	
	function set_log_actividades($nombre, $reporte)
	{
		$mensaje = array();
		$mensaje['elemento_descr'] = $nombre;
		if ($reporte == self::reporte_actividades) {
			$mensaje['cant_mesas'] = $this->cant_mesas_x_actividad;
		}
		$this->log->set($mensaje, array($reporte));
	}
	
	function set_log_cantidades($actividades, $actividades_sin_mesas)
	{
		$mensaje = array();
		$mensaje['actividades'] 			= $actividades;
		$mensaje['mesas'] 					= $this->cont_mesas;
		$mensaje['mesas_con_obs'] 			= $this->cont_mesas_con_obs;
		$mensaje['actividades_sin_mesas'] 	= $actividades_sin_mesas;
		$this->log->set($mensaje, array(self::reporte_resultados));
	}
	
	function existe_mesa_en_turno($actividad, $turno_destino)
	{
		if ($this->criterio_gen == mesa_de_examen::no_generar_mesa_si_existe) {
			return toba::consulta_php('co_mesas_de_examen')->existe_mesa_en_turno_actividad($actividad, $turno_destino);
		}
		return false;
	}
	
	function armar_lista_quoteada($array)
	{
		$lista = '';
		foreach ($array as $elemento) {
			$lista .= quote($elemento).',';
		}
		return substr($lista, 0, strlen($lista) - 1);
	}
	
	function get_posicion_ultima_mesa($elemento, $turno_destino)
	{
		$filtro = "sga_mesas_examen.elemento = $elemento AND sga_llamados_turno.turno_examen = $turno_destino";
		$mesas 	= toba::consulta_php('co_mesas_de_examen')->get_listado($filtro);
		return guarani_arreglos::count($mesas);
	}
	
}
?>