<?php

namespace siu\operaciones\admin_derechos_comision;

use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\formularios\elementos\guarani_vista_autocompletar;
use siu\operaciones\admin_derechos_comision\filtro\builder_filtro_comisiones;


class controlador extends controlador_g3w2
{
	public $params_comision, $pagina, $hash_comision;
	

	
	
	function get_clase_vista()
	{
		if ($this->accion == 'index' || $this->accion == 'filtrar_comisiones' ) { // se muestra la lista de comisiones
			return 'vista_comisiones';
		} else {
			return 'vista_edicion_derechos';
		}
	}
	
    /**
     * @return \siu\modelo\transacciones\admin_derechos_comision
     */
    function modelo()
    {
		return guarani::admin_derechos_comision();
    }

    function accion__index()
    {
	}


	function accion__edicion()
	{
		$this->load_params();
	}

	function accion__filtrar_comisiones()
	{
		//$this->load_params();
		$filtro = $this->get_builder_filtro()->get_formulario();
		if($filtro->procesar()){
			$vista = $this->vista()->pagelet('comisiones');
			$vista->set_filtro($filtro->get_datos());
			kernel::renderer()->add($vista);
			$this->accion__index();
		}


	}



	public function accion__buscar_actividades()
	{
		$datos = $this->modelo()->info__actividades_docente(array('term' => $this->getAutocompleteTerm()));
		$json = guarani_vista_autocompletar::to_autocompletar($datos, 'elemento', 'descripcion');
		$this->render_raw_json($json);
	}

	function accion__info_docentes()
	{
		$comision = $this->decodificar_param(0, 'get');
		$docentes = $this->modelo()->info__docentes_comision($comision);
		$this->render_raw_json($docentes);
	}

	function accion__guardar()
	{
		$this->load_params();
		$tabla = $this->get_derechos();
		$derechos_actuales = $tabla['filas'];
		$cambios = array();
		$post = kernel::request()->getPost();

		foreach($derechos_actuales as $persona => &$fila){
			$docente = $fila['datos']['id'];
			$derechos_nuevos = isset($post[$docente])? $post[$docente]: array();

			//Para todos los derechos veo si vinieron en el post y si estan habilitados.
			foreach($fila['derechos'] as $id_derecho =>$habilitado){

                //Si el derecho a modificar NO es uno de los habilitados lo salteo
                if(!in_array($id_derecho, $fila['derechos_comision_rol'])) continue;

				if(array_search($id_derecho, $derechos_nuevos) !== false){ //activado en el post

					if($habilitado == 0){ //no existe actualmente
						$cambio = $this->agregar_derecho($id_derecho, $persona);
						kernel::log()->add_debug("Agregando derecho", $cambio);
						$cambios[] = $cambio;
					}
				}else{

					if( $habilitado == 1){
						$cambio = $this->eliminar_derecho($id_derecho, $persona);
						kernel::log()->add_debug("Eliminando derecho", $cambio);
						$cambios[] = $cambio;
					}
				}
			}
		}
		$this->modelo()->evt__procesar_cambios($this->params_comision, $cambios);
		kernel::renderer()->add($this->vista()->pagelet('derechos'));
		$this->accion__edicion();
	}

	/**
	 * @return builder_filtro_comisiones
	 */
	function get_builder_filtro()
	{
		if (! isset($this->builder_filtro)) {
			$this->builder_filtro = kernel::localizador()->instanciar('operaciones\admin_derechos_comision\filtro\builder_filtro_comisiones');
			//$this->form->set_autoset_activo(true);
		}
		return $this->builder_filtro;
	}



	/**
	 * Busca en el get el acta y la página
	 */
	protected function load_params()
	{
		$this->hash_comision = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->params_comision = $this->decodificar_hash($this->hash_comision, 'edicion');

	}


	function get_encabezado()
	{
		return $this->modelo()->info__comision_cabecera($this->params_comision);
	}

	public function get_derechos(){

        $id_docente = kernel::persona()->get_id_docente();
        $comision = $this->params_comision['comision'];

        //Obtengo la responsabilidad del docente en la comision
        $responsabilidad_docente_comision = $this->modelo()->info__responsabilidad_docente_comision($id_docente, $comision);
        $rol_docente = $responsabilidad_docente_comision['responsabilidad'];

        //Obtengo los derechos de ese rol para las comisiones
        $derechos_comision_rol = $this->modelo()->info__derechos_comision_rol($rol_docente);
        //Me quedo solo con el ID del derecho
        $derechos_comision_rol = array_map(function($derecho_comision_rol) {return $derecho_comision_rol['derecho'];}, $derechos_comision_rol);

		$derechos = $this->modelo()->info__derechos_para_comision();
		$docentes = $this->modelo()->info__docentes_comision($this->params_comision);
		$contenido = $this->modelo()->info__derechos_comision($this->params_comision);

		$columnas = array();
		foreach ($derechos as  $columna){
			$columnas[$columna['derecho']] = 0;//deshabilito
		}

		$filas = array();
		foreach($docentes as $fila){
			$filas[$fila['persona']]['derechos'] = $columnas;
			$filas[$fila['persona']]['datos'] = $fila;

            //Si el docente es el actual NO le permito editar ninguno de sus derechos
            if($fila['id'] == $id_docente){
                $filas[$fila['persona']]['derechos_comision_rol'] = array();
            }
            else{
                $filas[$fila['persona']]['derechos_comision_rol'] = $derechos_comision_rol;
            }

		}

		foreach ($contenido as $der){
			$per = $der['persona'];
			$id_der = $der['derecho'];
            $filas[$per]['derechos'][$id_der] = 1;
		}

		$datos = array();
		$datos['columnas'] = $derechos;
		$datos['filas'] = $filas;
		return $datos;
	}


	private function agregar_derecho($id_derecho, $persona)
	{
		return array('A',$persona, $id_derecho);
	}

	private function eliminar_derecho($id_derecho, $persona)
	{
		return array('B',$persona, $id_derecho);
	}
}
?>