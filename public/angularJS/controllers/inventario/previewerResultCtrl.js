angular.module("wms").controller("previewerResultInventarioCtrl", function ($scope, $http, $window, shareDataService, uiDialogService) {

    $scope.inventario = {};
    $scope.results = [];
    $scope.showLoading = true;
    $scope.objectFilter = {};
    let maskEndereco = "";

    let configGridColumns = function () {
        $scope.gridColumns = [
            { name: 'endereco'          , label: 'Endereço' , width: '9%'  , filter: {size: 10 , masked: true  , maskFilter: maskEndereco}},
            { name: 'codProduto'        , label: 'Código'   , width: '7%'  , filter: {size: 7  } },
            { name: 'dscProduto'        , label: 'Produto'  , width: '30%' , filter: {size: 45 } },
            { name: 'grade'             , label: 'Grade'    , width: '11%' , filter: {size: 13 } },
            { name: 'elemento'          , label: 'Vol.'     , width: '8%'  , filter: false },
            { name: 'lote'              , label: 'Lote'     , width: '7%'  , filter: {size: 7 } },
            { name: 'validade'          , label: 'Validade' , width: '8%'  , filter: false },
            { name: 'qtdEstoque'        , label: 'Estoque'  , width: '7%'  , filter: false },
            { name: 'qtdInventariada'   , label: 'Qtd. Inv.', width: '7%'  , filter: false },
            { name: 'qtdDiff'           , label: 'Diff'     , width: '6%'  , filter: false }
        ];
    };

    let getInventario = function(id) {
        $http.get(URL_MODULO + "/index/get-preview-result-ajax/id/" + id).then(function (response){
            $scope.inventario = response.data.inventario;
            $scope.results = response.data.results;
            maskEndereco = response.data.mask;
        }).then(function () {
            $scope.showLoading = false ;
            configGridColumns();
        });
    };

    $scope.prepare = function () {
        getInventario(shareDataService.getDataShared("idInventario"));
    };

    $scope.ordenarPor = function (campo) {
        $scope.direction = (campo !== null && $scope.tbOrderBy === campo) ? !$scope.direction : true;
        $scope.tbOrderBy = campo;
    };

    $scope.atualizar = function () {
        uiDialogService.dialogConfirm("O inventário será APLICADO ao estoque. Deseja relamente prosseguir?", "ATENÇÃO - AÇÃO IRREVERSÍVEL", "Sim", "Não", function () {
            $window.location.href = URL_MODULO + "/index/atualizar/id/" + $scope.inventario.id;
        });
    };
    $scope.ordenarPor("endereco");
});