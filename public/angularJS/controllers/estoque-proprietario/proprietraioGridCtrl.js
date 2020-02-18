angular.module("wms").controller("proprietarioGridCtrl", function ($scope, $http, $filter) {

    $scope.results = [];
    $scope.noResults = false;
    $scope.showLoading = true;
    $scope.objectFilter = {};
    $scope.params = {
        codProduto: null,
        proprietario: null,
        saldo: false,
        historico: false,
    };

    let configGridColumns = function () {
        $scope.gridColumns = [
            { name: 'nomProp'      , label: 'Proprietário'      , width: '7%'    , filter: {size: 8 } },
            { name: 'codProduto'   , label: 'Código'            , width: '7%'    , filter: {size: 8 } },
            { name: 'dscProduto'   , label: 'Produto'           , width: '26%'   , filter: {size: 43 } },
            { name: 'qtdEstq'      , label: 'Qtd Estq'          , width: '6.5%'  , filter: false },
            { name: 'qtdPend'      , label: 'Pendente p/ Entrar', width: '6.5%'  , filter: false },
            { name: 'dthValidade'  , label: 'Validade'          , width: '7.5%'  , filter: false },
        ];
    };

    $scope.buscar = function() {
        let strParams = "";
        if (!isEmpty($scope.params.codProduto))
            strParams += "/codProduto/" + $scope.params.codProduto;

        if (!isEmpty($scope.params.codProduto))
            strParams += "/codProp/" + $scope.params.proprietario.id;

        if (!isEmpty($scope.params.codProduto))
            strParams += "/saldo/" + $scope.params.saldo;

        if (!isEmpty($scope.params.codProduto))
            strParams += "/historico/" + $scope.params.historico;

        $http.get(URL_MODULO + "/relatorio_get-saldo-proprietraio-ajax" + strParams).then(function (response){
            if (isEmpty(response.data.results)) {
                $scope.noResults = true;
            } else {
                $scope.results = response.data.results;
            }
        }).then(function () {
            $scope.showLoading = false ;
            configGridColumns();
        });
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
                        let blob = new Blob([response.data], { type: 'application/'+destino });
                        let url = URL.createObjectURL(blob);
                        window.open(url, '_blank');
                    } catch (ex) {
                        console.log(ex);
                    }
                }
            )
    };

    $scope.ordenarPor("contagem");
});