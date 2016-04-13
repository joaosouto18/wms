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
            success: function (dados) {
                if (dados == '') {
                    alert('Etiqueta Invalida');
                    return false;
                }
                $('#notaFiscal').val(dados[0]['numeroNf']);
            }
        })
    });
});


