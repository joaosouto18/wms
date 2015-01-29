/**
 * @tag controllers, home
 */
$.Controller.extend('Wms.Controllers.Recebimento',
/* @Static */
{
    pluginName: 'recebimento'
},
/* @Prototype */
{
    '{window} load' : function() {
        
        $('#recebimento-divergencia-form').find('input').keypress(function(e){
            if ( e.which == 13 )
                return false;
        });
        
       /**
        * When the page loads, gets all bars to be displayed.
        */
        $('.view-conferencia').live('click', function() {
            
            //url
            var url = this.href;
            // show a spinner or something via css
            var dialog = $('<div id="view-conferencia-dialog" style="display:none"></div>').appendTo('body');
        
            // open the dialog
            dialog.dialog({
                width : 750,
                height : 450,
                resizable: true,
                title : 'Visualizar Conferência',
                // add a close listener to prevent adding multiple divs to the document
                close: function(event, ui) {
                    // remove div with all data and events
                    dialog.remove();
                },
                modal: true
            });
            // load remote content
            dialog.load(
                url, 
                {}, // omit this param object to issue a GET request instead a POST request, otherwise you may provide post parameters within the object
                function (responseText, textStatus, XMLHttpRequest) {
                    // remove the loading class
                    dialog.removeClass('loading');
                }
                );
            //prevent the browser to follow the link
            return false;
        });
    },
    
    '#btnRecontagem click' : function() {
        $('#acaoFinalizacao').val('recontagem');
    },
    
    '#btnDivergencia click' : function() {
        
        var bntNFNaoSelecionada = false;
        
        $('select.notaFiscal').each(function (i, v) {
            if(this.value == 0)
                bntNFNaoSelecionada = true;
        });
        
        if(bntNFNaoSelecionada) {
            alert('Por favor selecione as notas fiscais das divergencias');
            return false;
        }
        
        var bntMotivoNaoSelecionado = false;
        
        $('select.motivosDivergencia').each(function (i, v) {
            if(this.value == 0)
                bntMotivoNaoSelecionado = true;
        });
        
        if(bntMotivoNaoSelecionado) {
            alert('Por favor selecione a observação das divergencias');
            return false;
        }
        
        
        
        if($('#senhaDivergencia').val() == '') {
            alert('Por favor digite a genha para fechar com divergencia');
            $('#senhaDivergencia').focus();
            return false;
        }
        
        $('#acaoFinalizacao').val('divergencia');
    },
    
    '#form-recebimento-conferencia #btnCconferencia click' : function(el , ev) {
        var elements =  $('input.qtdConferida');
        var blnSubmit = true;
        var blnZeros = false;
        
        elements.each(function (i, v) {
            if(this.value == '')  {
                blnSubmit = false;
                alert('Preencha a quantidade do produto');
                this.focus();
                return false;
            }
            
            if(this.value == 0)  {
                blnZeros = true;
            }
        })
        
        if(blnSubmit == true && blnZeros == true) {
            blnSubmit = confirm('Há produtos com quantidades iguais a zero (0). Está certo de continuar?')
        }
        
        if((blnSubmit == true) && ($('#idPessoa').val() == 0)) {
            blnSubmit = confirm('Não há um conferente selecionado, está certo de fechar sem Conferente?');
            
            if(!blnSubmit)
                $('#idPessoa').focus();
        }
        
        return blnSubmit;
    }
});