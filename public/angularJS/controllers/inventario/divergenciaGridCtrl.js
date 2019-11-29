angular.module("wms").controller("divergenciaGridCtrl", function ($scope, $http, $filter, $window, shareDataService) {

    $scope.inventario = {};
    $scope.listDivergencias = [];
    $scope.results = [];
    $scope.noResults = false;
    $scope.showLoading = true;
    $scope.objectFilter = {};
    let maskEndereco = "";

    let configGridColumns = function () {
        $scope.gridColumns = [
            { name: 'contagem'     , label: 'Contagem'      , width: '7%'    , filter: {size: 8 } },
            { name: 'endereco'     , label: 'Endereço'      , width: '9%'    , filter: {size: 11 , masked: true , maskFilter: maskEndereco}},
            { name: 'codProduto'   , label: 'Código'        , width: '7%'    , filter: {size: 8 } },
            { name: 'dscProduto'   , label: 'Produto'       , width: '26%'   , filter: {size: 43 } },
            { name: 'grade'        , label: 'Grade'         , width: '9%'    , filter: {size: 12 } },
            { name: 'loteConf'     , label: 'Lote Conf'     , width: '6.5%'  , filter: {size: 7 } },
            { name: 'loteEstq'     , label: 'Lote Estq'     , width: '6.5%'  , filter: {size: 7 } },
            { name: 'validadeConf' , label: 'Valid. Conf'   , width: '7.5%'  , filter: false },
            { name: 'validadeEstq' , label: 'Valid. Estq'   , width: '7.5%'  , filter: false },
            { name: 'qtdConf'      , label: 'Qtd Conf'      , width: '6.5%'  , filter: false },
            { name: 'qtdEstq'      , label: 'Qtd Estq'      , width: '6.5%'  , filter: false }
        ];
    };

    let getDivergencias = function(id) {
        $http.get(URL_MODULO + "/index/get-divergencias-ajax/id/" + id).then(function (response){
            $scope.inventario = response.data.inventario;
            if (isEmpty(response.data.results)) {
                $scope.noResults = true;
            } else {
                $scope.results = response.data.results;
            }
            maskEndereco = response.data.mask;
        }).then(function () {
            $scope.showLoading = false ;
            configGridColumns();
        });
    };

    $scope.prepare = function () {
        getDivergencias(shareDataService.getDataShared("idInventario"));
    };

    $scope.ordenarPor = function (campo) {
        $scope.direction = (campo !== null && $scope.tbOrderBy === campo) ? !$scope.direction : true;
        $scope.tbOrderBy = campo;
    };

    $scope.export = function (destino) {

        let result = $filter("filter")($scope.results, $scope.objectFilter);
        result = $filter("orderBy")(result, $scope.tbOrderBy, $scope.direction, typeSensitiveComparatorFn());

        let params = {
            destino: destino,
            divergencias: result
        };

        let config = { responseType: 'arraybuffer' };

        $http.post(URL_MODULO + '/index/export-divergencias-ajax', params, config)
            .then(function (response) {
                    try {
                        var blob = new Blob([response.data], { type: 'application/'+destino });
                        var url = URL.createObjectURL(blob);
                        window.open(url, '_blank');
                    } catch (ex) {
                        console.log(ex);
                    }
                }
            )
    };

    $scope.ordenarPor("contagem");
});