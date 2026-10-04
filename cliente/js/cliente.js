/* Cliente de la API REST de productos con jQuery.ajax */
$(function () {
    'use strict';

    var API = '../api.php';
    var estado = { pagina: 1, por_pagina: 8, orden: 'id' };
    var editandoId = null;
    var modal = new bootstrap.Modal('#modal');
    var pesos = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' });

    function esc(t) {
        return $('<div>').text(t == null ? '' : String(t)).html();
    }

    /** Hace el pedido y lo registra en la consola de la derecha. */
    function pedir(metodo, ruta, datos) {
        var opciones = {
            url: API + ruta,
            method: metodo,
            dataType: 'json',
            headers: { 'X-API-Key': $('#clave').val() }
        };
        if (metodo === 'GET') {
            opciones.data = datos;
        } else if (datos) {
            opciones.contentType = 'application/json';
            opciones.data = JSON.stringify(datos);
        }
        return $.ajax(opciones).always(function (a, b, c) {
            var xhr = a && a.status !== undefined && a.readyState !== undefined ? a : c;
            var url = ruta + (metodo === 'GET' && datos ? '?' + $.param(datos) : '');
            var cuerpo = xhr.responseJSON ? JSON.stringify(xhr.responseJSON, null, 2) : '';
            if (cuerpo.length > 600) {
                cuerpo = cuerpo.slice(0, 600) + '\n…';
            }
            $('#consola').prepend('<div class="linea"><span class="metodo metodo-' + metodo + '">' + metodo + '</span> ' + esc(url) +
                ' <span class="badge ' + (xhr.status < 300 ? 'text-bg-success' : 'text-bg-danger') + '">' + xhr.status + '</span>' +
                (cuerpo ? '<pre>' + esc(cuerpo) + '</pre>' : '') + '</div>');
        });
    }

    function cargar() {
        var params = $.extend({}, estado, {
            q: $('#q').val(),
            categoria: $('#categoria').val(),
            sin_stock: $('#sin-stock').is(':checked') ? 1 : ''
        });
        $.each(params, function (k, v) { if (v === '') { delete params[k]; } });

        pedir('GET', '/productos', params).done(function (r) {
            $('#filas').html(r.datos.length ? $.map(r.datos, function (p) {
                return '<tr class="' + (p.activo ? '' : 'text-muted') + '">' +
                    '<td>' + p.id + '</td>' +
                    '<td class="font-monospace small">' + esc(p.sku) + '</td>' +
                    '<td>' + esc(p.nombre) + (p.activo ? '' : ' <span class="badge text-bg-secondary">inactivo</span>') + '</td>' +
                    '<td class="d-none d-md-table-cell">' + esc(p.categoria) + '</td>' +
                    '<td class="text-end text-nowrap">' + pesos.format(p.precio) + '</td>' +
                    '<td class="text-end">' + (p.stock ? p.stock : '<span class="badge text-bg-warning">0</span>') + '</td>' +
                    '<td class="text-end text-nowrap">' +
                        '<button class="btn btn-sm btn-outline-primary editar" data-id="' + p.id + '" title="Editar"><i class="bi bi-pencil"></i></button> ' +
                        '<button class="btn btn-sm btn-outline-danger borrar" data-id="' + p.id + '" data-nombre="' + esc(p.nombre) + '" title="Eliminar"><i class="bi bi-trash"></i></button>' +
                    '</td></tr>';
            }).join('') : '<tr><td colspan="7" class="text-center text-muted py-4">Sin resultados</td></tr>');

            var m = r.meta;
            $('#resumen').text(m.total + ' productos · página ' + (m.paginas ? m.pagina : 0) + ' de ' + m.paginas);
            $('#anterior').prop('disabled', m.pagina <= 1);
            $('#siguiente').prop('disabled', m.pagina >= m.paginas);

            $('th[data-orden] .bi').attr('class', 'bi');
            var campo = estado.orden.replace('-', '');
            $('th[data-orden="' + campo + '"] .bi').addClass(estado.orden.charAt(0) === '-' ? 'bi-sort-down' : 'bi-sort-up');
        });
    }

    function cargarCategorias() {
        pedir('GET', '/categorias').done(function (r) {
            var actual = $('#categoria').val();
            $('#categoria').html('<option value="">Todas las categorías</option>' + $.map(r.datos, function (c) {
                return '<option value="' + esc(c.categoria) + '">' + esc(c.categoria) + ' (' + c.cantidad + ')</option>';
            }).join('')).val(actual);
            $('#lista-categorias').html($.map(r.datos, function (c) { return '<option value="' + esc(c.categoria) + '">'; }).join(''));
        });
    }

    /* ---------- Filtros, orden y paginación ---------- */

    var espera;
    $('#q').on('input', function () {
        clearTimeout(espera);
        espera = setTimeout(function () { estado.pagina = 1; cargar(); }, 300);
    });
    $('#categoria, #sin-stock').on('change', function () { estado.pagina = 1; cargar(); });
    $('th[data-orden]').on('click', function () {
        var campo = $(this).data('orden');
        estado.orden = estado.orden === campo ? '-' + campo : campo;
        cargar();
    });
    $('#anterior').on('click', function () { estado.pagina--; cargar(); });
    $('#siguiente').on('click', function () { estado.pagina++; cargar(); });
    $('#limpiar').on('click', function () { $('#consola').empty(); });

    /* ---------- Alta, edición y baja ---------- */

    function limpiarErrores() {
        $('#form .is-invalid').removeClass('is-invalid');
        $('#form-error').addClass('d-none');
    }

    $('#btn-nuevo').on('click', function () {
        editandoId = null;
        $('#form')[0].reset();
        limpiarErrores();
        $('#modal-titulo').text('Nuevo producto');
        modal.show();
    });

    $('#filas').on('click', '.editar', function () {
        var id = $(this).data('id');
        pedir('GET', '/productos/' + id).done(function (r) {
            var p = r.datos;
            editandoId = p.id;
            limpiarErrores();
            $('#modal-titulo').text('Editar producto #' + p.id);
            $('#f-sku').val(p.sku);
            $('#f-nombre').val(p.nombre);
            $('#f-categoria').val(p.categoria);
            $('#f-precio').val(p.precio);
            $('#f-stock').val(p.stock);
            $('#f-activo').prop('checked', p.activo);
            modal.show();
        });
    });

    $('#filas').on('click', '.borrar', function () {
        var id = $(this).data('id');
        if (!confirm('¿Eliminar "' + $(this).data('nombre') + '"?')) {
            return;
        }
        pedir('DELETE', '/productos/' + id).done(function () {
            cargar();
            cargarCategorias();
        }).fail(function (xhr) {
            alert(xhr.responseJSON ? xhr.responseJSON.error.mensaje : 'Error de conexión');
        });
    });

    $('#form').on('submit', function (e) {
        e.preventDefault();
        limpiarErrores();
        var datos = {
            sku: $('#f-sku').val(),
            nombre: $('#f-nombre').val(),
            categoria: $('#f-categoria').val(),
            precio: $('#f-precio').val() === '' ? null : Number($('#f-precio').val()),
            stock: $('#f-stock').val() === '' ? 0 : Number($('#f-stock').val()),
            activo: $('#f-activo').is(':checked')
        };
        var peticion = editandoId ? pedir('PUT', '/productos/' + editandoId, datos) : pedir('POST', '/productos', datos);

        peticion.done(function () {
            modal.hide();
            cargar();
            cargarCategorias();
        }).fail(function (xhr) {
            var err = xhr.responseJSON ? xhr.responseJSON.error : { mensaje: 'Error de conexión' };
            if (err.detalles) {
                $.each(err.detalles, function (campo, msj) {
                    $('#form [name=' + campo + ']').addClass('is-invalid').siblings('.invalid-feedback').text(msj);
                });
            } else {
                $('#form-error').text(err.mensaje).removeClass('d-none');
            }
        });
    });

    cargarCategorias();
    cargar();
});
