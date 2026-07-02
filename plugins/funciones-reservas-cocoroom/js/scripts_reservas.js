window.addEventListener('popstate', function(event) {
    // Aquí puedes manejar la lógica de restauración de estado
});
( function( factory ) {
    "use strict";
    if ( typeof define === "function" && define.amd ) {
        define( [ "../widgets/datepicker" ], factory );
    } else {
        factory( jQuery.datepicker );
    }
} )( function( datepicker ) {
"use strict";
datepicker.regional.es = {
    closeText: "Cerrar",
    prevText: "Ant",
    nextText: "Sig",
    currentText: "Hoy",
    monthNames: [ trad.Enero, trad.Febrero, trad.Marzo, trad.Abril, trad.Mayo, trad.Junio,
    trad.Julio, trad.Agosto, trad.Septiembre, trad.Octubre, trad.Noviembre, trad.Diciembre ],
    monthNamesShort: [ "ene", "feb", "mar", "abr", "may", "jun",
    "jul", "ago", "sep", "oct", "nov", "dic" ],
    dayNames: [ "Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado" ],
    dayNamesShort: [ "dom", "lun", "mar", "mié", "jue", "vie", "sáb" ],
    dayNamesMin: [ trad.D, trad.L, trad.M, trad.X, trad.J, trad.V, trad.S ],
    weekHeader: "Sm",
    dateFormat: "dd/mm/yy",
    firstDay: 1,
    isRTL: false,
    showMonthAfterYear: false,
    yearSuffix: "" };
datepicker.setDefaults( datepicker.regional.es );
return datepicker.regional.es;
} );
$( function(){
    const datePicker_campoFecha = $( "#fecha_reserva" ).datepicker({
        minDate: 0,
        dateFormat: 'yy-mm-dd'
    });

    datePicker_campoFecha.datepicker("setDate", null);
    $(".ui-datepicker-today a").removeClass("ui-state-active")
});


jQuery(document).ready(function($) {

    var request = null;

    let $bloque_1 = $('#bloque_1');
    let $bloque_2 = $('#bloque_2');
    let $bloque_3 = $('#bloque_3');
    let $btn_reservar = $('#btn_reservar');

    $('#num_usuarios').on('change', function(e) {
        if($(this).val()>=2 && $(this).val()<=19){
            $("html, body").animate({ scrollTop: $bloque_2.offset().top }, "fast");
              $bloque_2.fadeIn(500);
        }
        else{
            location.reload();
        }
    })

    $('#num_usuarios, #fecha_reserva').on('change', function() {
        $bloque_3.fadeOut(500);
        $btn_reservar.slideUp(500);
        $("#floating-button").css("bottom","12px");
        $("html, body").animate({ scrollTop: $bloque_1.offset().top }, "fast");

        let num_usuarios = $('#num_usuarios').val().trim();
        let fecha_reserva = $('#fecha_reserva').val().trim();
        if(typeof(juego_seleccionado) == "undefined"){
            juego_seleccionado=""
        }

        if (num_usuarios !== "" && num_usuarios>=2 && num_usuarios<=19) {
            
            if (fecha_reserva !== "") {

                request = $.ajax({
                    type: 'POST',
                    url: ajax_object.ajax_url,
                    data: {
                        action: 'obtener_reservas_por_ciudad',
                        ciudad: ciudad,
                        num_usuarios: num_usuarios,
                        fecha_reserva: fecha_reserva,
                        juego_seleccionado: juego_seleccionado,
                    },
                    beforeSend: function () {
                        if (request !== null) {
                            request.abort();
                        }
                    },
                    success: function(response) {
                        let parsedResponse = JSON.parse(response)

                        if(!parsedResponse.error){
                            let juegos = parsedResponse.juegos
                            let reservas = parsedResponse.reservas
                            let cierres = parsedResponse.cierres
                            let reserva_telefonica = parsedResponse.reserva_telefonica
                            let juego_seleccionado_api = parsedResponse.juego_seleccionado

                            if(juego_seleccionado_api == juego_seleccionado){
                                $bloque_3.fadeIn(500);
                                $("html, body").animate({ scrollTop: $bloque_3.offset().top }, "fast");
                                if(!parsedResponse.mensaje_error_juego_api)
                                    dibujar_juegos_disponibles(juegos, reservas, cierres, reserva_telefonica, juego_seleccionado_api)
                                else
                                    dibujar_juegos_disponibles(juegos, reservas, cierres, reserva_telefonica, juego_seleccionado_api,parsedResponse.mensaje_error_juego_api)
                            }
                            else{
                                $bloque_3.fadeIn(500);
                                $("html, body").animate({ scrollTop: $bloque_3.offset().top }, "fast");
                                dibujar_juegos_disponibles(juegos, reservas, cierres, reserva_telefonica)
                            }
                        }
                        else{
                            $bloque_3.fadeOut(500);
                            $("html, body").animate({ scrollTop: $bloque_1.offset().top }, "fast");
                            swal("error", parsedResponse.error)
                        }
                    },
                    // error: function(e) {
                    //     swal("error", trad.mensaje_error_servidor)
                    //     console.log(e);
                    // }
                });
            }
        }
    });

    

    function dibujar_juegos_disponibles(juegos, reservas, cierres, reserva_telefonica, juego_seleccionado_api=0, mensaje_error_juego_api=0){
        $('#contenedor_juegos').html("")
        $('#contenedor_mensaje_error').html("")
        $btn_reservar.slideUp(500);
        $("#floating-button").css("bottom","12px");


        let indice = 0;
        for(let juego of juegos){
            indice++;
            
            let id_salas = [];
            let nombre_salas = [];
            let visibilidad_dinamica = "";
            let class_color = "color_"+juego.color;

            if(class_color == "color_Normal"){
                class_color = "";
            }

            if(juego_seleccionado_api!=0 && juego_seleccionado_api != juego.id){
                visibilidad_dinamica="visibilidad_dinamica";
            }
            
            Object.values(juego.datos_salas).forEach(sala => {
                id_salas.push(sala.id.toString());
                nombre_salas.push(sala.nombre);
            });
            nombre_salas = nombre_salas.join(", ");
            nombre_salas = nombre_salas!="" ? " (" + nombre_salas + ")" : "";

            let contenedor_duracion_estimada = Number(juego.duracion_estimada) <= 60 ? "" : `
                        <div class='duracion_juego'>
                            <svg version="1.1"  xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="16px" height="16px" viewBox="0 0 16 16" enable-background="new 0 0 16 16" xml:space="preserve"> <g><path d="M8,0.25C3.727,0.25,0.25,3.727,0.25,8S3.727,15.75,8,15.75s7.75-3.477,7.75-7.75S12.273,0.25,8,0.25z M8,14.25c-3.446,0-6.25-2.804-6.25-6.25S4.554,1.75,8,1.75S14.25,4.554,14.25,8S11.446,14.25,8,14.25z"/> <path d="M8.75,7.689V3.494c0-0.414-0.336-0.75-0.75-0.75S7.25,3.08,7.25,3.494V8c0,0.199,0.079,0.39,0.22,0.53 l1.787,1.787c0.146,0.146,0.338,0.22,0.53,0.22s0.384-0.073,0.53-0.22c0.293-0.293,0.293-0.768,0-1.061L8.75,7.689z"/></g></svg>
                            ${juego.duracion_estimada} min
                        </div>`;

            let juego_html = `<div style='display: none;' class='juego ${class_color} ${visibilidad_dinamica}'
                        data-id-juego='${juego.id}'
                        data-telefono_contacto_local='${juego.telefono_contacto_local}'
                        data-id-local='${juego.id_local}'
                        data-id-salas='${id_salas.join(",")}'>
                            <div class='title_sala'>
                                <h3>${juego.nombre}
                                    <span class='ver_precios' data-lista_precios='${juego.lista_precios}'>${trad.ver_precios}</span>
                                </h3>
                                ${contenedor_duracion_estimada}
                            </div>
                        <div class='direccion_sala'>${juego.direccion_local}</div>`;

                        //     <h3>${juego.nombre} ${nombre_salas}
                        //         <span class='ver_precios' data-lista_precios='${juego.lista_precios}'>${trad.ver_precios}</span>
                        //     </h3>

            // let juego_html = "<div style='display: none;' class='juego " + class_color + " " + visibilidad_dinamica + "' data-id-juego='" + 
            //                     juego.id + "' data-telefono_contacto_local='" + juego.telefono_contacto_local + "' data-id-local='" + juego.id_local + "' data-id-salas='" + 
            //                     id_salas.join(",") + "'> <div class='title_sala'><h3>" + juego.nombre + nombre_salas  + "<span class='ver_precios' data-lista_precios='" + juego.lista_precios + "'>"+trad.ver_precios+"</span></h3> </div><div class='direccion_sala'>" 
            //                     + juego.direccion_local+ "</div>";
            let juego_horas =  juego.horas.split(',');


            

            var porcentajes_por_hora = {};
            if(juego.porcentajes){
                let juego_porcentajes =  juego.porcentajes.split(',')
                juego_porcentajes.forEach(function (porcentaje_hora) {
                var partes = porcentaje_hora.split('-');
                var hora = partes[0].trim();
                var porcentaje = (partes[1].trim());
                porcentajes_por_hora[hora] = porcentaje;
                });
            }

            for(let hora of juego_horas){                
                let deshabilitado = false;

                let salasJuego = id_salas;

                for (let cierre of cierres) {

                    let hora_comparacion = "";
                    let hora_array = hora.split(':');
                    if(hora_array[0].length == 1)
                        hora_comparacion = "0"+hora
                    else
                        hora_comparacion = hora

                    if((cierre.tipo_cierre == "local")){
                        if(cierre.id_local == juego.id_local){
                            if (cierre.hora.tipo === "todo_el_dia") {
                                deshabilitado = true;
                                break;
                            }
                            if (cierre.hora.tipo === "hora") {
                                if (hora_comparacion === cierre.hora.valores) {
                                    deshabilitado = true;
                                    break;
                                }
                            }
                            if (cierre.hora.tipo === "intervalo") {
                                let horas_array = cierre.hora.valores.split(',');
                                if (hora_comparacion >= horas_array[0] && hora_comparacion <= horas_array[1]) {
                                    deshabilitado = true;
                                    break;
                                }
                            }
                        }
                    }
                    else if((cierre.tipo_cierre == "juego")){
                        let salasCierre = cierre.id_salas;

                        if (salasCierre !== "") {
                            salasCierre = salasCierre.split(',');
                        } else {
                            salasCierre = [];
                        }

                        if (cierre.hora.tipo === "todo_el_dia") {
                            deshabilitado = condicion_deshabilitar_juego(salasJuego, salasCierre, cierre.id_juego, juego.id)
                            if(deshabilitado)
                                break;
                        }

                        if (cierre.hora.tipo === "hora") {
                            if (hora_comparacion === cierre.hora.valores) {
                                deshabilitado = condicion_deshabilitar_juego(salasJuego, salasCierre, cierre.id_juego, juego.id)
                                if(deshabilitado)
                                    break;
                            }
                        }
                        if (cierre.hora.tipo === "intervalo") {
                            let horas_array = cierre.hora.valores.split(',');
                            if (hora_comparacion >= horas_array[0] && hora_comparacion <= horas_array[1]) {
                                deshabilitado = condicion_deshabilitar_juego(salasJuego, salasCierre, cierre.id_juego, juego.id)
                                if(deshabilitado)
                                    break;
                            }
                        }
                    }
                }
                
                for(let reserva of reservas) {
                    if(deshabilitado){
                        break;
                    }

                    let salasReserva = reserva.id_salas;

                    if (salasReserva !== "") {
                        salasReserva = salasReserva.split(',');
                    } else {
                        salasReserva = [];
                    }

                    if(reserva.hora_reserva == hora)
                        deshabilitado = condicion_deshabilitar_juego(salasJuego, salasReserva, reserva.id_juego, juego.id)
                    
                    if(reserva.hora_reserva.includes(" - ")) {
                        if(reserva.hora_reserva.split(" - ")[0] == hora)
                            deshabilitado = condicion_deshabilitar_juego(salasJuego, salasReserva, reserva.id_juego, juego.id)
                    }
                }
 
                // Dependiendo del valor de "deshabilitado", ajustamos el HTML del checkbox.
                
                hora = deshabilitado ? '<span style="text-decoration:line-through;">' + hora + '</span>' : hora;
                var porcentaje = porcentajes_por_hora[hora];
                if (porcentaje !== undefined) {
                    hora_concatenada_porcentaje = hora + " - " + porcentaje;
                    porcentaje = `
                    <span class="porcentaje_descuento">
                        ${porcentaje}
                    </span>`;
                }
                else{
                    hora_concatenada_porcentaje = hora;
                    porcentaje = "";
                }

                
                    if(!deshabilitado){
                        if(!reserva_telefonica){
                            juego_html += `
                                <div class="select_time_div">
                                    <label class="check_content">
                                        <input class="select_time" type='checkbox' autocomplete="off" name='${hora_concatenada_porcentaje}'>
                                        <span class="reserva_"></span>
                                    </label>
                                    ${porcentaje}
                                    <span class="hora">
                                        ${hora}
                                    </span>
                                </div>
                            `;
                        }
                        else{
                            juego_html += `
                                <div class="select_time_div">
                                    <label class="check_content reserva_telefonica_">
                                        <span class="reserva_ reserva_telefonica_"><i class="material-icons">call</i> ${trad.Ver_disponibilidad}</span>
                                    </label>
                                    ${porcentaje}
                                    <span class="hora">
                                        ${hora}
                                    </span>
                                </div>
                            `;
                        }
                    }
                    else{
                        if(!reserva_telefonica){
                            juego_html += `
                                <div class="select_time_div">
                                    <label class="check_content">
                                        <i class="material-icons">lock</i>
                                    </label>
                                    ${porcentaje}
                                    <span class="hora">
                                        ${hora}
                                    </span>
                                </div>
                            `;
                        }
                        else{
                            juego_html += `
                                <div class="select_time_div">
                                    <label class="check_content reserva_telefonica_lock_">
                                        <i class="material-icons">lock</i>
                                    </label>
                                    ${porcentaje}
                                    <span class="hora">
                                        ${hora}
                                    </span>
                                </div>
                            `;
                        }
                    }
                    
                
                
            }

            juego_html += "</div>"
            juego_html = $(juego_html)
            $('#contenedor_juegos').append(juego_html);
            if(!visibilidad_dinamica)
                juego_html.delay(100 * indice).fadeIn(500);
        }

        if(juego_seleccionado_api!=0){
            if( mensaje_error_juego_api!=0 ){
                $('#contenedor_mensaje_error').html("<br><br>" + mensaje_error_juego_api + "<br><button id='btn_mostrar_todos'>"+trad.quieres_reservar_para_otro_juego+"</button>")
                $('#contenedor_mensaje_error').fadeIn(500);
            }
            else{
                $('#contenedor_mensaje_error').fadeIn(500);
                $('#contenedor_mensaje_error').html("<br><br>" + "Parece que hay más juegos disponibles. <br><button id='btn_mostrar_todos'>"+trad.quieres_reservar_para_otro_juego+"</button>")
            }
            $('#btn_mostrar_todos').on('click', function() {
                $('#contenedor_mensaje_error').fadeOut(500);
                $('#contenedor_juegos').children().each(function(index, element) {
                    $(element).delay(200 * index).fadeIn();
                });
            })
        }

        $('.select_time').change(function() {
            verificarBoton();
        });
        function verificarBoton() {
            if ($('.select_time:checked').length > 0) {
                $btn_reservar.slideDown(500);
                $("#floating-button").css("bottom","80px");
            } else {
                $btn_reservar.slideUp(500);
                $("#floating-button").css("bottom","12px");
            }
        }

        if(reserva_telefonica){
            $('.check_content.reserva_telefonica_').click(function() {
                var fechaOriginal = $('#fecha_reserva').val().trim();
                var fecha = new Date(fechaOriginal);
                var dia = fecha.getDate();
                var mes = fecha.getMonth() + 1;
                var año = fecha.getFullYear();
                var nuevaFecha = dia + "/" + mes + "/" + año;

                swal("info", `
                    <div class="telefono_contacto_local">Teléfono de contacto: ${$(this).closest(".juego").data('telefono_contacto_local')}</div>
                    <span class="mensaje_llamadas">${trad.mensaje_llamada_telefonica}</span>
                    <ul>
                        <li>${trad.tu_nombre_y_apellidos}</li> 
                        <li>${trad.numero_de_telefono}</li> 
                        <li>${trad.correo_electronico}</li> 
                        <li>${trad.numero_de_jugadores}: ${$('#num_usuarios').val().trim()} jugadores</li> 
                        <li>${trad.juego_para_reservar}: ${$(this).closest(".juego").find(".title_sala h3").text().replaceAll(trad.ver_precios," ")}</li> 
                        <li>${trad.fecha_de_la_reserva}: ${nuevaFecha}</li> 
                        <li>${trad.hora_seleccionada}: ${$(this).closest(".select_time_div").find(".hora").text()}</li> 
                        <li>${trad.alguna_solicitud_extra}</li> 
                    </ul> 
                `)
            });
        }

        $('.ver_precios').on('click', function() {
            let lista_precios_string = trad.lista_precios_por_jugador+" <br>" + $(this).data('lista_precios').replaceAll(",","<br>")
            swal("info", lista_precios_string)
        });
    }

    function condicion_deshabilitar_juego(salasJuego, salasCierre, cierre_id_juego, juego_id){
        let res = false;
        // 1. Comprobación si tiene sala y coinciden
        if(tieneInterseccion(salasJuego, salasCierre)) {
            res = true;
        }
        // 2. Comprobación si no tiene salas y coincide los juegos
        if(salasJuego.length==0 && salasCierre.length==0 && cierre_id_juego == juego_id) {
            res = true;
        }
        // 2. Comprobación si tiene una sala y coinciden
        if(salasJuego.length==1 && salasCierre.length==1 && salasJuego[0]==salasCierre[0]) {
            res = true;
        }
        return res;
    }


    function tieneInterseccion(a, b) {
        return a.some(v => b.includes(v));
    }

    $('#btn_reservar').on('click', function() {
        let num_usuarios = $('#num_usuarios').val().trim();
        let fecha_reserva = $('#fecha_reserva').val().trim();
        let horas_seleccionadas = [];
        
        $("input[type='checkbox']:checked").each(function() {
            let checkbox = $(this);
            let juegoContenedor = checkbox.closest('.juego'); 
            let hora = checkbox.attr('name');
    
            let id_juego = juegoContenedor.data('id-juego');
            let id_local = juegoContenedor.data('id-local');
            let salas = juegoContenedor.data('id-salas');
    
            horas_seleccionadas.push({
                id_juego: id_juego,
                id_local: id_local,
                id_salas: salas,
                hora: hora
            });
        });
    

       

        request = $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: {
                action: 'add_to_cart_reservar_horas',
                ciudad: ciudad,
                num_usuarios: num_usuarios,
                fecha_reserva: fecha_reserva,
                horas_seleccionadas: JSON.stringify(horas_seleccionadas)
            },
            beforeSend: function () {
                $('#btn_reservar').attr("disabled", true);
                $('#btn_reservar').attr("value", trad.Un_momento);

                if (request !== null) {
                    request.abort();
                }
            },
            success: function(response) {
                let parsedResponse = JSON.parse(response)
                if(!parsedResponse.error)
                    window.location.href = './finalizar-compra/';
                else{
                    swal("error", parsedResponse.error)
                    $('#btn_reservar').attr("disabled", false);
                    $('#btn_reservar').attr("value", trad.Finalizar_Reserva);
                }
            },
            error: function(e) {
                $('#btn_reservar').attr("disabled", false);
                $('#btn_reservar').attr("value", trad.Finalizar_Reserva);
                swal("error", trad.mensaje_error_servidor)
                console.log(e);
            }
        });
    });




    $('#vaciar_carrito').on('click', function() {
        Swal.fire({
            tittle: trad.volver_a_empezar,
            icon: 'question',
            html: trad.en_el_carrito_tienes_reservas_para + ' ' + ciudad_nombre + '. ' + trad.no_es_posible_mixtas_que_deseas_hacer,
            showCancelButton: true,
            confirmButtonColor: '#292929',
            confirmButtonText: trad.vaciar_carrito,
            cancelmButtonColor: '#fff',
            cancelButtonText: trad.seguir_en + ' ' + ciudad_nombre,
            showClass: {
                popup: 'animated fadeInDown faster',
            },
            hideClass: {
                popup: 'animated fadeOutUp faster',
            },
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?action=reset';
            }
        })
    })


    
    function swal(tipo, mensaje){
        Swal.fire({
            tittle:trad.oh_no,
            icon:tipo,
            html:mensaje,
            confirmButtonColor: '#292929',
            confirmButtonText: trad.cerrar,
            showClass: {
                popup: 'animated fadeInDown faster',
            },
            hideClass: {
                popup: 'animated fadeOutUp faster',
            },
        })
    }

});










jQuery(document).ready(function($) {
    $('#floating-button').hover(
        function() {
            $('.languages').stop().slideDown(300);
        },
        function() {
            $('.languages').stop().slideUp(300);
        }
    );

    function getQueryParams() {
        var queryString = window.location.search.slice(1);
        var obj = {};
        if (queryString) {
            queryString.split('&').forEach(function(pair) {
                var parts = pair.split('=');
                obj[parts[0]] = parts[1] && decodeURIComponent(parts[1]);
            });
        }
        return obj;
    }

    function objectToQueryString(obj) {
        return Object.keys(obj).map(function(key) {
            return encodeURIComponent(key) + '=' + encodeURIComponent(obj[key]);
        }).join('&');
    }

    var queryParams = getQueryParams();

    $('#floating-button li.lang-item a').each(function() {
        var currentHref = $(this).prop("href");
        var separator = currentHref.includes('?') ? '&' : '?'; // Determinar si se debe usar ? o & para añadir parámetros
        $(this).prop("href", currentHref + separator + objectToQueryString(queryParams));
    });

    
    
    window.onpopstate = function(event) {
        location.reload();
    };


});

