function verificarExpedicao(){
    var expedicao=$('#idExped').val();
    if (expedicao==''){
        alert('Digite uma expedicao');
        return false;
    }

    return true;
}

$('#btnFinalizar').live('click',function(){
    var valExped=$('#idExped').val();

    var idAtual=$('#idAtual').val();

    if (valExped==''){
        alert('Digite uma expedicao');
        return false;
    } else if (idAtual==valExped) {
        alert('Digite uma expedicao diferente da atual');
        return false;
    }

    var act=$('#formAgrupar').attr('action');
    var actionNova=act+"/idExpedicaoNova/"+valExped;
    $('#formAgrupar').attr('action',actionNova);



    $('#formAgrupar').submit();
});
