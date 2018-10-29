var clickSelection=false;

/**
 * @tag controllers, home
 */
$( document ).ready(function() {
    $("input[name*='expedicao[]']").live('click', function() {
        $('#gerar').attr('style','display:block');
        clickSelection=false;
        $("input[name*='expedicao[]']").each(function( index, value ){
            if ( $(this).prop('checked') ){
                clickSelection=true;
            }
        });

        if (clickSelection){
            $('#gerar').attr('style','display:block');
        } else {
            $('#gerar').attr('style','display:none');
        }
    });

    /*
     * Valida seleção de expedições
     * @array checkBoxes de expedições
     * return action submit / alert
     */
    $("#gerar").live('click', function() {
        clickSelection=false;
        $("input[name*='expedicao[]']").each(function( index, value ){
            if ( $(this).prop('checked') ){
                clickSelection=true;
            }
        });

        if (!clickSelection){
            alert('Selecione pelo menos um registro');
            return false;
        }

    });
});