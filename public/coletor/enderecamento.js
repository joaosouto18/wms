function focusInput() {
    document.getElementById('uma').focus();
    hiddenElement("formulario-nivel");
}

function hiddenElement(id) {
    document.getElementById(id).style.display= "none";
}

function showElement(id) {
    document.getElementById(id).style.display= "block";
}

jQuery(document).ready(function ($) {
    $('#endereco').blur(function () {
        $.ajax({
            url: '/mobile/enderecamento_manual/verificar-caracteristica-endereco/id/' + $('#endereco').val(),
            type: 'GET',
            dataType: 'html',
            beforeSend: function () {
                $("#inserir").html('<center><img src="/img/ajax-loader.gif" width="31" height="31"/>Processando...</center>');
                $("#submit").hide();
            },
            success: function(data) {
                var capacidadePicking = '';
                if (data == 'true') {
                    capacidadePicking = '<div class="field">' +
                        '<label>Capac. Picking:</label>' +
                        '<input style="width: 99%" maxlength="100" size="40" type="text" name="capacidadePicking" id="capacidadePicking" value="" />' +
                        '</div>';
                }
                $('#inserir').html(capacidadePicking);
                $("#submit").show();
            }
        });
    });

    $('#nivel').keyup(function () {
        $.ajax({
            url: '/mobile/enderecamento_manual/verificar-caracteristica-endereco/endereco/' + $('#endereco').val() + '/nivel/' + $('#nivel').val(),
            type: 'GET',
            dataType: 'html',
            beforeSend: function () {
                $("#inserir").html('<center><img src="/img/ajax-loader.gif" width="31" height="31"/>Processando...</center>');
                $("#submit").hide();
            },
            success: function(data) {
                var capacidadePicking = '';
                if (data == 'true') {
                    capacidadePicking = '<div class="field">' +
                        '<label>Capac. Picking:</label>' +
                        '<input style="width: 99%" maxlength="100" size="40" type="text" name="capacidadePicking" id="capacidadePicking" value="' + $('#capacidadePicking').val() + '" />' +
                        '</div>';
                }
                $('#inserir').html(capacidadePicking);
                $("#submit").show();
            }
        });
    });
});


