
$(document).ready(function(){

    /**
     * Calcula a qtdConferida total do produto
     */
    function calcTotalQuantidade() {
            
        var qtdConferida = parseFloat($('#qtdConferida').val()) || 0;
        var unidadePorEmbalagem = parseFloat($('#unidadePorEmbalagem').val()) || 0;
        var total = qtdConferida * unidadePorEmbalagem;
            
        $('#totalQuantidade').html(total);
    }
    
    
    // caso produto contado por embalagem
    if($('#recebimento-embalagem-quantidade-form').size() > 0) {
        
        $('#qtdConferida').keyup(function() {
            calcTotalQuantidade();
        });
        
        // calc quando entra
        calcTotalQuantidade();
    }
});
