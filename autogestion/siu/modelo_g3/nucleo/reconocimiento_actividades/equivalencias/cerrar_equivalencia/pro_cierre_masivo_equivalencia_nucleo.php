<?php

class pro_cierre_masivo_equivalencia_nucleo extends guarani_procesos_bk
{
	protected $tramites;
	const nombre = 'pro_cierre_masivo_equivalencia';
	
	const reporte_cerrados = 1;
    const reporte_no_pasaron = 2;
	const reporte_resultados = 3;
	
	function set_datos($datos=array())
	{		
		$this->tramites = $datos['tramites'];
	}
	
	function procesar()
	{
		if (!empty($this->tramites)) {
			$paso = 100 / guarani_arreglos::count($this->tramites);
		} else {
			$paso = 100;
			$this->log_estado->incrementar_progreso($paso);
		}
		
		$cerrados = 0;
		$no_pasaron = 0;
		foreach ($this->tramites as $tramite) {
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}
			$datos_tramite = toba::consulta_php('co_equivalencias')->get_datos_tramite_equivalencia_otorgado($tramite['equivalencia_tramite']);
			
			$mensaje = array();
			$mensaje['equivalencia_tramite'] = $datos_tramite['equivalencia_tramite'];
			$mensaje['fecha'] = $datos_tramite['fecha'];
			$mensaje['alumno_nombre_completo'] = $datos_tramite['alumno_nombre_completo'];
			$mensaje['legajo'] = $datos_tramite['legajo'];
			$mensaje['propuesta_alumno'] = $datos_tramite['propuesta_alumno'];
			$mensaje['documento_numero'] = $datos_tramite['documento_numero'];
			$mensaje['origen_descr'] = $datos_tramite['origen_descr'];
			try {
				toba::cn('manejador_cerrar_equivalencia')->cerrar_tramite_equivalencia($tramite['equivalencia_tramite'], $tramite['digital'], $this->usuario);
				$cerrados ++;
				$this->log->set($mensaje, array(self::reporte_cerrados));
			} catch (guarani_error $e) {
				$no_pasaron ++;				
				$mensaje['error'] = $e->get_mensaje_log();
				$this->log->set($mensaje, array(self::reporte_no_pasaron));
			} catch (toba_error $e) {
				$no_pasaron ++;				
				$mensaje['error'] = $e->get_mensaje_log();				
				$this->log->set($mensaje, array(self::reporte_no_pasaron));
			} catch (\Exception $e) {
				$no_pasaron ++;				
				$mensaje['error'] = $e->getMessage();				
				$this->log->set($mensaje, array(self::reporte_no_pasaron));
			}
			$this->log_estado->incrementar_progreso($paso);
		}
		$mensaje = array();
		$mensaje['reporte_cerrados'] = $cerrados;
		$mensaje['reporte_no_pasaron'] = $no_pasaron;
		$this->log->set($mensaje, array(self::reporte_resultados));
		$this->log->guardar();
	}

}