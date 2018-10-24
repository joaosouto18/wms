/**
 * @tag controllers, home
 * Displays a table of pessoa_telefones.	 Lets the user 
 * ["Wms.Controllers.pessoaTelefone.prototype.form submit" create], 
 * ["Wms.Controllers.pessoaTelefone.prototype.&#46;edit click" edit],
 * or ["Wms.Controllers.pessoaTelefone.prototype.&#46;destroy click" destroy] pessoa_telefones.
 */
$.Controller.extend('Wms.Controllers.PessoaTelefone',
/* @Static */
{
    pluginName: 'pessoaTelefone'
},
/* @Prototype */
{
    /**
     * When the page loads, gets all pessoa_telefones to be displayed.
     */
    "{window} load": function(){
        if(!$("#div-lista-telefones").length){
            $("#fieldset-telefones-cadastrados").append($('<div/>').attr('id','div-lista-telefones'));
            var idPessoa = $('#fieldset-telefone #telefones-idPessoa').val();
            if (idPessoa != '') {
                Wms.Models.PessoaTelefone.findAll({idPessoa:idPessoa}, this.callback('list'));
            }
        }
    },
    /**
     * Displays a list of pessoa_telefones and the submit form.
     * @param {Array} pessoa_telefones An array of Wms.Models.PessoaTelefone objects.
     */
    list: function( pessoa_telefones ){
        
        $('#div-lista-telefones').html(this.view('init', {
            pessoa_telefones:pessoa_telefones
        } ));
    },
    /**
     * Responds to the create form being submitted by creating a new Wms.Models.PessoaTelefone.
     * @param {jQuery} el A jQuery wrapped element.
     * @param {Event} ev A jQuery event whose default action is prevented.
     */
    '#btn-salvar-telefone click': function(el, ev) {
        if ($('#fieldset-telefone #telefones-idTipo').val() == '') {
            alert('Infome um tipo para o telefone');
            return;
        }
        
        var valores = $('#fieldset-telefone').formParams().telefones;
        var id = $("#fieldset-telefone #telefones-id").val(); 
        
        if (valores.ramal == '') {
            delete valores.ramal;
        }
        
        if (id != '') {
            valores.acao = id.indexOf('-new') == -1 ? 'alterar' : 'incluir';
            valores.lblTipoTelefone = $('#fieldset-telefone #telefones-idTipo option:selected').text();
            pessoa_telefone = new Wms.Models.PessoaTelefone(valores);
            this.show(pessoa_telefone);
        } else {
            var d = new Date();
            valores.id = d.getTime()+ '-new';
            valores.acao = 'incluir';
            valores.lblTipoTelefone = $('#fieldset-telefone #telefones-idTipo option:selected').text();
            pessoa_telefone = new Wms.Models.PessoaTelefone(valores);
            $('#div-lista-telefones').append( this.view("show", pessoa_telefone) );
        }
        $("#fieldset-telefone input[type=text], #fieldset-telefone input[type=hidden], #fieldset-telefone select").val('');
        $('#fieldset-telefone legend').html('Identificação');
        $('#fieldset-telefone button').html('Adicionar');
    },
    /**
     * Creates and places the edit interface.
     * @param {jQuery} el The pessoa_telefone's edit link element.
     */
    '.pessoa_telefone click': function( el ){
        var pessoa_telefone = el.model();
        // altera informacao
        $('#fieldset-telefone legend').html('Editando telefone');
        $('#fieldset-telefone button').html('Atualizar');
        
        $('#fieldset-telefone #telefones-id').val(pessoa_telefone.id);
        $('#fieldset-telefone #telefones-ddd').val(pessoa_telefone.ddd);
        $('#fieldset-telefone #telefones-idTipo').val(pessoa_telefone.idTipo);
        $('#fieldset-telefone #telefones-numero').val(pessoa_telefone.numero);
        $('#fieldset-telefone #telefones-ramal').val(pessoa_telefone.ramal);
        //$('#fieldset-telefone').populate(pessoa_telefone);
    },
    /**
     * Shows a pessoa_telefone's information.
     */
    show: function( pessoa_telefone ){
        pessoa_telefone.elements().replaceWith(this.view('show',pessoa_telefone));
    },
    /**
     *	 Handle's clicking on a pessoa_telefone's destroy link.
     */
    '.btn-excluir-telefone click': function(el, ev){
        //evita a propagação do click para a div
        ev.stopPropagation();
        
        if(confirm("Tem certeza que deseja excluir este telefone?")){
            var model = el.closest('.pessoa_telefone').model();
            var id = model.id.toString();
            
            //se é um telefone existente (não haja a palavra '-new' no id)
            if (id.indexOf('-new') == -1) {
                //limpa o ID
                id.replace('-new', '');
                //adiciona à fila para excluir 
                $('<input/>', {
                    name: 'telefones[' + id + '][acao]',
                    value: 'excluir',
                    type: 'hidden'
                }).appendTo('#fieldset-telefones-cadastrados'); 
            }
            //remove a div do telefone
            model.elements().remove();
        }
    }
});