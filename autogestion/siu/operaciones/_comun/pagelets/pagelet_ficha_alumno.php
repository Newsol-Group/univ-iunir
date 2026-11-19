<?php
namespace siu\operaciones\_comun\pagelets;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\guarani;

class pagelet_ficha_alumno extends pagelet
{
	protected $data_ficha;
	protected $url_imagen;
	
	protected function get_operacion_fisica()
    {
        return '_comun/pagelets';
    }
	
    function get_nombre()
    {
        return 'ficha_alumno';
    }
	
	function set_data($data)
	{
		$this->data_ficha = $data;
	}
	
	function set_url_imagen($url)
	{
		$this->url_imagen = $url;
	}
	
    function prepare()
    {
		$this->data['ficha'] = $this->data_ficha;
		$this->data['url_imagen'] = $this->url_imagen;
		kernel::log()->add_debug('DATAX', $this->data);
    }
}
?>
