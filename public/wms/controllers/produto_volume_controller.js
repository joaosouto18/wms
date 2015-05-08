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
        if($(".grupoDadosLogisticos").size() == 0) {        
            var idProduto = $('#volume-idProduto').val();
            var grade = $('#volume-grade').val();
        
            if (idProduto != '' && grade != '') {
                Wms.Models.ProdutoVolume.findNormasPaletizacao({
                    idProduto:idProduto, 
                    grade:grade
                }, this.callback('listNorma'));
            }
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
        
        //fieldset validation
        if($('#volume-codigoSequencial').val() == "") {
            alert('Preencha a Sequência.');
            $('#volume-codigoSequencial').focus();
            return false;
        }
        if($('#volume-altura').val() == "") {
            alert('Preencha o Altura.');
            $('#volume-altura').focus();
            return false;
        }
        if($('#volume-largura').val() == "") {
            alert('Preencha a Largura.');
            $('#volume-largura').focus();
            return false;
        }
        if($('#volume-profundidade').val() == "") {
            alert('Preencha o Profundidade.');
            $('#volume-profundidade').focus();
            return false;
        }
        if($('#volume-cubagem').val() == "") {
            alert('Preencha o Cubagem.');
            $('#volume-cubagem').focus();
            return false;
        }
        if($('#volume-peso').val() == "") {
            alert('Preencha o Peso.');
            $('#volume-peso').focus();
            return false;
        }
        
        if(($('#produto-codigoBarrasBase').val() == '') && ($('#volume-CBInterno').val() == 'N') && ($('#volume-codigoBarras').val() == '')) {
            alert('Preencha o Código de Barras.');
            $('#volume-codigoBarras').focus();
            return false;
        }
        
        if(($('#produto-codigoBarrasBase').val() != '') && ($('#volume-CBInterno').val() == 'S')) {
            alert('Este Produto contém Código de Barra Base. Não é permitido ter volumes com Código de Barras Automático.');
            $('#produto-codigoBarrasBase').focus();
            return false;
        }
        
        //caso a quantidade de grupos de normatizacao seja igual a zero
        if( grupoDadosLogisticos.size() ==  0 ) {
            alert('Crie ao menos uma norma de paletização para cadastrar o dado logistico');
            return false;
        }
        
        //verifica se ja existe o codigo de barras informado
        this.verificarCodigoBarras();
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
           
        if (id != '') {
            valores.acao = id.indexOf('-new') == -1 ? 'alterar' : 'incluir';
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
     * @param {Array} produto_volumes An array of Wms.Models.ProdutoVolume objects.
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
                
                if(normaId != normas_paletizacao[k].id) 
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
        if(idTipoComercializacao == UNITARIO) {
            // se houver volume cadastrado
            if(qtdVolumesCadastrados == 1) {
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
     */
    '.btn-editar-volume click': function( el , ev ){
        
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
        ev.stopPropagation();
        
        if(confirm("Tem certeza que deseja excluir esta volume?")){
            var model = el.closest('.produto_volume').model();
            var id = model.id.toString();
            
            //se é um endereço existente (não haja a palavra '-new' no id)
            if (id.indexOf('-new') == -1) {
                //limpa o ID
                id.replace('-new', '');
                //adiciona à fila para excluir 
                $('<input/>', {
                    name: 'volumes[' + id + '][acao]',
                    value: 'excluir',
                    type: 'hidden'
                }).appendTo('.grupoDadosLogisticos'); 
            }
            
            //remove a div do endereco
            model.elements().remove();
            //limpo form
            this.resetarForm();
            //Calcula Peso e Cubagem Total para aba produto
            Wms.Controllers.Produto.prototype.pesoTotal();
            Wms.Controllers.Produto.prototype.cubagemTotal();
            //Calcula o peso para norma de paletizacao
            this.calcularPesoNormaPaletizacao();
        }
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
        
        if(grupoVolume.find('.produto_volume').size() != ''){
            if(confirm("Esta norma de paletização deve está vazia para poder ser excluida.")) {
                return false;
            }
        }else if(confirm("Tem certeza que deseja excluir esta norma de paletização?")){
            grupoVolume.remove();
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
            if(qtdVolumes == 0)
                inputAcao.val('excluir');
            else {
                if (inputId.val().indexOf('-new') == -1)
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
        var valores = new Object();
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
        if($('#produto-codigoBarrasBase').val() != ''){
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

        $('#volume-codigoBarras').attr('readonly', false).val('');
        
        if(codigoBarrasBase != "") {
            if(codigoSequencial.length == 1){
                codigoSequencial = "0" + codigoSequencial;
            }
            
            if(numVolumes.length == 1){
                numVolumes = "0" + numVolumes;
            }
            
            inputsCodigoBarras.each(function(i, val) {
                var codigoBarras = $(inputsCodigoBarras[i]);
                var sequencia = codigoBarrasBase + i;
                var model = codigoBarras.closest('.produto_volume').model();
                var acao = codigoBarras.closest('div').find('input.acao');
                var codigoBarrasSequenciaVolumes = sequencia + codigoSequencial + numVolumes;
                
                //caso necessario criar
                if(codigoBarras.val().indexOf(codigoBarrasBase) == -1) {
                    if(acao.val() == '')
                        codigoBarras.closest('div').find('input.acao').val('alterar');
                
                    codigoBarras.val(codigoBarrasSequenciaVolumes);
                    codigoBarras.closest('div').find('span.codigoBarras').html(codigoBarrasSequenciaVolumes);
                    model.codigoBarras = codigoBarrasSequenciaVolumes;
                }
            });
            
            $('#volume-codigoBarras').attr('readonly', true).val(codigoBarrasBase + codigoSequencial + numVolumes);
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
        if($('#produto-codigoBarrasBase').val() == ''){
            if($('#volume-CBInterno').val() == 'S'){
                $('#volume-imprimirCB').val('S');
                $('#volume-codigoBarras').val('').attr('readonly', true);
            }else{
                $('#volume-imprimirCB').val('N');
                $('#volume-codigoBarras').attr('readonly', false);
            }
        }else{
            $('#volume-codigoBarras').attr('readonly', true);
            if($('#volume-CBInterno').val() == 'S'){
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
        $('#volume-codigoBarras').attr('readonly', false);
        if ($('#volume-CBInterno').val() == 'S'){
            $('#volume-codigoBarras').attr('readonly', true);
        }
    },
    
    /**
    *
    */
    checarCodBarrasBase: function() {
        $('#volume-codigoBarras').attr('readonly', false);
        if($('#produto-codigoBarrasBase').val() != ''){
            $('#volume-codigoBarras').attr('readonly', true);
        }
    },
    
    /**
    * Verifica se ja existe o codigo de barras informado
    */
    verificarCodigoBarras:function() {
        var acao = $('#volume-acao').val();
        var codigoBarras = $('#volume-codigoBarras').val();
        var codigoBarrasAntigo = $('#volume-codigoBarrasAntigo').val();
        var codigosBarras = $('.codigoBarras');
        
        if (codigoBarras == ""){
            this.verificarEndereco();
            return false;
        }
        
        //verifico se existe volumes neste produto com o mesmo codigo de barras
        var numVezes = 0;
        codigosBarras.each(function(){
            if ( this.value == codigoBarras ){
                numVezes++;
            }
        });
    
        if ( numVezes >= 1 && (codigoBarras != codigoBarrasAntigo)) {
            alert('Este código de barras já foi cadastrado neste produto.');
            $('#volume-codigoBarras').focus();
            return false;
        }
            
        //Verifica se a embalagem esta sendo editada e o codigo é igual
        if((acao == 'alterar') && (codigoBarras == codigoBarrasAntigo)){
            this.verificarEndereco();
            return false;
        }
    
        var idProduto = $('#volume-idProduto').val();
        var grade = $('#volume-grade').val();
        new Wms.Models.ProdutoVolume.verificarCodigoBarras({
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
        var acao = $('#volume-acao').val();
        var endereco = $('#volume-endereco').val();
        var enderecoAntigo = $('#volume-enderecoAntigo').val();
        var idProduto = $('#volume-idProduto').val();
        var grade = $('#volume-grade').val();
        
        if (endereco == ""){
            this.salvarDadosVolume();
            return false;
        }
        
        if (endereco.length != 12){
            alert('Formato inválido de Endereço.');
            $('#volume-endereco').focus();
            return false;
        }
        
        //Verifica se o volume esta sendo editado e o endereco é igual
        if((acao == 'alterar') && (endereco == enderecoAntigo)){
            this.salvarDadosVolume();
            return false;
        }
        
        new Wms.Models.ProdutoVolume.verificarEndereco({
            idProduto:idProduto,
            grade:grade,
            endereco:endereco
        }, this.callback('validarEndereco'));
    },
    
    /**
 * Valida o endereco informado
 * @param {Array} params Matriz de objetos Wms.Models.ProdutoVolume.
 */
    validarEndereco: function( params ){
        if(params.status == 'error'){
            alert(params.msg);
            return false;
        }
        
        this.salvarDadosVolume();
    }
    
});