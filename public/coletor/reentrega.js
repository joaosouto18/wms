jQuery(document).ready(function ($) {
     $('#codEtiqueta').change(function () {
        var etiqueta = $('#codEtiqueta').val();
        $.ajax({
            url: 'get-nota-or-cod-barras-by-campo-bipado',
            type: 'post',
            dataType: 'json',
            data: {
                etiqueta: etiqueta
            },
            beforeSend: function () {
                $("#inserir").html('<center><img src="/img/ajax-loader.gif" width="31" height="31"/>Processando...</center>');
                $("#inserir").show();
                $("#submit").hide();
            },
            success: function (dados) {
                $("#submit").show();
                $("#inserir").hide();
                if (dados == '') {
                    alert('Etiqueta Invalida');
                    return false;
                }
                $('#notaFiscal').val(dados[0]['numeroNf']);
            }
        })
    });
});


