/**
 * Controller para administrar Embalagens dos produtos
 */
$.Controller.extend('Wms.Controllers.ProdutoEmbalagem',
    /* @Static */
    {
        pluginName: 'produtoEmbalagem'

    },
    /* @Prototype */
    {
        /**
         * Ações à serem executadas ao carregar documento
         */
        "{window} load": function(){
            // verifica se lista de embalagens ja foi carregada
            if( !$("#div-lista-embalagens").length ) {
                $("#fieldset-embalagens-cadastradas").append($('<div/>').attr('id','div-lista-embalagens'));
                var idProduto = $('#embalagem-idProduto').val();
                var grade = $('#embalagem-grade').val();
                if ( idProduto != '' ) {
                    Wms.Models.ProdutoEmbalagem.findAll({
                        idProduto:idProduto,
                        grade:grade
                    }, this.callback('list'));
                }
            }
        },

        /**
         *
         * @param {jQuery} el A jQuery wrapped element.
         * @param {Event} ev A jQuery event whose default action is prevented.
         */
        "#ativarDesativar click" : function(el,ev) {
            var check = $(el).parent('div').find('.ativarDesativar');
            var date = $(el).parent('div').find('.dataInativacao');
            var div = $(el).parent('div').parent('td');

            if (check.is(":checked") == true) {
                if (date.text() == "EMB. ATIVA") {
                    var today = new Date();
                    var dd = today.getDate();
                    var mm = today.getMonth()+1;
                    var yyyy = today.getFullYear();

                    if(dd<10){
                        dd='0'+dd
                    }
                    if(mm<10){
                        mm='0'+mm
                    }
                    var today = dd+'/'+mm+'/'+yyyy;

                    date.text(today);
                }
                div.css("color","red");
            } else {
                date.text("EMB. ATIVA");
                div.css("color","green");

            }
        },

        /**
         * Responds to the create form being submitted by creating a new Wms.Models.ProdutoEmbalagem.
         *
         * @param {jQuery} el A jQuery wrapped element.
         * @param {Event} ev A jQuery event whose default action is prevented.
         */
        '#btn-salvar-embalagem click': function(el, ev) {

            var inputAcao = $('#embalagem-acao').val();
            var valores = $('#fieldset-embalagem').formParams(false).embalagem;
            var id = $("#fieldset-embalagem #embalagem-id").val();
            var este = this;

            if($('#embalagem-descricao').val() == "") {
                alert('Preencha a Descrição.');
                $('#embalagem-descricao').focus();
                return false;
            }
            if(($('#embalagem-quantidade').val() == "") || ($('#embalagem-quantidade').val() == 0) ) {
                alert('Preencha a Quantidade.');
                $('#embalagem-quantidade').focus();
                return false;
            }
            if($('#embalagem-isPadrao').val() == "") {
                alert('Preencha o Padrão.');
                $('#embalagem-isPadrao').focus();
                return false;
            }

            if (($('#embalagem-CBInterno').val() == "N") && ($('#embalagem-codigoBarras').val() == "")) {
                alert('Preencha o Código de Barras.');
                $('#embalagem-codigoBarras').focus();
                return false;
            }

            if(!this.verificarEmbalagemRecebimento(id, valores)){
                return false;
            }

            $.ajax({
                url: URL_MODULO + '/produto/verificar-parametro-codigo-barras-ajax',
                type: 'post',
                dataType: 'json',
                success: function (data) {
                    if (data == 'S') {
                        alert("Não é possível adicionar nova embalagem com parametro de código de barras desativado");
                        return false;
                    } else {
                        este.verificarCodigoBarras();
                    }
                }
            });

            ev.preventDefault();

        },

        salvarDadosEmbalagem:function() {
            var id = $("#fieldset-embalagem #embalagem-id").val();
            var valores = $('#fieldset-embalagem').formParams(false).embalagem;
            valores.lblIsPadrao = $('#fieldset-embalagem #embalagem-isPadrao option:selected').text();
            valores.lblCBInterno = $('#fieldset-embalagem #embalagem-CBInterno option:selected').text();
            valores.lblImprimirCB = $('#fieldset-embalagem #embalagem-imprimirCB option:selected').text();
            valores.lblEmbalado = $('#fieldset-embalagem #embalagem-embalado option:selected').text();
            valores.dataInativacao = 'EMB. ATIVA';

            if (id != '') {
                valores.acao = id.indexOf('-new') == -1 ? 'alterar' : 'incluir';

                this.show(new Wms.Models.ProdutoEmbalagem(valores));
            } else {
                var d = new Date();
                valores.id = d.getTime()+ '-new';
                valores.acao = 'incluir';
                $('#div-lista-embalagens').append( this.view("show", new Wms.Models.ProdutoEmbalagem(valores)) );
            }

            // limpo form
            this.resetarForm();
            // carregar embalagens nos dados logisticos
            this.carregarSelectEmbalagens();
        },

        /**
         * Creates and places the edit interface.
         * @param {jQuery} el The produto_embalagem's edit link element.
         */
        '.btn-editar-embalagem click': function( el, ev ){
            $.ajax({
                url: URL_MODULO + '/produto/verificar-parametro-codigo-barras-ajax',
                type: 'post',
                dataType: 'json',
                success: function (data) {
                    alert('abc');
                    if (data === 'S') {
                        $('#fieldset-embalagem #embalagem-codigoBarras').attr("disabled", true);
                    }
                }
            });

            ev.stopPropagation();
            var produto_embalagem = el.closest('.produto_embalagem').model();

            // campos da embalagem
            var inputsEmbalagem = $('#div-lista-embalagens input');
            // controle da quantidade de embalagens do tipo recebimento
            var qtdEmbalagensRecebimento = 0;
            // controle da quantidade de embalagens do tipo expedicao
            var qtdEmbalagensExpedicao = 0;

            // verifico se existe embalagem de recebimento
            inputsEmbalagem.each(function(i, v) {

                if ( this.className == 'isPadrao' ) {
                    if ( this.value == 'S' ) {
                        // incremento a qtd de embalagens de recebimento cadastradas
                        qtdEmbalagensRecebimento = qtdEmbalagensRecebimento + 1;
                    }
                    else{
                        // incremento a qtd de embalagens de expedicao cadastradas
                        qtdEmbalagensExpedicao = qtdEmbalagensExpedicao + 1;
                    }
                }
            });

            $('#fieldset-embalagem #embalagem-quantidade').attr('disabled', false);
            //caso embalagem de recebimento, não pode alterar a quantidade
            if ((produto_embalagem.isPadrao == 'S') && ( qtdEmbalagensExpedicao > 0 )){
                $('#fieldset-embalagem #embalagem-quantidade').attr('disabled', true);
            }

            // altera informacao
            $('#fieldset-embalagem legend').html('Editando embalagem');
            $('#fieldset-embalagem #btn-salvar-embalagem').val('Atualizar');
            $('#embalagem-acao').val('alterar');
            // carrega dados
            $('#fieldset-embalagem #embalagem-id').val(produto_embalagem.id);
            $('#fieldset-embalagem #embalagem-descricao').val(produto_embalagem.descricao);
            $('#fieldset-embalagem #embalagem-quantidade').val(produto_embalagem.quantidade);
            $('#fieldset-embalagem #embalagem-isPadrao').val(produto_embalagem.isPadrao);
            $('#fieldset-embalagem #embalagem-CBInterno').val(produto_embalagem.CBInterno);
            $('#fieldset-embalagem #embalagem-imprimirCB').val(produto_embalagem.imprimirCB);
            $('#fieldset-embalagem #embalagem-codigoBarras').val(produto_embalagem.codigoBarras);
            $('#fieldset-embalagem #embalagem-codigoBarrasAntigo').val(produto_embalagem.codigoBarras);
            $('#fieldset-embalagem #embalagem-endereco').val(produto_embalagem.endereco);
            $('#fieldset-embalagem #embalagem-enderecoAntigo').val(produto_embalagem.endereco);
            $('#fieldset-embalagem #embalagem-embalado').val(produto_embalagem.embalado);
            $('#fieldset-embalagem #embalagem-capacidadePicking').val(produto_embalagem.capacidadePicking);
            $('#fieldset-embalagem #embalagem-pontoReposicao').val(produto_embalagem.pontoReposicao);

            // checa opcoes de Codigo de Barras Interno
            this.checarCBInterno();

        },

        /**
         * Handle's clicking on a produto_embalagem's destroy link.
         */
        '.btn-excluir-embalagem click': function(el, ev) {

            var model = el.closest('.produto_embalagem').model();
            var id = model.id.toString();

            //evita a propagação do click para a div
            ev.stopPropagation();

            //Verifica se existe dados logisticos com esta embalagem
            var inputsEmbalagem = $('.produto_dado_logistico input.idEmbalagem');
            var qtdEmbalagens = 0;
            inputsEmbalagem.each(function() {
                if(this.value == id) {
                    qtdEmbalagens = qtdEmbalagens + 1;
                }
            });

            if(qtdEmbalagens != 0){
                alert('Não é possível excluir esta embalagem. \nRemova os dados logísticos cadastrados com ela.');
                return false;
            }

            if( confirm("Tem certeza que deseja excluir esta embalagem?") ) {
                var produto_embalagem = el.closest('.produto_embalagem').model();
                $('#fieldset-embalagem #embalagem-enderecoAntigo').val(produto_embalagem.endereco);
                var enderecoAntigo = $('#embalagem-enderecoAntigo').val();
                var idProduto = $('#embalagem-idProduto').val();
                var grade = $('#embalagem-grade').val();
                var este = this;

                var isPadrao = $(el).parent('div').find('.isPadrao').val();
                // caso seja uma embalagem de recebimento
                if ( isPadrao == 'S' ) {
                    // embalagens
                    var inputsIsPadrao = $('#div-lista-embalagens input.isPadrao');
                    // controle da quantidade de embalagens do tipo expedicao
                    var qtdEmbalagensExpedicao = 0;

                    // verifico se existe embalagem de recebimento
                    inputsIsPadrao.each(function(i, v) {
                        if ( this.value == 'N' )
                            qtdEmbalagensExpedicao = qtdEmbalagensExpedicao + 1;
                    });

                    if( qtdEmbalagensExpedicao > 0 )
                        alert('Remova as embalagens de Expedição antes de remover a de Recebimento.');
                }

                // se é um endereço existente (não haja a palavra '-new' no id)
                if (id.indexOf('-new') == -1) {
                    //limpa o ID
                    id.replace('-new', '');
                    //adiciona à fila para excluir
                    $('<input/>', {
                        name: 'embalagens[' + id + '][acao]',
                        value: 'excluir',
                        type: 'hidden'
                    }).appendTo('#fieldset-embalagens-cadastradas');
                }

                $.ajax({
                    url: URL_MODULO + '/endereco/verificar-estoque-ajax',
                    type: 'POST',
                    data: {
                        enderecoAntigo: enderecoAntigo,
                        grade: grade,
                        produto: idProduto
                    },
                    success: function(data){
                        if (data.status == 'error') {
                            alert(data.msg);
                            return false;
                        } else if (data.status == 'success') {
                            $.ajax({
                                url: URL_MODULO + '/produto/verificar-parametro-codigo-barras-ajax',
                                type: 'post',
                                dataType: 'json',
                                success: function (data) {
                                    if (data == 'S') {
                                        alert("Não é possível excluir embalagem com parametro de código de barras desativado");
                                        return false;
                                    } else {
                                        //remove a div do endereco
                                        model.elements().remove();
                                        //reseta o form
                                        este.resetarForm();
                                        // carregar embalagens nos dados logisticos
                                        este.carregarSelectEmbalagens();
                                    }
                                }
                            });
                        }
                    }
                });
            }
        },

        /**
         * Exibe a lista de produto_embalagens e submete o formulario
         *
         * @param {Array} produto_embalagens Matriz de objetos Wms.Models.ProdutoEmbalagem.
         */
        list: function( produto_embalagens ){

            $('#div-lista-embalagens').html(this.view('init', {
                produto_embalagens:produto_embalagens
            } ));

            // carregar embalagens nos dados logisticos
            this.carregarSelectEmbalagens();
        },

        /**
         *
         */
        '#embalagem-CBInterno change': function () {
            if($('#embalagem-CBInterno').val() == 'S'){
                $('#embalagem-imprimirCB').val('S');
                $('#embalagem-codigoBarras').val('').attr('readonly', true);
            }else{
                $('#embalagem-imprimirCB').val('N');
                $('#embalagem-codigoBarras').attr('readonly', false);
            }
        },

        /**
         * Reseta o form base para novo cadastro
         */
        resetarForm: function() {
            if($('.produto_embalagem').size() >= 1)
                $('#embalagem-isPadrao').val('N');

            $('#embalagem-quantidade').attr('disabled', false);
            $('#embalagem-descricao,#embalagem-pontoReposicao, #embalagem-capacidadePicking, #embalagem-quantidade, #embalagem-id, #embalagem-codigoBarras, #embalagem-codigoBarrasAntigo, #embalagem-endereco').val('');
            $('#embalagem-pontoReposicao, #embalagem-capacidadePicking').val('0');
            $('#embalagem-isPadrao').val('N').attr('disabled', false);
            $('#embalagem-codigoBarras').attr('disabled', false);
            $('#embalagem-capacidadePicking').attr('disabled', false);
            $('#embalagem-pontoReposicao').attr('disabled', false);
            $('#embalagem-acao').val('incluir');

            $('#embalagem-CBInterno, #embalagem-imprimirCB, #embalagem-embalado').val('N');

            $('#btn-salvar-embalagem').val('Adicionar');
            $('#fieldset-embalagem legend').html('Criar Novo');
        },

        /**
         * Valida as embalagens cadastradas
         */
        verificarEmbalagemRecebimento: function (id, valores) {

            // constantes tipo comercializacao
            var UNITARIO = 1;
            var COMPOSTO = 2;
            var KIT = 3;

            // acao do form
            var inputAcao = $('#embalagem-acao').val();
            // variaveis
            var qtdEmbalagensCadastradas = $('.produto_embalagem').size();
            // campos da embalagem
            var inputsEmbalagem = $('#div-lista-embalagens input');
            // controle da quantidade de embalagens do tipo recebimento
            var qtdEmbalagensRecebimento = 0;
            // controle da quantidade de embalagens do tipo expedicao
            var qtdEmbalagensExpedicao = 0;
            // quantidades de itens da embalagem de recebimento cadastrada
            var qtdItemEmbalagemRecebimento = 0;

            // verifico se existe embalagem de recebimento
            inputsEmbalagem.each(function(i, v) {

                if ( this.className == 'isPadrao' ) {
                    if ( this.value == 'S' ) {
                        // adiciono o valor da embalagem de recebimento
                        qtdItemEmbalagemRecebimento = parseInt($(this).parent('div').find('.qtdItens').val());
                        // incremento a qtd de embalagens de recebimento cadastradas
                        qtdEmbalagensRecebimento = qtdEmbalagensRecebimento + 1;
                    }
                    else
                        qtdEmbalagensExpedicao = qtdEmbalagensExpedicao + 1;
                }
            });

            // se não houver embalagem cadastrada
            if ( qtdEmbalagensCadastradas == 0 )  {
                // caso primeira embalagem seja de expedicao, lanco erro
                if($('#embalagem-isPadrao').val() == 'N') {
                    alert('A primeira embalagem deve ser de recebimento. \n Altere o recebimento para SIM.');
                    return false;
                }
            }

            // caso cadastro de nova embalagem
            switch(inputAcao) {
                case 'incluir':
                    // o tipo de comercializacao do produto seja composto e já tenha outras embalagens cadastradas lanço o erro
                    //if ( ( $('#produto-idTipoComercializacao').val() == COMPOSTO ) && ( qtdEmbalagensCadastradas >= 1 ) ) {
                    //    alert('O tipo de comercialização deste produto é Composto. \n Só é possível cadastrar uma Embalagem por produto.');
                    //    return false;
                    //}

                    // controle de cadastro de embalagens de recebimento
                    if ( (qtdEmbalagensRecebimento >= 1) && ($('#embalagem-isPadrao').val() == 'S')) {
                        alert('O produto deve conter APENAS uma embalagem cadastrada do tipo recebimento. Altere "Embalagem de Recebimento" para "Não"');
                        return false;
                    }
                    break;
                case 'alterar':
                    // verifico a possibilidade de editar embalagens de recebimento.
                    // caso haja embalagens de expedicao dar alerta.
                    //if ( ( $('#embalagem-isPadrao').val() == 'S' ) && ( qtdEmbalagensExpedicao > 0 ) ) {
                    //    alert('Remova as embalagens de expedição para poder editar esta embalagem de recebimento.');
                    //    return false;
                    //}
                    break;

            }

            // calculo de quantidade de itens para embalagens de expedição de produto unitarios
            if(($('#produto-idTipoComercializacao').val() == UNITARIO) && ($('#embalagem-isPadrao').val() == 'N')) {
                var qtdItensEmbalagem = parseInt($('#embalagem-quantidade').val());

                if ( qtdItensEmbalagem > qtdItemEmbalagemRecebimento ) {
                    alert('Quantidade de itens da embalagem de expedição, deve ser menor ou igual da quantidade de itens da embalagem de recebimento.');
                    return false;
                }

                if ( (qtdItemEmbalagemRecebimento % qtdItensEmbalagem) != 0 ) {
                    alert('Quantidade de itens da embalagem de expedição deve ser multipla da quantidade de itens da embalagem de recebimento.');
                    return false;
                }
            }

            return true;
        },

        /**
         * Shows a produto_embalagem's information.
         */
        show: function( produto_embalagem ){
            produto_embalagem.elements().replaceWith(this.view('show',produto_embalagem));
        },

        /**
         * Carrega todas as embalagens cadastras no select dos dados logisticos
         */
        carregarSelectEmbalagens:function() {
            //
            var select = $('select#dadoLogistico-idEmbalagem');
            var blocosEmbalagem = $('div.produto_embalagem');

            // remove all
            select.find('option').remove();

            blocosEmbalagem.each( function() {
                var id = $(this).find('.embalagem-id').val();
                var descricao = $(this).find('.embalagem-descricao').val();

                select.append('<option value="' + id + '">' + descricao + '</option>');
            });
        },

        /**
         * Retorna o id da embalagem de recebimento
         */
        buscarEmbalagemRecebimento:function() {
            var idEmbalagemRecebimento = 0;
            // campos da embalagem
            var inputsEmbalagem = $('#div-lista-embalagens input');
            var idEmbalagemRecebimento = 0;
            var blocosEmbalagem = $('div.produto_embalagem');

            blocosEmbalagem.each( function() {
                var id = $(this).find('.embalagem-id').val();
                var isPadrao = $(this).find('.isPadrao').val();

                if ( isPadrao == 'S' ) {
                    idEmbalagemRecebimento = id;
                }
            });

            return idEmbalagemRecebimento;
        },

        /**
         *
         */
        checarCBInterno: function() {
            $('#embalagem-codigoBarras').attr('readonly', false);
            if ($('#embalagem-CBInterno').val() == 'S'){
                $('#embalagem-codigoBarras').attr('readonly', true);
            }
        },

        /**
         * Verifica se ja existe o codigo de barras informado
         */
        verificarCodigoBarras:function() {
            var acao = $('#embalagem-acao').val();
            var codigoBarras = $('#embalagem-codigoBarras').val();
            var codigoBarrasAntigo = $('#embalagem-codigoBarrasAntigo').val();
            var codigosBarras = $('.codigoBarras');

            if (codigoBarras == ""){
                this.verificarEndereco();
                return false;
            }

            // verifico se existe embalagens neste produto com o mesmo codigo de barras
            var numVezes = 0;
            codigosBarras.each(function(){
                if ( this.value == codigoBarras ){
                    numVezes++;
                }
            });

            if ( numVezes >= 1 && (codigoBarras != codigoBarrasAntigo)) {
                alert('Este código de barras já foi cadastrado neste produto.');
                return false;
            }

            //Verifica se a embalagem esta sendo editada e o codigo é igual
            if((acao == 'alterar') && (codigoBarras == codigoBarrasAntigo)){
                this.verificarEndereco();
                return false;
            }

            var idProduto = $('#embalagem-idProduto').val();
            var grade = $('#embalagem-grade').val();
            new Wms.Models.ProdutoEmbalagem.verificarCodigoBarras({
                idProduto:idProduto,
                grade:grade,
                codigoBarras:codigoBarras
            }, this.callback('validarCodigoBarras'));
        },

        /**
         * Valida o codigo de barras informado
         *
         * @param {Array} params Matriz de objetos Wms.Models.ProdutoEmbalagem.
         */
        validarCodigoBarras: function( params ){
            if(params.status == 'error'){
                alert(params.msg);
                return false;
            }

            this.verificarEndereco();

        },

        /**
         * Verifica se existe o endereco informado
         */
        verificarEndereco:function() {
            var acao = $('#embalagem-acao').val();
            var endereco = $('#embalagem-endereco').val();
            var enderecoAntigo = $('#embalagem-enderecoAntigo').val();
            var idProduto = $('#embalagem-idProduto').val();
            var grade = $('#embalagem-grade').val();

            if (endereco == "" || (endereco != enderecoAntigo)){
                var este = this;
                $.ajax({
                    url: URL_MODULO + '/endereco/verificar-estoque-ajax',
                    type: 'POST',
                    data: {
                        enderecoAntigo: enderecoAntigo,
                        grade: grade,
                        produto: idProduto
                    },
                    success: function(data){
                        if (data.status == 'error') {
                            alert(data.msg);
                        } else if (data.status == 'success') {
                            este.salvarDadosEmbalagem();
                        }
                    }
                });
                return false;
            }

            if (endereco.length != 12){
                alert('Formato inválido de Endereço.');
                return false;
            }

            //Verifica se a embalagem esta sendo editada e o codigo é igual
            if((acao == 'alterar') && (endereco == enderecoAntigo)){
                this.salvarDadosEmbalagem();
                return false;
            }

            new Wms.Models.ProdutoEmbalagem.verificarEndereco({
                idProduto:idProduto,
                grade:grade,
                endereco:endereco
            }, this.callback('validarEndereco'));
        },

        /**
         * Valida o endereco informado
         * @param {Array} params Matriz de objetos Wms.Models.ProdutoEmbalagem.
         */
        validarEndereco: function( params ){
            if(params.status == 'error'){
                alert(params.msg);
                return false;
            }

            this.salvarDadosEmbalagem();
        },

        validarEstoqueEndereco: function( params ){
            if(params.status == 'error'){
                alert(params.msg);
                return false;
            }
        }

    });