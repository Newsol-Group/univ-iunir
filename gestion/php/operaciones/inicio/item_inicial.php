<?php

//Obtengo un contenedor para los gadgets a mostrar en pantalla
$container = toba::contenedor_gadgets();

$gadget = new gadget_operaciones();
$gadget->set_titulo('Operaciones Recientes');
$gadget->set_eliminable('S');
$gadget->set_proyecto('guarani');
$gadget->set_item('141000060');
$gadget->set_orden(1);

//Lo agrego al contenedor
$container->agregar_gadgets(array($gadget));

//GADGET DE RECURSOS

/*    $gadget = new toba_gadget_shindig();
$gadget->set_gadget_url('http://localhost/guarani/3.0/gadgets/recursos.xml'); //reemplazar el url por el que corresponda
$gadget->set_titulo('Recursos SIU');
$gadget->set_eliminable('S');
$gadget->set_orden(2);


//Lo agrego al contenedor
$container->agregar_gadgets(array($gadget));*/

//Grafico los gadgets
$container->generar_html();

echo '<div class="logo">';
echo toba_recurso::imagen_proyecto("logo_grande.png",true);
echo '</div>';

?>