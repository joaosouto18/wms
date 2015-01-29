var clickSelection=false;
/**
 * @tag controllers, home
 */
$.Controller.extend('Wms.Controllers.Expedicao',
/* @Static */
{
    pluginName: 'expedicao'
},
/* @Prototype */
{
    '{window} load' : function() {

        $('#centrais a').live('click', function() {
            var urlImpressao = $(this).attr('href');
            urlImpressao = urlImpressao+'?'+$('#cargas input:checked').serialize();
            window.location.href = urlImpressao;
            return false;
        });
        
        /*
         * Exibe/Oculta botão gerar
         * @array checkBoxes de expedições
         */
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
            
            if (clickSelection){                
                $('#relatorio-picking-listar').submit();
            } else {                
                alert('Selecione pelo menos uma expedição');
            }
            
        });
        
        
    }

});