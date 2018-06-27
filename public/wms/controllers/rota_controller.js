/**
 * @tag controllers, home
 */
$( document ).ready(function() {
    $("#identificacao-botaoAddRota").click(function(){
        var numRota=$('#identificacao-num_rotas').val(),numRotaProx,url;
        numRotaProx=parseInt(numRota)+1;

        url=URL_SISTEMA+"/rota/getPracasAjax";

        $.ajax(url, {
            success: function(data) {
                $("#rotas").append('<div style="clear:both" id="tdRemove_'+numRotaProx+'"> <div class="field"><label for="identificacao-praca_'+numRotaProx+'" class="field optional">Praça</label><select name="identificacao[praca_'+numRotaProx+']" id="identificacao-praca_'+numRotaProx+'" mostrarselecione="1" style="min-width:150px"><option value="" label="Selecione...">Selecione...</option>'+data+'</select></div> <dd style="height:15px"></dd> <button name="identificacao[botaoAdd]" id="remover" data-id="'+numRotaProx+'" type="button" class="header" style="background-color:#666">Remover</button> </div>');
            },
            error: function() {
                $('#notification-bar').text('An error occurred');
            }
        });

        $('#identificacao-num_rotas').val(numRotaProx);
    });


});