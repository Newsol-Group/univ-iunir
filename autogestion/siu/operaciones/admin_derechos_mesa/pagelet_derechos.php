<?php
namespace siu\operaciones\admin_derechos_mesa;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\transacciones\admin_derechos_mesa;

class pagelet_derechos extends pagelet
{
	protected $precondiciones;
	protected static $iconos = array(
		//los nombre completos tienen que ir primeros porque los mas generales matchean antes
		'bitacora_administrar' => 'icon-list',
		'cargar_notas' => 'icon-pencil',
		'asistencia_editar' => 'icon-pencil',
		'anular' => 'icon-minus',
		'cerrar' => 'icon-ok',
		'consultar' => 'icon-search',
		'crear' => 'icon-plus',
		'imprimir' => 'icon-print',
		'editar' => 'icon-edit',
		'justificar' => 'icon-edit',
		'mesa_admin_cur_virtuales' => 'icon-book',
	);

	protected function get_clase_desde_derecho($derecho){

		if(strpos($derecho, 'mesa') === 0){
			$clase = 'col-mesa';
		}
		else if(strpos($derecho, 'acta') === 0){
			$clase = 'col-acta';
		}
		else {
			$clase = substr($derecho, 0, strpos($derecho, '_'));
		}
		return  $clase;
	}

    function get_nombre()
    {
        return 'derechos';
    }

	/**
	 * @return admin_derechos_mesa
	 */
	protected function modelo()
	{
		return $this->controlador->modelo();
	}

	public function get_js_files()
	{
		$archivos = parent::get_js_files();
		return $archivos;
	}

	protected function get_icono_desde_derecho($derecho){

		foreach (self::$iconos as $id=>$icono) {
			if (substr($derecho, -strlen($id)) == $id) return $icono;
		}
		return '';
	}

	protected function get_derechos(){
		$tabla = $this->controlador->get_derechos();

		$colgroup = $this->generar_colgroup($tabla['columnas']);

		$tabla['colgroup'] = $colgroup;
		foreach ($tabla['columnas'] as  &$columna){ //
			$columna['icono'] = $this->get_icono_desde_derecho($columna['derecho']);
		}
		return $tabla;
	}

	protected function get_url_guardar()
	{
		return kernel::vinculador()->crear('admin_derechos_mesa', 'guardar', array($this->controlador->hash_mesa));
	}


    function prepare()
    {
		$renglones  = $this->get_derechos();

//		echo "<pre>";
//		print_r($renglones);
		klog2('derechos___', $renglones);
		
		$this->data = $renglones;
	    $this->data['encabezado'] = $this->controlador->get_encabezado();
	    $this->data['url_back'] = $this->get_url_back();
		$this->data['url_guardar'] = $this->get_url_guardar();

		$this->add_mensaje_js('mensaje_exito', kernel::traductor()->trans('guardar_ok'));

    }

	/**
	 * @param $columnas
	 * @internal param $columna
	 * @return array
	 */
	protected function generar_colgroup($columnas)
	{
		$colgroup = array();
		$colgroup[] = array('clase' => 'docente', 'span' => 1);
		$clase_anterior = 'docente';
		$span = 0;


		foreach ($columnas as  $columna){

			$clase_actual = $this->get_clase_desde_derecho($columna['derecho']);
			//var_dump($clase_actual);
			if ($clase_actual != $clase_anterior && $span > 0) {
				$colgroup[] = array('clase' => $clase_anterior, 'span' => $span);
				$span =0;
			}
			$span++;
			$clase_anterior = $clase_actual;
		}

		if ($span > 0) {
			$colgroup[] = array('clase' => $clase_actual, 'span' => $span);
		}
		return $colgroup;
	}

	private function get_url_back()
	{
		/* url botón Volver */
		$operacion = kernel::ruteador()->get_id_operacion();
		//$this->agregar_a_contexto('url_back', );
		return kernel::vinculador()->crear($operacion);
	}
}