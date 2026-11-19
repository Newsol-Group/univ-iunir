<?php
namespace siu\modelo\transacciones;

use acta;
use SIU\Chulupi\ErrorKernel;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\errores\error_guarani_procesar_renglones;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\alumno_foto;
use siu\modelo\entidades\rs_arreglo;
use siu\modelo\entidades\rs_consulta;


class admin_derechos_mesa extends transaccion
{

	function iniciar() {}
	
	//---------------------------------------------
	//	INFO
	//---------------------------------------------
	function info__mesas($filtro)
	{
		$parametros = array('persona' => $this->persona);

		if(isset($filtro['actividad'])){
			$parametros['actividad'] = $filtro['actividad'];
		}else{
			$parametros['actividad'] = null;
		}

		$datos = catalogo::consultar('derechos_mesa', 'lista_mesas_docente', $parametros);
		
		$rs = new rs_arreglo($datos);
		$rs->set_columna_id('llamado_mesa');
		$rs->agregar_entidad('llamado_mesa');

		return $rs;
	}
	
	function info__mesa_cabecera($datos)
	{ 
		$parametros = array('llamado_mesa' => $datos['llamado_mesa']);
		$consulta = catalogo::consultar('derechos_mesa', 'mesa_cabecera', $parametros);
		return $consulta;
	}

	function info__derechos_mesa($llamado_mesa)
	{
		$parametros = array('llamado_mesa' => $llamado_mesa);
		return catalogo::consultar('derechos_mesa', 'derechos_mesa', $parametros);
	}

	public function info__docentes_mesa($llamado_mesa){
		$parametros = array('llamado_mesa' => $llamado_mesa);
        $docentes_mesa = catalogo::consultar('derechos_mesa', 'docentes_mesa', $parametros);

        foreach($docentes_mesa as $key => $docente_mesa){
            $id_imagen_docente = ($docente_mesa['id_imagen'])? $docente_mesa['id_imagen'] : null;
            $docentes_mesa[$key]['url_imagen_chica'] = alumno_foto::url_imagen($id_imagen_docente);
        }

        return $docentes_mesa;
	}

	public function info__derechos_para_mesa(){
		return catalogo::consultar('derechos_mesa', 'derechos_asignables');
	}

	function info__actividades_docente($params)
	{
		$parametros = array('persona' => $this->persona, 'term' => $params['term']);
		return catalogo::consultar('derechos_mesa', 'lista_actividades_docente', $parametros);
	}

    function info__rol_docente_mesa($docente, $llamado_mesa)
    {
        $parametros = array('docente' => $docente, 'llamado_mesa' => $llamado_mesa);
        return catalogo::consultar('derechos_mesa', 'rol_docente_mesa', $parametros);
    }

    function info__derechos_mesa_rol($rol)
    {
        $parametros = array('rol' => $rol);
        return catalogo::consultar('derechos_mesa', 'derechos_mesa_rol', $parametros);
    }

	//---------------------------------------------
	//	EVENTOS
	//---------------------------------------------
	function evt__procesar_cambios($mesa_params, $cambios)
	{

		foreach ($cambios as $derecho) {
			$parametros = array('entidad' => $mesa_params['entidad'],
								'persona_' => $derecho[1],
								'derecho' => $derecho[2]);
			if($derecho[0] == 'A'){
				$afectados = catalogo::consultar('derechos_mesa', 'agregar_derecho', $parametros);
			}else if($derecho[0] == 'B'){
				$afectados = catalogo::consultar('derechos_mesa', 'eliminar_derecho', $parametros);
			}

			if($afectados != 1){
			    $error = new error_guarani_procesar_renglones();
				throw $error;
			}
		}
	}
}
?>