<?php

namespace siu\extension_kernel;

use SIU\Chulupi\nucleo\controlador as controlador_chulupi;
use SIU\Chulupi\nucleo\ruteador;
use SIU\Chulupi\util\u;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\modelo\entidades\fuente_rs_guarani;
use SIU\Chulupi\util\modelo\AdaptadorModelo;

/**
 * @property helper_controlador_g3w2 $helper
 */
abstract class controlador_g3w2 extends controlador_chulupi
{

	protected $codificador_modelo;
    protected $helper;
	
	/*
	 * @var string $pdf_fuente	Fuente por defecto de los PDFs
	 * @see https://redmine.siu.edu.ar/redmine/issues/41609
	 */
	protected $pdf_fuente = "Courier.afm";

    public function __construct(){

        if(!isset($this->helper)){
            $this->helper = kernel::localizador()->instanciar('extension_kernel\helper_controlador_g3w2');
        }

    }

    /**
     * @return helper_controlador_g3w2
     */
    protected function get_helper(){
        return $this->helper;
    }

	/**
	 *
	 * @param string $key
	 * @param string $method
	 * @param string $type valores posibles: string, int, float, url, string_array, int_array
	 * @param array $options
	 *    allow_quotes: boolean
	 *  trim: boolean
	 * @return string
	 */
	protected function get_param($key, $method = 'post', $type = 'string', $options = array())
	{
		$value = ($method == 'post') ? kernel::request()->getPost($key) : kernel::url()->get_param($key);
		return u::sanitize_value($value, $type, $options);
	}

	protected function force_ssl()
	{
		if ((kernel::proyecto()->get_ssl_alcance() == ruteador::SSL_ALCANCE_NONE) || (!kernel::proyecto()->get_ssl_redirigir_ssl())) {
			return;
		}

		if (!isset($_SERVER['HTTPS']) || $_SERVER["HTTPS"] != "on") {
			header("Location: " . kernel::url()->self(true));
			die;
		}
	}

	protected function render_raw_json($data)
	{
		parent::render_raw_json($data);
	}

	/**
	 * @return \SIU\Chulupi\util\modelo\AdaptadorModelo|AdaptadorModelo
	 */
	function codificador_modelo()
	{
		if (isset($this->codificador_modelo))
			return $this->codificador_modelo;

		$p = new AdaptadorModelo(
			$this->modelo(),
			new fuente_rs_guarani(),
			kernel::ruteador()->get_id_operacion(),
			$this->accion);
		$p->habilitar_control_entidades();

		$this->codificador_modelo = $p;
		return $p;
	}

	function procesar_accion()
	{
		parent::procesar_accion();
		$info = $this->get_info_zona();
		if (empty($info)) return;

		switch ($info['zona']) {
			case 'comision':
				$clase = 'operaciones\\_comun\\zonas\\zona_comision';
				break;
			case 'examen':
				$clase = 'operaciones\\_comun\\zonas\\zona_examen';
				break;
			case 'clase':
				$clase = 'operaciones\\_comun\\zonas\\zona_clase';
				break;
			default:
				throw new error_guarani("Zona no soportada- La zona debe indicarse en el campo 'zona'");
		}
		/** @var $zona siu\operaciones\_comun\zonas\zona */
		$zona = kernel::localizador()->instanciar($clase, $this);
		if (isset($info['seleccion'])) {
			$zona->set_entidad_seleccionada($info['seleccion']);
		}

	}

	/**
	 * Devuelve un array('zona'=> 'comision'\'xxxx', 'seleccion' => 'id_entidad_seleccionada').
	 * Si es vacio no se activa la zona.
	 * El template debe incluir a la zona (posiblemente en los template padres del chulupi)
	 * Se llama justo antes de finalizar el procesamiento de la accion.
	 * @return array
	 */
	function get_info_zona()
	{
		return null;
	}
	
    /**
     * Ventana que se ejecuta antes de ejecutar una acción
     */
    protected function pre_accion()
    {
        $this->get_helper()->pre_accion();
    }
}