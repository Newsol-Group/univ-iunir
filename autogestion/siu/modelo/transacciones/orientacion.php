<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\guarani;
use siu\errores\error_guarani;
use siu\modelo\controles\controles;
use siu\modelo\datos\catalogo;
use siu\errores\error_guarani_seguridad;
use siu\modelo\controles\proceso_control;

class orientacion extends transaccion
{
	protected $operacion = 'agcert01';
	protected $evento = '2';
	protected $estrategia_acceso_datos;
	protected $nro_inscripcion;
	protected $lista_orientaciones;
	protected $orientacion_actual;

	function __construct($nro_inscripcion)
	{
		$this->nro_inscripcion = $nro_inscripcion;
		parent::__construct($nro_inscripcion);
	}	
	//---------------------------------------------
	//	INFO
	//---------------------------------------------

	function lista_orientaciones()
	{
		$nuevos = array();
		if (!empty($this->lista_orientaciones)){
			$nuevos = $this->lista_orientaciones;
		} else {	
			$datos = array();
			$parametros['nro_inscripcion'] = kernel::persona()->get_nro_inscripcion();
			$datos = catalogo::consultar('orientacion', 'lista_orientaciones', $parametros);

			$carrera_activa = kernel::persona()->get_id_propuesta_activa();
			$orientacion_actual = $this->orientacion_actual();

			if (!empty($orientacion_actual) && !empty($orientacion_actual[0][0])) {
				$o_actual = $orientacion_actual[0][0];
			} else {
				$o_actual = false;
			}

			$i = 0;
			foreach($datos as $value) {
				if ($value['carrera'] == $carrera_activa && $value['orientacion'] != $o_actual){
					$nuevos[$i] = $value;
					$i++;
				}
			}
			$this->lista_orientaciones = $nuevos;
		}
		return $nuevos;
	}
	
	function get_orientacion($id){
		$orientaciones = $this->lista_orientaciones();
		
		foreach ($orientaciones as $registro){
			if ($registro['__ID__'] == $id){
				$orientacion = $registro;	
			}
		}
		if(!isset($orientacion)){
			throw new error_guarani_seguridad('No existe la orientación');
		}
		
		return $orientacion;
	}
	
	function orientacion_actual()
	{
		$nuevo = array();
		if (!empty($this->orientacion_actual)){
			$nuevo = $this->orientacion_actual;
		} else {
			$parametros = array();
			$parametros['carrera'] = kernel::persona()->get_id_propuesta_activa();
			$parametros['legajo'] = kernel::persona()->get_legajo_activo();		
			$datos = catalogo::consultar('orientacion', 'orientacion_actual', $parametros);

			foreach($datos as $key => $value){
				$nuevo[$key]['orientacion'] = $value[0];
				$nuevo[$key]['plan'] =  $value[2];
				$nuevo[$key]['titulo'] =  $value[4];
				$nuevo[$key]['nombre'] =  $value[1];
				$nuevo[$key]['titulo_id'] =  $value[3];			
			}
			$this->orientacion_actual = $nuevo;
		}
		return $nuevo;
	}
			
	//---------------------------------------------
	//	EVENTOS
	//---------------------------------------------
	function evt__solicitar($id)
	{
		kernel::db()->abrir_transaccion();
		
		$parametros = array();
		$parametros['carrera'] = kernel::persona()->get_id_propuesta_activa();
		$parametros['legajo'] = kernel::persona()->get_legajo_activo();
		
		$seleccion = $this->get_orientacion($id);
		$o_actual = $this->orientacion_actual($parametros);
		
		$parametros['plan'] = $seleccion['plan'];
		$parametros['titulo'] = $seleccion['titulo_id'];
		$parametros['orientacion'] = $seleccion['orientacion'];
		$parametros['movimiento'] = "A";
		
		if (!empty($o_actual)){			
			$parametros_actual['carrera'] = $parametros['carrera'];
			$parametros_actual['legajo'] = $parametros['legajo'];
			$parametros_actual['plan'] = $o_actual[0]['plan'];
			$parametros_actual['titulo'] = $o_actual[0]['titulo_id'];
			$parametros_actual['orientacion'] = $o_actual[0]['orientacion'];
			$parametros_actual['movimiento'] = "B";	
			
			// elimino la orientación actual
			$ok_actual = catalogo::consultar('orientacion', 'seleccionar_orientacion', $parametros_actual);
		}
		
		$ok = catalogo::consultar('orientacion', 'seleccionar_orientacion', $parametros);
		
		if ($ok[0] >= 0){
			kernel::db()->cerrar_transaccion();
			return true;
		} else {
			kernel::db()->abortar_transaccion();
			throw new error_guarani('Error durante la selección de orientación');
		}
	}	
}
?>