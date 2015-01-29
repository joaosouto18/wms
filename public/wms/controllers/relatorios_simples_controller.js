/**
 * @tag controllers, home
 */
$( document ).ready(function() {
    $("#gerar").click(function(){
        var relatorio=$(this).attr("data-relatorio");
        var tipo=$(this).attr("data-tipo");

        $("#relatorios-form").append("<input type='hidden' value='"+relatorio+"' name='relatorio' />");
        $("#relatorios-form").append("<input type='hidden' value='"+tipo+"' name='tipo' />");


        //document.getElementById("relatorios-form").submit();
        $("#relatorios-form").submit();
    });
});