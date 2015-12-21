/**
 * @tag controllers, home
 */
$( document ).ready(function() {
    $("#identificacao-botaoAdd").click(function(){
        var numPraca=$('#identificacao-num_pracas').val(),numPracaProx;
        numPracaProx=parseInt(numPraca)+1;

        $("#pracas").append('<div style="clear:both" id="tdRemove_'+numPracaProx+'">       <div class="field"><label for="identificacao-faixa1_'+numPracaProx+'" class="field optional">Faixa de CEP</label>            <input type="text" name="identificacao[faixa1_'+numPracaProx+']" id="identificacao-faixa1_'+numPracaProx+'" value="" style="min-width:150px"></div>  <div class="field"><label for="identificacao-faixa2_'+numPracaProx+'" class="field optional">Até</label>            <input type="text" name="identificacao[faixa2_'+numPracaProx+']" id="identificacao-faixa2_'+numPracaProx+'" value="" style="min-width:150px"></div>  <dd style="height:15px"></dd> <button name="identificacao[botaoAdd]" id="remover" data-id="'+numPracaProx+'" type="button" class="header" style="background-color:#666">Remover</button>            </div> ');


        $('#identificacao-num_pracas').val(numPracaProx);
    });

    $("#remover").live('click',function(){
        var data=$(this).attr('data-id');
        $("#tdRemove_"+data).remove();
    });
});