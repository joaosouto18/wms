
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
    /**
     * When the page loads, gets all produto_volumes to be displayed.
     */
    "{window} load": function() {
        //checo tipo comercializacao/embalagens
        this.validarEmbalagens();
        //checo tipo comercializacao/volumes
        this.validarVolumes();
        //oculta campo de dias para vencimento
        if ($('#produto-validade').val() == 'S') {
            $('#produto-diasVidaUtil').show();
            $('#produto-diasVidaUtil').parent().show();
        } else if ($('#produto-validade').val() == 'N') {
            $('#produto-diasVidaUtil').hide();
            $('#produto-diasVidaUtil').parent().hide();
        }

        
        //checa quantidade de volumes
        $(".btnSave").off('click').click(function(e) {
            ///checa embalagem e volume
            if(!Wms.Controllers.Produto.prototype.verificarEmbalagemVolume())
                return false;
            
            $('.saveForm').submit();
        });

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
                if ( qtdEmbalagensCadastradas == 0 ) {
                    alert('O produto deve conter pelo menos uma embalagem cadastrada.');
                    return false;
                }  
        
                // verifico se existe embalagem de recebimento
                var qtdEmbalagensRecebimento = 0;
                listaEmbalagens.each(function(i, v) {
                    if( ( this.name.indexOf('isPadrao') != -1 ) && ( this.value == 'S' ) )
                        qtdEmbalagensRecebimento = qtdEmbalagensRecebimento + 1;
                });
        
                // caso sem embalagens
                if ( qtdEmbalagensRecebimento == 0 ) {
                    alert('O produto deve conter AO MENOS uma embalagem cadastrada do tipo recebimento.');
                    return false;   
                }
                // caso a quantidade de volumes cadastradados diferentes da 
                // quantidade de volumes requeridos pelo produto, solicito cadastro
                if( qtdDadosLogisticosCadastrados == 0 ) {
                    alert('Deve haver pelo menos um dado logistico cadastrado para o produto na Aba "Dados Logisticos"');
                    return false;
                }
                
                break;
            case COMPOSTO :
                if ( ( numVolumesProduto <= 1 ) ) {
                    alert('A quantidade de volumes para esse tipo de comercialização deve ser maior do que 1 (um).');
                    $('#produto-numVolumes').focus();
                    return false;
                }
                // caso a quantidade de volumes cadastradados diferentes da 
                // quantidade de volumes requeridos pelo produto, solicito cadastro
                if( qtdVolumesCadastrados != numVolumesProduto ) {
                    alert('O numero de volumes cadastrados (Aba Volumes) divergem do Nº Volumes informado para o produto (Aba Produto)');
                    return false;
                }
                
                if(($('#produto-codigoBarrasBase').val().length > 1) && ($('#produto-codigoBarrasBase').val().length <= 3) ) {
                    alert('O Código de Barra Base deve conter no minimo 3 caracteres.');
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
                        alert('Este Produto contém Código de Barra Base. Não é permitido ter volumes com Código de Barras Automático.');
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

                    if ( numVezesSequencia == 0 ) {
                        alert('O produto tem que ter a sequência ' + i + '/' + numVolumesProduto + '.');
                        return false;   
                    }
                    if ( numVezesSequencia > 1 ) {
                        alert('A sequência ' + i + ' está cadastrada mais de uma vez.');
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
    '#produto-idTipoComercializacao change': function() {        
        this.validarEmbalagens();
        this.validarVolumes();
        this.pesoTotal();
    },

    '#produto-validade change' : function() {
        if ($('#produto-validade').val() == 'S') {
            $('#produto-diasVidaUtil').parent().show();
            $('#produto-diasVidaUtil').show();
        } else if ($('#produto-validade').val() == 'N') {
            $('#produto-diasVidaUtil').parent().hide();
            $('#produto-diasVidaUtil').hide();
        }
    },

    '#ativarDesativar click' : function() {
        if (document.getElementsByName('ativarDesativar').checked == true) {
            alert('abc');
        }
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
                    pesoVolume = parseFloat(this.value.replace('.','').replace(',','.'))
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
                    var pesoVolume = parseFloat($(this).find('.peso').val().replace('.','').replace(',','.'))
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
                var cubagemVolume = 0
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