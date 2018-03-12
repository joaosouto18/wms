var clickSelection=false;
/**
 * @tag controllers, home
 */
$.Controller.extend('Wms.Controllers.Expedicao',
    /* @Static */
    {
        pluginName: 'expedicao'
    },
    /* @Prototype */
    {
        '{window} load' : function() {

            var este = this;
            $('#centrais a').live('click', function() {
                var urlImpressao = $(this).attr('href');
                urlImpressao = urlImpressao+'?'+$('#cargas input:checked').serialize();
                window.location.href = urlImpressao;
                return false;
            });

            /*
             * Exibe/Oculta botão gerar
             * @array checkBoxes de expedições
             */
            $("input[name*='expedicao[]']").live('click', function() {
                $('#gerar').attr('style','display:block');
                $('#modelo-separacao').attr('style','display:block');
                clickSelection=false;
                $("input[name*='expedicao[]']").each(function( index, value ){
                    if ( $(this).prop('checked') ){
                        clickSelection=true;
                    }
                });

                if (clickSelection){
                    $('#gerar').attr('style','display:inline');
                    $('#modelo-separacao').attr('style','height: 26px');
                } else {
                    $('#gerar').attr('style','display:none');
                    $('#modelo-separacao').attr('style','display:none');
                }
            });

            $('#aguarde').attr('style','display:none');
            $("#gerar").live('click', function() {
                    $('#gerar').attr('style','display:none');
                    $('#modelo-separacao').attr('style','display:none');
                    $('#aguarde').attr('style','background-color: lightsteelblue; text-align: center; padding: 5px');
            });

            $("#modelo-separacao").live('click', function() {
                $('#gerar').attr('style','display:none');
                $('#modelo-separacao').attr('style','display:none');
            });

            $('#modelo-separacao').live('click', function () {
                clickSelection=false;
                $("input[name*='expedicao[]']").each(function( index, value ){
                    if ( $(this).prop('checked') ){
                        clickSelection=true;
                    }
                });

                if (clickSelection){
                    var liberado = true;
                    $.ajax({
                        url: URL_BASE + '/expedicao/onda-ressuprimento/modelo-separacao-expedicao-ajax',
                        type: 'post',
                        async: false,
                        dataType: 'html',
                        data: $('#relatorio-picking-listar').serialize()
                    }).success(function (data) {
                        console.log(data);
                        $('#inside-modal-dialog').append(data);
                    });
                } else {
                    alert('Selecione pelo menos uma expedição');
                }

            });

            /*
             * Valida seleção de expedições
             * @array checkBoxes de expedições
             * return action submit / alert
             */
            $("#gerar").live('click', function() {
                este.gerarRessuprimento();
            });

            grade = $("#grade");

            grade.autocomplete({
                source: "/enderecamento/movimentacao/filtrar/idproduto/",
                minLength: 0
            });

            grade.keyup(function(e){
                if ($("#idProduto").val() == '' || $("#id").val() == '') {
                    return false;
                }
                var produtoVal  = $("#idProduto").val();
                if (typeof  produtoVal == 'undefined') {
                    var produtoVal  = $("#id").val();
                }
                grade.autocomplete({
                    source:"/enderecamento/movimentacao/filtrar/idproduto/"+produtoVal,
                    select: function( event, ui ) {
                        $.getJSON("/enderecamento/movimentacao/volumes/idproduto/"+produtoVal+"/grade/"+encodeURIComponent(ui['item']['value']),function(dataReturn){
                            if (dataReturn.length > 0) {
                                var options = '<option value="">Selecione um agrupador de volumes...</option>';
                                for (var i = 0; i < dataReturn.length; i++) {
                                    options += '<option value="' + dataReturn[i].cod + '">' + dataReturn[i].descricao + '</option>';
                                }
                                $('#volumes').html(options);
                                $('#volumes').parent().show();
                            } else {
                                $('#volumes').empty();
                                $('#volumes').parent().hide();
                            }
                        })
                    }
                });
            });
        },

        /*
         * Valida seleção de expedições
         * @array checkBoxes de expedições
         * return action submit / alert
         */
        gerarRessuprimento: function () {
            var este = this;

            if ($("input[name*='expedicao[]']:checked").length > 0){
                var liberado = true;
                $.ajax({
                    url: URL_BASE + '/expedicao/onda-ressuprimento/verificar-expedicoes-processando-ajax',
                    type: 'post',
                    async: false,
                    dataType: 'json',
                    data: $('#relatorio-picking-listar').serialize()
                }).success(function (data) {
                    if (data.status === "Ok") {
                        liberado = true;
                    } else if (data.status === "Error") {
                        este.dialogAlert(data.msg, function(){
                            window.location = URL_BASE + "/expedicao/onda-ressuprimento";
                        });
                        liberado = false;
                        este.processoCancelado();
                    }
                });
                if (liberado) {
                    var msgs = null;
                    var expedicoes = null;
                    $.ajax({
                        url: URL_BASE + "/expedicao/onda-ressuprimento/gerar",
                        type: 'post',
                        async: false,
                        dataType: 'json',
                        data: $('#relatorio-picking-listar').serialize()
                    }).success(function (data) {
                        if (data.status === "Ok") {
                            expedicoes = data.expedicoes;
                            msgs = data.response;
                        } else if (data.status === "Error") {
                            msgs = data.response;
                        }
                    });

                    if (expedicoes !== null) {
                        este.dialogConfirm(
                            "Deseja já gerar e imprimir os mapas e etiquetas destas expedições ressupridas?",
                            expedicoes,
                            este.callback("selectExpToPrint")
                        )
                    }

                    este.dispararMultiMsgs(msgs);
                    //$('#relatorio-picking-listar').submit();
                }
            } else {
                alert('Selecione pelo menos uma expedição');
            }
        },

        processoCancelado: function () {
            $('#aguarde').attr('style','display:none');
            $("input[name*='expedicao[]']").each(function( index, value ){
                if ( $(this).prop('checked') ){
                    $(this).prop('checked', false);
                }
            });
        },

        selectExpToPrint: function (expedicoes) {
            var divExpedicoes = '';
            $.each(expedicoes, function (k,v) {
                console.log(divExpedicoes);
                divExpedicoes = divExpedicoes.concat('<b style="padding: 12px;"><a href="' + URL_MODULO + '/etiqueta/index/id/' + v + '" type="button" class="dialogAjax pdf" target="_self">' + v + '</a></b>');
            });
            var htmlBody =
                '<h2 style="text-align: center; margin: 5px; margin-bottom: 0px; font-size: 14px;">' +
                '    Selecione uma das expedições para imprimir' +
                '</h2>' +
                '<div class="padding-top">' +
                '    <fieldset id="fieldset-identification" rowspan="2">' +
                '        <legend>Expedições</legend>' +
                '        <div id="div-fieldset-expedicoes">' + divExpedicoes + '</div>' +
                '    </fieldset>' +
                '</div>';
            console.log(htmlBody);
            $.wmsDialogModal({
                title: "---  Sistema  ---"
            }, htmlBody)
        },

        dispararMultiMsgs : function (msgs) {
            var este = this;
            $.each(msgs, function (k,v) {
                este.dialogAlert(v)
            });
        },

        dialogAlert: function (msg, funct) {
            $.wmsDialogAlert({
                title: 'Alerta',
                msg: msg
            }, funct);
        },

        dialogConfirm: function (msg, params, confirmFunction) {
            $.wmsDialogConfirm({
                title: "---  Sistema  ---",
                msg: msg
            }, confirmFunction, params)
        }
    });