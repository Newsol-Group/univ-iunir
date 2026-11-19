<?php
/**
 * Clase para tomar los ws de renaper
 * @link http://php.net/manual/en/language.oop5.late-static-bindings.php
 * @package Renaper
 */

namespace siu\modelo;

use \GuzzleHttp\Client;

class ws_renaper
{
	const MSJ_ERROR_CONEXION = 'Falló la conexión al servidor Renaper';
    const EXITO = 'Exito';
    
	protected $cliente_renaper;
    protected $apiKey;
    protected $content_type;
    protected $url;
    protected $url_header;

    function __construct($apiKey, $content_type, $url, $url_header)
    {	
        $this->content_type = $content_type;
        $this->url = $url;
        $this->url_header = $url_header;
        $this->apiKey = $apiKey;
        $this->cliente_renaper = new Client(['base_uri' => $this->url]);
    }
    
    
    
	public function post_cliente_rest($number = '', $gender = '', $order = '')
	{
        $options = [];
        $headers = ['Content-Type' => $this->content_type, 'apiKey'=> $this->apiKey, 'url'=> $this->url_header];
        $options['headers'] = $headers;
        
        try {
            
            $URI_Response = $this->cliente_renaper->request('POST', $this->url, [
                'headers'=> $headers,
                'json' => ['number' => $number,'gender' => $gender, 'order' => $order]
            ]);
            
            return $URI_Response->getBody()->getContents();
        } catch (RequestException $e) {
            echo Psr7\Message::toString($e->getRequest());
            if ($e->hasResponse()) {
                echo Psr7\Message::toString($e->getResponse());
            }
        }
	}

}