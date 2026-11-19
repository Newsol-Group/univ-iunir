// Prueba aislada del pagelet: DOM y AJAX simulados, sin navegador ni conexiones.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../../pers/nsg01/operaciones/datos_ctacte/reporte/pagelet_reporte.js'), 'utf8');

function escenario(propuesta) {
    const resultado = {peticiones: [], ventanas: [], alertas: [], envios: 0, campos: {}};
    const botones = {};
    for (const tipo of ['MCP', 'MP']) {
        botones[tipo] = {
            valores: {saldo: 100, index: 7, comprobante: 'referencia-' + tipo, codfor: 'TEST', nrofor: 1},
            data(clave) { return this.valores[clave]; },
            prop(clave, valor) { this[clave] = valor; return this; },
            on(evento, callback) { this.click = callback; }
        };
    }
    const form = {
        length: 1,
        find(campo) { return {val(valor) { resultado.campos[campo] = valor; }}; },
        submit() { resultado.envios++; }
    };
    const root = {
        find(selector) {
            if (selector === '#payForm_7') { return form; }
            if (selector === '.btnPagarMCP') { return botones.MCP; }
            if (selector === '.btnPagarMP') { return botones.MP; }
            throw new Error('Selector inesperado: ' + selector);
        }
    };
    const $ = elemento => elemento === '#reporte_prueba' ? root : elemento;
    $.ajax = opciones => resultado.peticiones.push(opciones);
    let factory;
    vm.runInNewContext(source, {
        kernel: {renderer: {registrar_pagelet(nombre, callback) { factory = callback; }}},
        $, prompt: () => '50', confirm: () => true,
        alert: mensaje => resultado.alertas.push(mensaje),
        window: {open: (...args) => resultado.ventanas.push(args)}
    });
    factory({id: 'reporte_prueba', propuesta, url_pagar_mcp: '/mcp', url_pagar_mp: '/mp',
        error_pago: 'error traducido', msj_confirmar_pagar_mp: 'Confirmar '}).onload();
    return {resultado, botones};
}

let {resultado, botones} = escenario(92001);
botones.MCP.click();
let peticion = resultado.peticiones.pop();
assert.equal(peticion.data.comprobante, 'referencia-MCP');
assert.equal(peticion.data.propuesta, 92001);
assert.equal(peticion.data.monto, '50,00');
assert.equal('index' in peticion.data, false);
assert.equal(resultado.envios, 0);
assert.equal(botones.MCP.disabled, true);
peticion.success({trans_id: 'TEST_1', monto: 'cifrado', hash: 'hash-ficticio'});
assert.equal(resultado.envios, 1);
assert.equal(resultado.campos['input[name="TransaccionComercioId"]'], 'TEST_1');
assert.equal(botones.MCP.disabled, false);

botones.MCP.click();
peticion = resultado.peticiones.pop();
peticion.success({error: 'comprobante no disponible'});
assert.equal(resultado.envios, 1);
assert.equal(resultado.alertas.pop(), 'comprobante no disponible');
botones.MCP.click();
resultado.peticiones.pop().error();
assert.equal(botones.MCP.disabled, false);
assert.equal(resultado.alertas.pop(), 'error traducido');

botones.MP.click();
peticion = resultado.peticiones.pop();
assert.equal(peticion.data.comprobante, 'referencia-MP');
assert.equal(peticion.data.propuesta, 92001);
assert.equal(resultado.ventanas.length, 0);
peticion.success({link: 'https://example.invalid/enlace-validado'});
assert.equal(resultado.ventanas[0][0], 'https://example.invalid/enlace-validado');
assert.equal(botones.MP.disabled, false);
botones.MP.click();
resultado.peticiones.pop().success({error: 'cuenta invalida'});
assert.equal(resultado.ventanas.length, 1);
assert.equal(resultado.alertas.pop(), 'cuenta invalida');
botones.MP.click();
resultado.peticiones.pop().error();
assert.equal(botones.MP.disabled, false);

({resultado, botones} = escenario(null));
botones.MCP.click();
assert.equal('propuesta' in resultado.peticiones.pop().data, false);
botones.MP.click();
assert.equal('propuesta' in resultado.peticiones.pop().data, false);
console.log('OK: 23 verificaciones de payloads, formularios y errores. Sin conexiones ni pagos reales.');
