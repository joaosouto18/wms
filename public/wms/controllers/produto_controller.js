
/**
 * @tag controllers, home
 */
$.Controller.extend('Wms.Controllers.Produto',
    /* @Static */
    {
        pluginName: 'produto'
    },
    /* @Prototype */
    {
        dialogAlert: function ( msg ) {
            $.wmsDialogAlert({
                title: 'Alerta',
                msg: msg,
                height: 150,
                resizable: false
            });
        },

        /**
         * When the page loads, gets all produto_volumes to be displayed.
         */
        "{window} load": function() {

            //checo tipo comercializacao/embalagens
            this.validarEmbalagens();
            //checo tipo comercializacao/volumes
            this.validarVolumes();

            this.checkShowValidade();
            this.checkShowPesoVariavel();
            var este = this;

            $('#produto-diasVidaUtil').parent().append($('#produto-percentMinVidaUtil')).append(' %');

            this.changePercent($('#produto-diasVidaUtilMaximo').val(), $('#produto-diasVidaUtil').val());

            //checa quantidade de volumes
            $(".btnSave").off('click').click(function(e) {
                $('form input').each(function(e){
                    $(this).removeClass('required');
                });

                // Trava retirada para fazer nova trava de proporcionalidade
                // var unitizadores = [];
                // var invalido = false;
                //
                // $("select.unitizador option:selected").each(function () {
                //
                //     var option = $(this);
                //
                //     var result = unitizadores.find(function(i) {
                //         return option.val() === i;
                //     });
                //
                //     if (result === undefined) {
                //         unitizadores.push($(this).val());
                //     }
                //     else {
                //         invalido = true;
                //     }
                //
                // });
                //
                // if (invalido) {
                //     $.wmsDialogAlert({
                //         title: 'Processo cancelado',
                //         msg: "Existem normas com o mesmo unitizador. " +
                //         "<br />Altere ou remova uma das normas e tente novamente!",
                //         height: 140,
                //         resizable: false
                //     });
                //     return false;
                // }

                ///checa embalagem e volume
                if(!este.verificarEmbalagemVolume())
                    return false;

                if(!este.verificarValidade())
                    return false;

                if(!este.verificarNormaPaletizacaoProdutoDadoLogistico()){
                        $.wmsDialogAlert({
                            title: 'Processo cancelado',
                            msg: "Existe alguma norma de paletização com mais de um grupo de dado logistico.",
                            height: 140,
                            resizable: false
                        });
                    return false;
                }

                if(!este.verificarNormaPaletizacao()){
                    $.wmsDialogAlert({
                        title: 'Processo cancelado',
                        msg: "Existem normas com o mesmo unitizador e quantidade de itens diferentes.",
                        height: 140,
                        resizable: false
                    });
                    return false;
                }

                $('.saveForm').submit();
            });

        },
        '#produto-pVariavel change' : function() {
            this.checkShowPesoVariavel();
        },

        '#produto-indFracionavel change' : function() {
            this.checkShowUnidFracionavel();
        },

        '#produto-unidFracao change' : function() {
            this.checkShowUnidFracionavel();
        },

        '#produto-diasVidaUtilMaximo change' : function(e) {
            var max = e.val();
            var min = $('#produto-diasVidaUtil').val();
            this.changePercent(max, min);
        },

        '#produto-diasVidaUtil change' : function(e) {
            var max = $('#produto-diasVidaUtilMaximo').val();
            var min = e.val();
            this.changePercent(max, min);
        },

        '#produto-percentMinVidaUtil change' : function(e) {
            var total = ($('#produto-diasVidaUtilMaximo').val() * e.val().replace(',', '.')) / 100;
            $('#produto-diasVidaUtil').val(Math.floor(total));
        },

        '#produto-percTolerancia blur' : function() {
            var pesoTotal = parseFloat($("#produto-peso").val());
            var percTolerancia = parseFloat($("#produto-percTolerancia").val());
            var pVariavel = pesoTotal*percTolerancia/100;

            $("#produto-toleranciaNominal").val(pVariavel);
        },

        checkShowValidade: function () {
            var inptDiasVidaUtil = $('#produto-diasVidaUtil');
            var inptDiasVidaUtilMaximo = $('#produto-diasVidaUtilMaximo');
            var inptPercentMinVidaUtil = $('#produto-percentMinVidaUtil');
            if ($('#produto-validade').val() === 'S') {
                inptDiasVidaUtil.show();
                inptDiasVidaUtil.parent().show();
                inptDiasVidaUtil.addClass('required');
                inptDiasVidaUtilMaximo.parent().show();
                inptDiasVidaUtilMaximo.show();
                inptDiasVidaUtilMaximo.addClass('required');
                inptPercentMinVidaUtil.parent().show();
                inptPercentMinVidaUtil.show();
            } else {
                inptDiasVidaUtil.hide();
                inptDiasVidaUtil.parent().hide();
                inptDiasVidaUtilMaximo.parent().hide();
                inptDiasVidaUtilMaximo.hide();
                inptPercentMinVidaUtil.parent().hide();
                inptPercentMinVidaUtil.hide();
            }
        },

        checkShowPesoVariavel: function () {
            var inptPercTolerancia = $('#produto-percTolerancia');
            var inptToleranciaNominal = $('#produto-toleranciaNominal');
            var inptPesoVar = $('#produto-pVariavel');
            if (inptPesoVar.val() === 'S') {
                if ($("#produto-indFracionavel").val() === "S") {
                    this.dialogAlert("Este produto tem unidade fracionável, não pode ter peso variavel no momento.");
                    inptPesoVar.prop('selectedIndex',1);
                    return false;
                }
                inptPercTolerancia.parent().show();
                inptPercTolerancia.show();
                inptToleranciaNominal.parent().show();
                inptToleranciaNominal.show();
            } else {
                inptPercTolerancia.parent().hide();
                inptPercTolerancia.hide();
                inptToleranciaNominal.parent().hide();
                inptToleranciaNominal.hide();
            }
        },

        checkShowUnidFracionavel: function () {
            var inptUndFraca= $('#produto-unidFracao');
            var embFracionavel = null;
            var inptIndFracionavel = $('#produto-indFracionavel');
            var este = this;
            if (inptIndFracionavel.val() === 'S'){
                if ($("#produto-idTipoComercializacao").val() !== "1") {
                    this.dialogAlert("Apenas produtos unitários podem utilizar esta opção.");
                    inptIndFracionavel.prop('selectedIndex',1);
                    return false;
                }
                if ($("#produto-pVariavel").val() === 'S') {
                    this.dialogAlert("Produtos de peso variável não podem utilizar esta opção.");
                    inptIndFracionavel.prop('selectedIndex',1);
                    return false;
                }
                inptUndFraca.parent().show();
                inptUndFraca.show();
                inptUndFraca.addClass('required');
                $("#embalagem-embExpDefault").show();
                if (inptUndFraca.val() !== "") {
                    var produto = {
                        id: $("#produto-id").val(),
                        grade: $("#produto-grade").val(),
                        unidFracao: inptUndFraca.val()
                    };
                    embFracionavel = Wms.Controllers.ProdutoEmbalagem.prototype.checkExistEmbFracionavel();
                    if (!embFracionavel) {
                        Wms.Controllers.ProdutoEmbalagem.prototype.createEmbFracionavel(produto)
                    } else {
                        Wms.Controllers.ProdutoEmbalagem.prototype.updateEmbFracionavel(produto)
                    }
                }
            } else {
                embFracionavel = Wms.Controllers.ProdutoEmbalagem.prototype.checkExistEmbFracionavel();
                if (embFracionavel) {
                    Wms.Controllers.ProdutoEmbalagem.prototype.removeEmbFracionavelDefault(
                        este.callback('hideUnidFracionavel')
                    );
                } else if (!embFracionavel) {
                    este.hideUnidFracionavel(true)
                } else {
                    inptIndFracionavel.prop('selectedIndex',0);
                }
            }
        },

        hideUnidFracionavel: function ( result ) {
            if (result) {
                var inptUndFraca = $('#produto-unidFracao');
                inptUndFraca.parent().hide();
                $("#embalagem-embExpDefault").hide().prop('selectedIndex', -1);
                inptUndFraca.hide().prop('selectedIndex', 0);
                inptUndFraca.removeClass('required');
                inptUndFraca.removeClass('invalid');
            } else {
                $('#produto-indFracionavel').prop('selectedIndex',0);
            }
        },

        verificarValidade: function () {
            var este = this;
            if ($('#produto-validade').val() == 'S') {
                if($('#produto-diasVidaUtil').val() == '' ||
                    $('#produto-diasVidaUtilMaximo').val() == '' ||
                    $('#produto-diasVidaUtilMaximo').val() == 0){
                    este.dialogAlert('Preencha os campos relacionados a validade.');
                    $('#produto-diasVidaUtilMaximo').focus();
                    return false;
                }else{
                    return true
                }
            }else{
                return true
            }
        },

        verificarNormaPaletizacao: function () {
            var ret = true;
            var array = [];
            $('.grupoDadosLogisticos').each(function () {
                if(array[$(this).find('.unitizador').val()]){
                    if(array[$(this).find('.unitizador').val()] != $(this).find('#normaPaletizacao-numNorma').val() * $(this).find('.qtdEmbalagem').val()){
                        ret = false;
                    }
                }else {
                    array[$(this).find('.unitizador').val()] = $(this).find('#normaPaletizacao-numNorma').val() * $(this).find('.qtdEmbalagem').val();
                }
            });
            return ret;
        },

        verificarNormaPaletizacaoProdutoDadoLogistico: function () {
            var ret = true;
            $('.grupoDadosLogisticos').each(function () {
                if($(this).find('.produto_dado_logistico').length > 1){
                    ret = false;
                }
            });
            return ret;
        },

        changePercent: function (max, min) {
            if(min != '' && max != '' && min > 0 && max > 0) {
                var percentual = (min * 100) / max;
                $('#produto-percentMinVidaUtil').val(percentual.toFixed(2).replace('.', ','));
            }
        },

        /**
         * Valida os formularios de cadastro das Embalagens e Volumes
         */
        verificarEmbalagemVolume: function() {
            // constantes tipo comercializacao
            var UNITARIO = 1;
            var COMPOSTO = 2;
            var KIT = 3;

            // variaveis
            var idTipoComercializacao = parseInt($('#produto-idTipoComercializacao').val());
            var qtdVolumesCadastrados = $('.produto_volume').size();
            var qtdDadosLogisticosCadastrados = $('.produto_dado_logistico').size();
            var qtdEmbalagensCadastradas = $('.produto_embalagem').size();
            var numVolumesProduto = parseInt($('#produto-numVolumes').val());
            var listaEmbalagens = $('#div-lista-embalagens input[type="hidden"]');
            var CBInternos = $('.CBInterno');
            var codigoBarrasBase = $('#produto-codigoBarrasBase').val();

            // checando embalagens
            switch( idTipoComercializacao ) {
                case UNITARIO :
                    if ( qtdEmbalagensCadastradas === 0 ) {
                        this.dialogAlert('O produto deve conter pelo menos uma embalagem cadastrada.');
                        return false;
                    }

                    // verifico se existe embalagem de recebimento
                    var qtdEmbalagensRecebimento = 0;
                    listaEmbalagens.each(function(i, v) {
                        if( ( this.name.indexOf('isPadrao') !== -1 ) && ( this.value === 'S' ) )
                            qtdEmbalagensRecebimento = qtdEmbalagensRecebimento + 1;
                    });

                    // caso sem embalagens
                    if ( qtdEmbalagensRecebimento === 0 ) {
                        this.dialogAlert('O produto deve conter UMA embalagem cadastrada do tipo recebimento.');
                        return false;
                    }
                    // caso a quantidade de volumes cadastradados diferentes da
                    // quantidade de volumes requeridos pelo produto, solicito cadastro
                    if( qtdDadosLogisticosCadastrados === 0 ) {
                        this.dialogAlert('Deve haver pelo menos um dado logistico cadastrado para o produto na Aba "Dados Logisticos"');
                        return false;
                    }

                    break;
                case COMPOSTO :
                    if ( ( numVolumesProduto <= 1 ) ) {
                        this.dialogAlert('A quantidade de volumes para esse tipo de comercialização deve ser maior do que 1 (um).');
                        $('#produto-numVolumes').focus();
                        return false;
                    }
                    // caso a quantidade de volumes cadastradados diferentes da
                    // quantidade de volumes requeridos pelo produto, solicito cadastro
                    if( qtdVolumesCadastrados !== numVolumesProduto ) {
                        this.dialogAlert('O numero de volumes cadastrados (Aba Volumes) divergem do Nº Volumes informado para o produto (Aba Produto)');
                        return false;
                    }

                    if(($('#produto-codigoBarrasBase').val().length > 1) && ($('#produto-codigoBarrasBase').val().length <= 3) ) {
                        this.dialogAlert('O Código de Barra Base deve conter no minimo 3 caracteres.');
                        $('#produto-codigoBarrasBase').focus();
                        return false;
                    }

                    if(codigoBarrasBase != ''){

                        //verifico se existe volumes neste produto com CBInterno marcado SIM e o produto tem codigo de barra base
                        var numVezesCBInterno = 0;
                        CBInternos.each(function(){
                            if ( this.value == 'S'){
                                numVezesCBInterno++;
                            }
                        });

                        if ( numVezesCBInterno > 0){
                            this.dialogAlert('Este Produto contém Código de Barra Base. Não é permitido ter volumes com Código de Barras Automático.');
                            return false;
                        }
                    }

                    // verifico se existe volumes com a mesma sequencia
                    var codigoSequencial = $('.codigoSequencial');

                    for (i = 1; i <= numVolumesProduto ; i++) {
                        var numVezesSequencia = 0;

                        codigoSequencial.each(function(){
                            if ( this.value == i )
                                numVezesSequencia++;
                        });

                        if ( numVezesSequencia === 0 ) {
                            this.dialogAlert('O produto tem que ter a sequência ' + i + '/' + numVolumesProduto + '.');
                            return false;
                        }
                        if ( numVezesSequencia > 1 ) {
                            this.dialogAlert('A sequência ' + i + ' está cadastrada mais de uma vez.');
                            return false;
                        }
                    }

                    break;
            }

            return true;
        },
        /**
         * Valida acoes ao carregar a tela
         */
        validarEmbalagens: function() {
            // constantes tipo comercializacao
            var UNITARIO = 1;
            var COMPOSTO = 2;
            var KIT = 3;

            // variaveis
            var idTipoComercializacao = parseInt($('#produto-idTipoComercializacao').val());
            var tabEmbalagem = $('#fieldset-embalagem').parents('.ui-tabs-panel');
            var idPanel = tabEmbalagem.attr('id');
            var tab = $('a[href="#' + idPanel + '"]').parents('li.ui-tabs-nav-item');

            // caso tipo comercializacao composto, removo opcao de embalagem de expedicao
            switch(idTipoComercializacao) {
                case COMPOSTO:
                    $("#embalagem-isPadrao option[value='N']").remove();

                    tabEmbalagem.css('display','none');
                    tab.css('display','none');

                    break;
                case UNITARIO:

                    if ( $("#embalagem-isPadrao option[value='N']").size() == 0 )
                        $("#embalagem-isPadrao").append('<option value="N">NÃO</option>');

                    tabEmbalagem.css('display','block');
                    tab.css('display','block');
                    break;
                case KIT:
                    if ( $("#embalagem-isPadrao option[value='N']").size() == 0 )
                        $("#embalagem-isPadrao").append('<option value="N">NÃO</option>');

                    tabEmbalagem.css('display','none');
                    tab.css('display','none');
                    break;
            }
        },
        /**
         *
         */
        validarVolumes: function() {
            // constantes tipo comercializacao
            var UNITARIO = 1;
            var COMPOSTO = 2;
            var KIT = 3;

            // tipo comercializacao
            var idTipoComercializacao = parseInt($('#produto-idTipoComercializacao').val());

            var tabVolume = $('#fieldset-volume').parents('.ui-tabs-panel');
            var linkTabVolume = $('a[href="#' + tabVolume.attr('id') + '"]').parents('li.ui-tabs-nav-item');

            var tabDadoLogistico = $('#fieldset-dado-logistico').parents('.ui-tabs-panel');
            var linkTabDadoLogistico = $('a[href="#' + tabDadoLogistico.attr('id') + '"]').parents('li.ui-tabs-nav-item');

            switch(idTipoComercializacao) {
                case COMPOSTO:
                    $('#produto-numVolumesProduto').attr('readonly', false);

                    // habilito Qtd. Volumes
                    $('#produto-numVolumes').parents('div.field').css('display', 'block');
                    // habilito CBB
                    $('#produto-codigoBarrasBase').parents('div.field').css('display', 'block');

                    // controles de exibicao das tabs
                    tabVolume.css('display','block');
                    linkTabVolume.css('display','block');
                    tabDadoLogistico.css('display','none');
                    linkTabDadoLogistico.css('display','none');
                    break;
                case UNITARIO:
                    $('#produto-numVolumesProduto').val(1).attr('readonly', true);

                    //nao tenho qtd. volumes quando unitario
                    $('#produto-numVolumes').parents('div.field').css('display', 'none').val('');
                    // n tenho CBB qndo unitario
                    $('#produto-codigoBarrasBase').parents('div.field').css('display', 'none').val('');

                    // controles de exibicao das tabs
                    tabVolume.css('display','none');
                    linkTabVolume.css('display','none');
                    tabDadoLogistico.css('display','block');
                    linkTabDadoLogistico.css('display','block');
                    break;
                case KIT:
                    $('#produto-numVolumesProduto').attr('readonly', false);

                    //nao tenho qtd. volumes quando kit
                    $('#produto-numVolumes').parents('div.field').css('display', 'none').val('');
                    // habilito CBB
                    $('#produto-codigoBarrasBase').parents('div.field').css('display', 'block');

                    // controles de exibicao das tabs
                    tabVolume.css('display','none');
                    linkTabVolume.css('display','none');
                    tabDadoLogistico.css('display','none');
                    linkTabDadoLogistico.css('display','none');
                    break;
            }
        },

        validarUnidFracionavel: function (el) {
            if (el.val() !== '1') {
                var produto = {
                    id: $("#produto-id").val(),
                    grade: $("#produto-grade").val(),
                    unidFracao: $('#produto-unidFracao').val()
                };
                var embFracionavel = Wms.Controllers.ProdutoEmbalagem.prototype.checkExistEmbFracionavel(produto);
                if (embFracionavel){
                    this.dialogAlert("Este produto tem embalagem de unidade fracionável. <br /> Remova ela antes de alterar esta opção");
                    $("#produto-idTipoComercializacao").prop('selectedIndex', 0);
                    return false;
                }
            }

            this.validarEmbalagens();
            this.validarVolumes();
            this.pesoTotal();

        },

        /**
         * Checa o valor digitado na quantidade de volumes
         */
        '#produto-numVolumesProduto change': function(el, ev) {
            if($(el).val() >= 15)
                alert('Número de volumes muito alto. \n Está certo sobre esta informação?');
        },

        /**
         * Ao alterar o tipo de comercializacao do produto verifica dados novamente
         */
        '#produto-idTipoComercializacao change': function(el) {
            this.validarUnidFracionavel(el);
        },

        '#produto-validade change' : function() {
            this.checkShowValidade();
        },

        /**
         * Calcula o peso de todos os volumes e altera na aba produto
         */
        pesoTotal: function() {
            // constantes tipo comercializacao
            var UNITARIO = 1;
            var COMPOSTO = 2;
            var KIT = 3;

            // tipo comercializacao
            var idTipoComercializacao = parseInt($('#produto-idTipoComercializacao').val());

            switch(idTipoComercializacao) {
                case COMPOSTO:
                    //caso composto, soma todos os pesos dos volumes
                    var inputsPeso = $('.produto_volume input.peso');
                    var pesoTotal = 0;
                    var pesoVolume = 0
                    inputsPeso.each(function(i, v) {
                        pesoVolume = parseFloat(this.value.replace('.','').replace(',','.'));
                        pesoTotal = pesoTotal + pesoVolume;
                    });

                    pesoTotal = Wms.Controllers.CalculoMedida.prototype.formatMoney(parseFloat(pesoTotal.toString().replace(',', '.')).toFixed(3), 3, ',', '.');
                    $('#produto-peso').val(pesoTotal);

                    break;

                case UNITARIO:
                    //caso unitario, soma apenas o peso dos dados logisticos com embalagem de recebimento
                    var pesoTotal = 0;
                    var blocosDadosLogisticos = $('div.produto_dado_logistico');

                    blocosDadosLogisticos.each( function() {
                        var idEmbalagem = $(this).find('.idEmbalagem').val();
                        var pesoVolume = parseFloat($(this).find('.peso').val().replace('.','').replace(',','.'));
                        var idEmbalagemRecebimento = Wms.Controllers.ProdutoEmbalagem.prototype.buscarEmbalagemRecebimento();

                        if ( idEmbalagem == idEmbalagemRecebimento ) {
                            pesoTotal = pesoTotal + pesoVolume;
                        }
                    });

                    pesoTotal = Wms.Controllers.CalculoMedida.prototype.formatMoney(parseFloat(pesoTotal.toString().replace(',', '.')).toFixed(3), 3, ',', '.');
                    $('#produto-peso').val(pesoTotal);

                    break;

                case KIT:
                    $('#produto-peso').val('0,000');
                    break;
            }
        },

        /**
         * Calcula a cubagem de todos os volumes e altera na aba produto
         */
        cubagemTotal: function() {
            // constantes tipo comercializacao
            var UNITARIO = 1;
            var COMPOSTO = 2;
            var KIT = 3;

            // tipo comercializacao
            var idTipoComercializacao = parseInt($('#produto-idTipoComercializacao').val());

            switch(idTipoComercializacao) {
                case COMPOSTO:
                    //caso composto, soma todas as cubagens dos volumes
                    var inputsCubagem = $('.produto_volume input.cubagem');
                    var cubagemTotal = 0;
                    var cubagemVolume = 0;
                    inputsCubagem.each(function(i, v) {
                        cubagemVolume = parseFloat(this.value.replace('.','').replace(',','.'))
                        cubagemTotal = cubagemTotal + cubagemVolume;
                    });

                    cubagemTotal = Wms.Controllers.CalculoMedida.prototype.formatMoney(parseFloat(cubagemTotal.toString().replace(',', '.')).toFixed(4), 4, ',', '.');
                    $('#produto-cubagem').val(cubagemTotal);
                    break;

                case UNITARIO:
                    //caso unitario, soma apenas a cubagem dos dados logisticos com embalagem de recebimento
                    var cubagemTotal = 0;
                    var blocosDadosLogisticos = $('div.produto_dado_logistico');

                    blocosDadosLogisticos.each( function() {
                        var idEmbalagem = $(this).find('.idEmbalagem').val();
                        var cubagemVolume = parseFloat($(this).find('.cubagem').val().replace('.','').replace(',','.'))
                        var idEmbalagemRecebimento = Wms.Controllers.ProdutoEmbalagem.prototype.buscarEmbalagemRecebimento();

                        if ( idEmbalagem == idEmbalagemRecebimento ) {
                            cubagemTotal = cubagemTotal + cubagemVolume;
                        }
                    });

                    cubagemTotal = Wms.Controllers.CalculoMedida.prototype.formatMoney(parseFloat(cubagemTotal.toString().replace(',', '.')).toFixed(4), 4, ',', '.');
                    $('#produto-cubagem').val(cubagemTotal);

                    break;

                case KIT:
                    $('#produto-cubagem').val('0,0000');
                    break;
            }
        },

        /**
         *
         */
        '#produto-numVolumes change' : function () {
            // gera cod barra base do volume
            Wms.Controllers.ProdutoVolume.prototype.gerarCodBarraBase();

            if( $.trim($('#produto-codigoBarrasBase').val()) != ''){
                // atualiza cod barra base do volume
                this.atualizarCodBarraBase();
            }
        },

        /**
         * Chama metodo de geracao de codigo de barra baseado no codigo de barra base
         */
        '#produto-codigoBarrasBase change' : function () {
            // gera cod barra base do volume
            Wms.Controllers.ProdutoVolume.prototype.gerarCodBarraBase();

            //if( $.trim($('#produto-codigoBarrasBase').val()) != ''){
            // atualiza cod barra base do volume
            this.atualizarCodBarraBase();
            //}
        },

        /**
         * Atualiza o codigo de barras base nos volumes cadastrados e adicionados
         */
        atualizarCodBarraBase: function(){
            //atualizo o codigo de barras dos volumes adicionados
            var codigoBarrasBase = $('#produto-codigoBarrasBase').val();
            var volumes = $('.produto_volume');
            var numVolumes = $('#produto-numVolumes').val();

            if(numVolumes.length == 1){
                numVolumes = "0" + numVolumes;
            }

            volumes.each(function(){
                var codigoBarras = $(this).find('.codigoBarras');
                var codigoSequencial = $(this).find('.codigoSequencial').val();
                var codigoBarrasExibicao = $(this).find('.codigoBarrasExibicao');

                if(codigoSequencial.length == 1){
                    codigoSequencial = "0" + codigoSequencial;
                }

                codigoBarras.val(codigoBarrasBase + codigoSequencial + numVolumes);
                codigoBarrasExibicao.html(codigoBarrasBase + codigoSequencial + numVolumes);

            });
        }

    });