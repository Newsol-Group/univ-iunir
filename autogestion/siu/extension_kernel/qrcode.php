<?php
namespace siu\extension_kernel;

use siu\guarani;

class qrcode {
    protected $title = "";
    protected $datei = "";
    protected $datef = "";
    protected $description = "";
    protected $location = "";

    public function __construct($title = null, $datei = null, $datef = null, $description = "", $location = null) {
        $this->title = $title;
        $this->datei = $datei;
        $this->datef = $datef;
        $this->description = $description;
        $this->location  = $location;
    }

    public function get_code() {
        $this->generate_qr();
    }

    private function generate_qr() {
        $text = "BEGIN:VEVENT
SUMMARY:".$this->title."
DTSTART:".$this->datei."
DTEND:".$this->datef."
LOCATION:
DESCRIPTION:".$this->description."
END:VEVENT";
        $img = \QRcode::png($text);
        return $img;
    }

    //----------------------------------------------------------------------

    public function get_code_periodos() {
        $this->generate_qr_periodos();
    }

    public function generate_qr_periodos() {
        $text = "BEGIN:VEVENT
SUMMARY:{$this->title}
DTSTART:{$this->datei}
DTEND:{$this->datef}
END:VEVENT";

        $img = \QRcode::png($text);
        return $img;
    }

    public function get_calendar_event_info() {
        
        $text = "BEGIN:VEVENT
SUMMARY:{$this->title}
DTSTART;TZID=America/Argentina/Buenos_Aires:{$this->datei}
DTEND;TZID=America/Argentina/Buenos_Aires:{$this->datef}\n";
if($this->location) $text .= "LOCATION:{$this->location}\n";
$text .= "END:VEVENT";
        
        return $text;
    }
}
