angular.module("wms").controller("previewerCtrl", function ($scope, $http, $window, shareDataService, uiDialogService) {

    $scope.modelos = [];

    let labelsCriterio = {
        E: "Endereço",
        P: "Produto",
    };

    let requestModelos = function () {
        $http.get(URL_MODULO + '/modelo-inventario/get-modelos-inventarios-ajax').then(function (response) {
            $scope.modelos = response.data;
        }).then(function () {
            angular.forEach($scope.modelos, function (obj) {
                if (obj.isDefault) {
                    obj.dscModelo += " (Default)";
                    $scope.modSel = obj;
                }
            })
        })
    };

    let getDataShared = function() {
        $scope.criterio = shareDataService.getDataShared("criterio");
        $scope.itens = shareDataService.getDataShared("itens");
        $scope.gridColumns = shareDataService.getDataShared("gridColumns");
    };

    getDataShared();
    requestModelos();
    $scope.lblCriterio = labelsCriterio[$scope.criterio];

    $scope.ordenarPor = function (campo) {
        $scope.direction = (campo !== null && $scope.direction === campo) ? !$scope.direction : true;
        $scope.orderBy = campo;
    };

    $scope.drop = function(obj) {
        $scope.itens.splice($scope.itens.findIndex(function (el) { return (el === obj) }), 1 );
        if ($scope.itens.length === 0) {
            uiDialogService.close("previewer-inventario.html")
        }
    };

    $scope.criarInventario = function () {
        if (isEmpty($scope.dscInventario)) {
            uiDialogService.dialogConfirm("Não foi definido um nome para o inventário. Deseja relamente prosseguir?", null, "Sim", "Não", function () {
                postInventario();
            });
        } else {
            postInventario();
        }
    };

    let postInventario = function () {
        if ($scope.itens.length)
            $http.post(URL_MODULO + '/index/criar-inventario', {
                criterio: $scope.criterio,
                descricao: $scope.dscInventario,
                selecionados: $scope.itens,
                modelo: $scope.modSel
            }).then(function () {
                $window.location.href = URL_MODULO
            }).catch(function (err) {
                uiDialogService.dialogAlert(err.data);
            })
    };
});