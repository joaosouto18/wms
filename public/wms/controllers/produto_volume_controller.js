/**
 * @tag controllers, home
 * Displays a table of produto_volumes.	 Lets the user
 * ["Wms.Controllers.ProdutoVolume.prototype.form submit" create],
 * ["Wms.Controllers.ProdutoVolume.prototype.&#46;edit click" edit],
 * or ["Wms.Controllers.ProdutoVolume.prototype.&#46;destroy click" destroy] produto_volumes.
 */
$.Controller.extend('Wms.Controllers.ProdutoVolume',
    /* @Static */
    {
        pluginName: 'produtoVolume'
    },
    /* @Prototype */
    {
        /**
         * Carrega as normas de paletizacao e volumes ao carregar a pagina
         */
        "{window} load": function() {
            if($(".grupoDadosLogisticos").size() === 0) {
                var idProduto = $('#volume-idProduto').val();
                var grade = $('#volume-grade').val();

                if (idProduto !== '' && grade !== '') {
                    Wms.Models.ProdutoVolume.findNormasPaletizacao({
                        idProduto:idProduto,
                        grade:grade
                    }, this.callback('listNorma'));
                }
            }
            var codBarrasBase = $('#produto-codigoBarrasBase');
            if (codBarrasBase.val() === ""){
                $('#volume-codigoBarras').attr('readonly', false).addClass("required");
            } else {
                $('#volume-codigoBarras').attr('readonly', true);
            }
        },


        /**
         *
         * @param {jQuery} el A jQuery wrapped element.
         * @param {Event} ev A jQuery event whose default action is prevented.
         */
        '#ativarDesativar click' : function(el,ev) {

            var check = $(el).parent('div').find('.ativarDesativar');
            var date = $(el).parent('div').find('.dataInativacao');
            var div = $(el).parent('div').parent('td');

            if (check.is(":checked") === true) {
                if (date.text() === "VOL. ATIVO") {
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
                    today = dd+'/'+mm+'/'+yyyy;

                    date.text(today);
                }
                div.css("color","red");
            } else {
                date.text("VOL. ATIVO");
                div.css("color","green");
            }
        },

        /**
         * Responds to the create form being submitted by creating a new Wms.Models.ProdutoVolume.
         * @param {jQuery} el A jQuery wrapped element.
         * @param {Event} ev A jQuery event whose default action is prevented.
         */
        '#btn-salvar-volume click': function(el, ev) {
            // constantes tipo comercializacao
            var UNITARIO = 1;
            var COMPOSTO = 2;
            var KIT = 3;

            // variaveis
            var idTipoComercializacao = parseInt($('#produto-idTipoComercializacao').val());
            var grupoDadosLogisticos = $('#fieldset-grupo-volumes').find('div.grupoDadosLogisticos');
            var este = this;

            var result = true;
            $.ajax({
                url: URL_MODULO + '/produto/verificar-parametro-codigo-barras-ajax',
                type: 'post',
                async: false,
                dataType: 'json',
                success: function (data) {
                    if (data === 'N') {
                        este.dialogAlert("Pelos parâmetros definidos, não é permitido incluir/editar volumes no WMS apenas no ERP");
                        result = false;
                    }
                }
            });
            if (!result)
                return result;

            //caso a quantidade de grupos de normatizacao seja igual a zero
            if( grupoDadosLogisticos.size() === 0 ) {
                this.dialogAlert('Crie ao menos uma norma de paletização para cadastrar o dado logistico');
                return false;
            }

            var acao = $('#volume-acao').val();
            var codigoBarras = $('#volume-codigoBarras');
            var codBarrasBase = $('#produto-codigoBarrasBase');
            var codigoBarrasAntigo = $('#volume-codigoBarrasAntigo');
            var cbInterno = $('#volume-CBInterno');

            var erros = 0;
            if (codBarrasBase.val() === "") {
                if (cbInterno.val() === "N") {
                    if ((codigoBarras.val() !== '') && (acao === 'alterar')) {
                        if (codigoBarras.val() === codigoBarrasAntigo.val())
                            return true;
                    } else if (codigoBarras.val() === '') {
                        erros++
                    }
                }
            }

            if ($('#volume-descricao').val() === '') erros++;

            if (parseFloat($('#volume-altura').val()) === 0) {
                erros++;
                $('#volume-altura').addClass('required invalid');
            }

            if (parseFloat($('#volume-largura').val()) === 0) {
                erros++;
                $('#volume-largura').addClass('required invalid');
            }

            if (parseFloat($('#volume-profundidade').val()) === 0) {
                erros++;
                $('#volume-profundidade').addClass('required invalid');
            }

            if (parseFloat($('#volume-peso').val()) === 0) {
                erros++;
                $('#volume-peso').addClass('required invalid');
            }

            if (erros > 0) {
                this.dialogAlert("Os itens destacados são obrigatórios");
                return false;
            }


            if (este.verificarCodigoBarras()) {
                if (este.verificarEndereco()) {
                    este.salvarDadosVolume();
                } else {
                    $('#volume-endereco').focus();
                }
            } else {
                $('#volume-codigoBarras').focus();
            }

            //cancela evento
            ev.preventDefault();
        },

        salvarDadosVolume:function() {
            var valores = $('#fieldset-volume').formParams(false).volume;
            var id = $("#volume-id").val();
            var grupoDadosLogisticos = $('#fieldset-grupo-volumes').find('div.grupoDadosLogisticos');
            valores.lblIsPadrao = $('#fieldset-volume #volume-isPadrao option:selected').text();
            valores.lblCBInterno = $('#fieldset-volume #volume-CBInterno option:selected').text();
            valores.lblImprimirCB = $('#fieldset-volume #volume-imprimirCB option:selected').text();
            valores.dataInativacao = 'VOL. ATIVO';
            var produto_volume = null;

            if (id !== '') {
                valores.acao = id.indexOf('-new') === -1 ? 'alterar' : 'incluir';
                produto_volume = new Wms.Models.ProdutoVolume(valores);
                this.show(produto_volume);
            } else {
                var d = new Date();
                valores.id = d.getTime()+ '-new';
                valores.idNormaPaletizacao = $(grupoDadosLogisticos[0]).find('.normasPaletizacao-id').val();
                valores.acao = 'incluir';
                produto_volume = new Wms.Models.ProdutoVolume(valores);
                $(grupoDadosLogisticos[0]).append( this.view("show", produto_volume) );
            }

            //limpo form
            this.resetarForm();
            //drag and drop
            this.dragdropGrupoVolumes();

            //Calcula Peso e Cubagem Total para aba produto
            Wms.Controllers.Produto.prototype.pesoTotal();
            Wms.Controllers.Produto.prototype.cubagemTotal();
            //Calcula o peso para norma de paletizacao
            this.calcularPesoNormaPaletizacao();
        },

        /**
         * Calculo de cubagem
         */
        '.parametro-cubagem change' : function () {
            var largura = $('#volume-largura').val().replace('.','').replace(',','.');
            var altura = $('#volume-altura').val().replace('.','').replace(',','.');
            var profundidade = $('#volume-profundidade').val().replace('.','').replace(',','.');
            var cubagem = Wms.Controllers.CalculoMedida.prototype.calculaCubagem(largura, altura, profundidade, 4);

            cubagem = Wms.Controllers.CalculoMedida.prototype.formatMoney(parseFloat(cubagem.toString().replace(',', '.')).toFixed(4), 4, ',', '.');
            $('#volume-cubagem').val(cubagem);
        },

        /**
         * Lista de normas de paletizacao
         */
        listNorma: function( normas_paletizacao ) {

            $('#fieldset-grupo-volumes').append( $.View('//wms/views/norma_paletizacao/init', {
                normas_paletizacao:normas_paletizacao
            }));


            var grupoDadosLogisticos = $('div.grupoDadosLogisticos');

            grupoDadosLogisticos.each(function() {
                var normaId = $(this).find($('input.normasPaletizacao-id')).val();

                for(var k=0; k<normas_paletizacao.length; k++) {
                    var volumes = normas_paletizacao[k].volumes;

                    if(normaId !== normas_paletizacao[k].id)
                        continue;

                    for(var j=0; j<volumes.length; j++) {
                        // transformo em um model do tipo produto_volume
                        var produto_volume = new Wms.Models.ProdutoVolume(volumes[j]);
                        // mando exibir
                        $(this).append( $.View('//wms/views/produto_volume/show',  produto_volume));
                    }
                }
            });

            // drag and drop dos volumes
            this.dragdropGrupoVolumes();
            // valor da sequencia
            $("#volume-codigoSequencial").val($('.produto_volume').size() + 1);
            // gera cod barra base do volume
            this.gerarCodBarraBase();
            //Calcula Peso e Cubagem Total para aba produto
            Wms.Controllers.Produto.prototype.pesoTotal();
            Wms.Controllers.Produto.prototype.cubagemTotal();
        },

        /**
         * Limpa form para cadastros
         */
        resetarForm: function() {
            //reseta valores
            //$("#fieldset-volume input[type=hidden]").val('');
            $('#volume-altura, #volume-largura, #volume-profundidade, #volume-cubagem, #volume-peso').val('0,000');
            $('#embalagem-pontoReposicao, #embalagem-capacidadePicking').val('0');
            $('#volume-id, #volume-descricao, #volume-codigoBarras, #volume-codigoBarrasAntigo, #volume-endereco, #volume-enderecoAntigo').val('');
            $('#volume-CBInterno, #volume-imprimirCB').val('N');

            //valido codigo de barras base para limpar o do volume
            if($('#produto-codigoBarrasBase').val() == ''){
                $('#volume-codigoBarras').val('');
            }

            // valor da sequencia
            $("#volume-codigoSequencial").val($('.produto_volume').size() + 1);
            // gera cod barra base do volume
            this.gerarCodBarraBase();

            $('#btn-salvar-volume').val('Adicionar Volume');
        },

        /**
         * Checa se a quantidade de volumes está de acordo com tipo de comercializacao
         */
        verificarQuantidadeVolumes: function () {
            // constantes tipo comercializacao
            var UNITARIO = 1;
            var COMPOSTO = 2;
            var KIT = 3;

            // variaveis
            var idTipoComercializacao = parseInt($('#produto-idTipoComercializacao').val());
            var qtdVolumesCadastrados = $('.produto_volume').size();

            // caso unitario
            if(idTipoComercializacao === UNITARIO) {
                // se houver volume cadastrado
                if(qtdVolumesCadastrados === 1) {
                    alert('O tipo de comercialização deste produto é Unitário. \n Só é possível cadatrar um Volume para o mesmo.');
                    return false;
                }
            }

            return true;
        },

        /**
         * Drag and Drop dos volumes para os grupos
         */
        dragdropGrupoVolumes: function () {

            $( "#div-lista-volumes" ).sortable({
                placeholder: "ui-state-highlight"
            });

            $( "#div-lista-volumes" ).disableSelection();

            $('.produto_volume').draggable( {
                containment: '#content',
                stack: '#div-lista-volumes div',
                cursor: 'pointer',
                revert: true,
                drag: function(event, ui) {
                    $(this).addClass('moveData');
                },
                stop: function(event, ui) {
                    $(this).removeClass('moveData');
                    // altera valor da norma paletizacao do volume
                    var idNormaPaletizacao = $(this).parents('div.grupoDadosLogisticos').find($('input.normasPaletizacao-id')).val();
                    $(this).find('input.idNormaPaletizacao').val(idNormaPaletizacao);

                    Wms.Controllers.ProdutoVolume.prototype.checarStatuNormasPaletizacao();
                    //Calcula o peso para norma de paletizacao
                    Wms.Controllers.ProdutoVolume.prototype.calcularPesoNormaPaletizacao();
                }
            } );

            $('.grupoDadosLogisticos').droppable( {
                accept: '.produto_volume',
                //activeClass: "ui-state-highlight",
                drop: function( event, ui ) {
                    ui.draggable.appendTo( this );
                    $( "#div-lista-volumes div.ui-draggable-dragging" ).remove();
                }
            } );
        },
        /**
         * Creates and places the edit interface.
         * @param {jQuery} el The produto_volume's edit link element.
         * @param {jQuery} ev The event.
         */
        '.btn-editar-volume click': function( el , ev ){

            $.ajax({
                url: URL_MODULO + '/produto/verificar-parametro-codigo-barras-ajax',
                type: 'post',
                dataType: 'json',
                success: function (data) {
                    if (data === 'N') {
                        $('#volume-codigoBarras').attr("disabled", true);
                    }
                }
            });

            ev.stopPropagation();
            var produto_volume = el.closest('.produto_volume').model();

            // altera informacao
            $('#fieldset-volume legend').html('Editando volume');

            // carrega dados
            $('#volume-acao').val('alterar');
            $('#volume-id').val(produto_volume.id);
            $('#volume-codigoSequencial').val(produto_volume.codigoSequencial);
            $('#volume-largura').val(produto_volume.largura);
            $('#volume-altura').val(produto_volume.altura);
            $('#volume-profundidade').val(produto_volume.profundidade);
            $('#volume-cubagem').val(produto_volume.cubagem);
            $('#volume-peso').val(produto_volume.peso);
            $('#volume-descricao').val(produto_volume.descricao);
            $('#volume-CBInterno').val(produto_volume.CBInterno);
            $('#volume-imprimirCB').val(produto_volume.imprimirCB);
            $('#volume-codigoBarras').val(produto_volume.codigoBarras);
            $('#volume-codigoBarrasAntigo').val(produto_volume.codigoBarras);
            $('#volume-idNormaPaletizacao').val(produto_volume.idNormaPaletizacao);
            $('#volume-endereco').val(produto_volume.endereco);
            $('#volume-enderecoAntigo').val(produto_volume.endereco);
            $('#volume-capacidadePicking').val(produto_volume.capacidadePicking);
            $('#volume-pontoReposicao').val(produto_volume.pontoReposicao);
            $('#btn-salvar-volume').val('Editar Volume');

            // checa opcoes de Codigo de Barras Interno
            this.checarCBInterno();
            // checa o Codigo de Barras Base
            this.checarCodBarrasBase();

        },

        /**
         *	 Handle's clicking on a produto_volume's destroy link.
         */
        '.btn-excluir-volume click': function(el, ev){
            //evita a propagação do click para a div
            var model = el.closest('.produto_volume').model();
            var id = model.id.toString();
            var este = this;

            //se é um endereço existente (não haja a palavra '-new' no id)
            if (id.indexOf('-new') === -1) {
                //limpa o ID
                id = id.replace('-new', '');
            }

            var temReserva = false;
            $.ajax({
                url: URL_MODULO + '/produto-volume/verificar-estoque-reserva-ajax',
                type: 'POST',
                data: { id: id },
                async: false,
                success: function (data) {
                    if (data.status === 'error'){
                        este.dialogAlert(data.msg);
                        temReserva = true;
                    }
                }
            });

            if (temReserva)
                return false;

            var idProduto = $('#volume-idProduto').val();
            var grade = $('#volume-grade').val();
            var enderecoAntigo = model.endereco.toString();
            var temEstoque = false;
            $.ajax({
                url: URL_MODULO + '/endereco/verificar-estoque-ajax',
                type: 'POST',
                async: false,
                data: {
                    enderecoAntigo: enderecoAntigo,
                    grade: grade,
                    produto: idProduto
                },
                success: function(data){
                    if (data.status === 'error') {
                        este.dialogAlert(data.msg);
                        temEstoque = true;
                    }
                }
            });

            if (temEstoque) return false;

            $.ajax({
                url: URL_MODULO + '/produto/verificar-parametro-codigo-barras-ajax',
                type: 'post',
                dataType: 'json',
                success: function (data) {
                    if (data === 'N') {
                        este.dialogAlert("Pelos parâmetros definidos <br>Não é permitido excluir volumes no WMS apenas no ERP");
                        return false;
                    } else {

                    }
                }
            });

            ev.stopPropagation();

            this.dialogConfirm("Tem certeza que deseja excluir este volume?", this.callback("deleteConfirmed"),{id:id});
        },

        deleteConfirmed: function(params) {
            var id = params.id;

            //adiciona à fila para excluir
            $('<input/>', {
                name: 'volumes[' + id + '][acao]',
                value: 'excluir',
                type: 'hidden'
            }).appendTo('.grupoDadosLogisticos');

            //remove a div do endereco
            model.elements().remove();
            //limpo form
            this.resetarForm();
            //Calcula Peso e Cubagem Total para aba produto
            Wms.Controllers.Produto.prototype.pesoTotal();
            Wms.Controllers.Produto.prototype.cubagemTotal();
            //Calcula o peso para norma de paletizacao
            this.calcularPesoNormaPaletizacao();
        },

        /**
         *
         */
        '#btn-add-grupo click': function( el , ev ){
            this.createGroupBlock();
        },

        /**
         *
         */
        '#btn-excluir-grupo click': function( el , ev ){
            var grupoVolume = el.closest('.grupoDadosLogisticos');

            if(grupoVolume.find('.produto_volume').size() !== ''){
                this.dialogAlert("Esta norma de paletização deve estar vazia para poder ser excluida.");
                return false;
            }else {
                this.dialogConfirm("Tem certeza que deseja excluir esta norma de paletização?", grupoVolume.remove());
            }
        },


        /**
         * Checa status dos grupos de norma de paletizacao
         */
        checarStatuNormasPaletizacao: function () {
            var normasPaletizacao = $('.grupoDadosLogisticos');

            normasPaletizacao.each(function() {
                var qtdVolumes = $(this).find('.produto_volume').size();
                var inputAcao = $(this).find('input.normasPaletizacao-acao');
                var inputId = $(this).find('input.normasPaletizacao-id');

                // controle da acao com o grupo da norma de paletizacao
                if(qtdVolumes === 0)
                    inputAcao.val('excluir');
                else {
                    if (inputId.val().indexOf('-new') === -1)
                        inputAcao.val('alterar');
                    else
                        inputAcao.val('incluir');
                }

            });
        },
        /**
         * Shows a produto_volume's information.
         */
        show: function( produto_volume ){
            produto_volume.elements().replaceWith(this.view('show',produto_volume));
        },
        /**
         * Cria um grupo para agregar volumes pela norma de paletização
         */
        createGroupBlock : function () {
            // criando um novo objeto grupo
            var valores = {};
            var d = new Date();
            valores.acao = 'incluir';
            valores.numLastro = '0';
            valores.numCamadas = '0';
            valores.numNorma = '0';
            valores.numPeso = '0,000';
            valores.unitizadores = eval($('#produto-unitizadores').val());

            valores.id = d.getTime()+ '-new';
            norma_paletizacao = new Wms.Models.NormaPaletizacao(valores);

            $('#fieldset-grupo-volumes').append($.View('//wms/views/norma_paletizacao/show', norma_paletizacao));

            //drag and drop dos volumes
            this.dragdropGrupoVolumes();
        },

        /**
         *
         */
        '#volume-codigoSequencial change' : function () {
            // gera cod barra base do volume
            if($('#produto-codigoBarrasBase').val() !== ''){
                this.gerarCodBarraBase();
            }
        },

        /**
         * Atualiza o codigo de barras base no cadastro dos volumes
         */
        gerarCodBarraBase: function() {
            var codigoBarrasBase = $('#produto-codigoBarrasBase').val();
            var inputsCodigoBarras = $('.produto_volume input.codigoBarras');
            var numVolumes = $('#produto-numVolumes').val();
            var codigoSequencial = $('#volume-codigoSequencial').val();
            var codBarras = $('#volume-codigoBarras');

            codBarras.attr('readonly', false).val('').addClass("required");

            if(codigoBarrasBase !== "") {
                if(codigoSequencial.length === 1){
                    codigoSequencial = "0" + codigoSequencial;
                }

                if(numVolumes.length === 1){
                    numVolumes = "0" + numVolumes;
                }

                inputsCodigoBarras.each(function(i, val) {
                    var codigoBarras = $(inputsCodigoBarras[i]);
                    var sequencia = codigoBarrasBase + i;
                    var model = codigoBarras.closest('.produto_volume').model();
                    var acao = codigoBarras.closest('div').find('input.acao');
                    var codigoBarrasSequenciaVolumes = sequencia + codigoSequencial + numVolumes;

                    //caso necessario criar
                    if(codigoBarras.val().indexOf(codigoBarrasBase) === -1) {
                        if(acao.val() === '')
                            codigoBarras.closest('div').find('input.acao').val('alterar');

                        codigoBarras.val(codigoBarrasSequenciaVolumes);
                        codigoBarras.closest('div').find('span.codigoBarras').html(codigoBarrasSequenciaVolumes);
                        model.codigoBarras = codigoBarrasSequenciaVolumes;
                    }
                });

                codBarras.removeClass("required invalid").attr('readonly', true).val(codigoBarrasBase + codigoSequencial + numVolumes);
            }
        },

        /**
         * Calculo de norma de paletizacao
         */
        '.parametro-norma change' : function (el, ev) {
            var grupoVolume = el.closest('.grupoDadosLogisticos');
            var lastro = grupoVolume.find('#normaPaletizacao-numLastro').val().replace('.','').replace(',','.');
            var camadas = grupoVolume.find('#normaPaletizacao-numCamadas').val().replace('.','').replace(',','.');
            var numNormaPaletizacao = Wms.Controllers.CalculoMedida.prototype.calculaNormaPaletizacao(lastro, camadas);

            grupoVolume.find('#normaPaletizacao-numNorma').val(numNormaPaletizacao);

            //Calcula o peso para norma de paletizacao
            this.calcularPesoNormaPaletizacao();
        },

        /**
         * Calculo de peso para norma de paletizacao
         */
        calcularPesoNormaPaletizacao: function (el, ev) {
            var grupoVolume = $('.grupoDadosLogisticos');

            //soma os volumes por grupo
            grupoVolume.each(function(el, ev) {
                var pesoNormaPaletizacao = 0;
                var inputsPeso = $(this).find('.produto_volume input.peso');
                inputsPeso.each(function(el, ev) {
                    pesoVolume = parseFloat(this.value.replace('.','').replace(',','.'));
                    pesoNormaPaletizacao = pesoNormaPaletizacao + pesoVolume;
                });

                //pega o valor de norma de paletizacao
                var numNormaPaletizacao = $(this).find('#normaPaletizacao-numNorma').val();
                //multiplica norma pelo total do peso
                pesoNormaPaletizacao = numNormaPaletizacao * pesoNormaPaletizacao;

                pesoNormaPaletizacao = Wms.Controllers.CalculoMedida.prototype.formatMoney(parseFloat(pesoNormaPaletizacao.toString().replace(',', '.')).toFixed(3), 3, ',', '.');
                $(this).find('#normaPaletizacao-numPeso').val(pesoNormaPaletizacao);
            });
        },

        /**
         *
         */
        '#volume-CBInterno change': function () {
            if($('#produto-codigoBarrasBase').val() === ''){
                if($('#volume-CBInterno').val() === 'S'){
                    $('#volume-imprimirCB').val('S');
                    $('#volume-codigoBarras').val('').attr('readonly', true).removeClass("required invalid");
                }else{
                    $('#volume-imprimirCB').val('N');
                    $('#volume-codigoBarras').attr('readonly', false).addClass("required");
                }
            }else{
                $('#volume-codigoBarras').attr('readonly', true);
                if($('#volume-CBInterno').val() === 'S'){
                    $('#volume-imprimirCB').val('S');
                }else{
                    $('#volume-imprimirCB').val('N');
                }
            }
        },

        /**
         *
         */
        checarCBInterno: function() {
            var readOnly = false;
            if ($('#volume-CBInterno').val() === 'S'){
                readOnly = true;
            }
            $('#volume-codigoBarras').attr('readonly', readOnly);
        },

        /**
         *
         */
        checarCodBarrasBase: function() {
            var readOnly = false;
            if($('#produto-codigoBarrasBase').val() !== ''){
                readOnly = true;
            }
            $('#volume-codigoBarras').attr('readonly', readOnly);
        },

        /**
         * Verifica se ja existe o codigo de barras informado
         */
        verificarCodigoBarras:function() {
            var acao = $('#volume-acao').val();
            var codigoBarras = $('#volume-codigoBarras');
            var codBarrasBase = $('#produto-codigoBarrasBase');
            var codigoBarrasAntigo = $('#volume-codigoBarrasAntigo');
            var codigosBarras = $('.codigoBarras');
            var cbInterno = $('#volume-CBInterno');
            var este = this;

            if((codBarrasBase.val() !== '') && (cbInterno.val() === 'S')) {
                this.dialogAlert('Este Produto contém Código de Barras Base. Não é permitido ter volumes com Código de Barras Automático.');
                codigoBarras.focus();
                return false;
            }

            codigosBarras.each(function(){
                if ( this.value === codigoBarras ){
                    este.dialogAlert("Este código de barras já foi cadastrado neste produto.");
                    codigoBarras.focus();
                    return false;
                }
            });

            var result = null;
            $.ajax({
                url: URL_MODULO + '/produto/verificar-codigo-barras-ajax',
                type: 'post',
                async: false,
                dataType: 'json',
                data: { codigoBarras:codigoBarras }
            }).success(function (data) {
                if (data.status === "success") {
                    result = true;
                } else if (data.status === "error") {
                    este.dialogAlert(data.msg);
                    result = false;
                }
            });
            return result;
        },

        /**
         * Verifica se existe o endereco informado
         */
        verificarEndereco:function() {
            var acao = $('#volume-acao').val();
            var endereco = $('#volume-endereco').val();
            var enderecoAntigo = $('#volume-enderecoAntigo').val();
            var idProduto = $('#volume-idProduto').val();
            var grade = $('#volume-grade').val();
            var este = this;

            if (endereco !== enderecoAntigo && endereco !== "") {
                var result = null;
                $.ajax({
                    url: URL_MODULO + '/endereco/verificar-endereco-ajax',
                    type: 'post',
                    async: false,
                    dataType: 'json',
                    data: {endereco: endereco}
                }).success(function (data) {
                    if (data.status === "success") {
                        result = true;
                    } else if (data.status === "error") {
                        este.dialogAlert(data.msg);
                        result = false;
                    }
                });
                return result;
            } else {
                return true;
            }
        },

        dialogAlert: function ( msg ) {
            $.wmsDialogAlert({
                title: 'Alerta',
                msg: msg,
                height: 150,
                resizable: false
            });
        },

        dialogConfirm: function ( msg, callback, params ) {
            return $.wmsDialogConfirm({
                title: 'Tem certeza?',
                msg: msg
            }, callback, params);
        }

    });