<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Abertura de Chamados</title>

</head>

<body>
<div class="container">
    <br>

    <form class="form-horizontal" method="POST" action="<?php echo '/filial/index' ?>" enctype="multipart/form-data">
        <fieldset>

            <!-- Título do formulário -->
            <legend>Logomarca</legend>

            <!-- Campo: Nome -->
            <div class="form-group">
                <label class="col-md-4 control-label" for="nome">Nome</label>
                <div class="col-md-4">
                    <input size="60" id="nome" name="nome" placeholder="Logomarca atual" class="form-control input-md" required="" type="text">
                </div>
            </div>

            <!-- Campo: anexo -->
            <div class="form-group">
                <label class="col-md-4 control-label" for="arquivo">Anexo</label>
                <div class="col-md-4">
                    <input size="30" id="arquivo" name="arquivo" class="input-file" type="file">
                    <span class="help-block">2MB por mensagem</span>
                </div>
            </div>

            <!-- Botão Enviar -->
            <center>
                <div class="form-group">
                    <label class="col-md-4 control-label" for="submit"></label>
                    <div class="col-md-4">
                        <button type="submit" name="submit" class="btn btn-inverse">Enviar</button>
                    </div>
                </div>

        </fieldset>
    </form>

</div>
</body>
</html>

