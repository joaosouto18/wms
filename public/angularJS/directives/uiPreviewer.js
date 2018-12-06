angular.module('wms').directive("uiPreviewer", function ($http, $window) {

    let arrConfigColumns = {
        endereco:  [
            { name: 'dscEndereco', label: 'Endereço', type: 'ordenator', width: '13%'},
            { name: 'caracEnd', label: 'Característica', type: 'ordenator', width: '19%'},
            { name: 'dscArea', label: 'Área', type: 'ordenator', width: '32%'},
            { name: 'dscEstrutura', label: 'Estrutura', type: 'ordenator', width: '32%'}
        ],
        produto: [
            { name: 'codProduto', label: 'Código', type: 'ordenator', width: '13%'},
            { name: 'dscProduto', label: 'Descrição', type: 'ordenator', width: '13%'},
            { name: 'grade', label: 'Grade', type: 'ordenator', width: '13%'},
            { name: 'dscEndereco', label: 'Endereço', type: 'ordenator', width: '13%'}
        ]
    };

    let configGridHeaders = function (criterio) {
        let arrColumns = arrConfigColumns[criterio];
        arrColumns.push({ name: 'dscEndereco', label: 'Endereço', type: 'dropAction'});
        return arrColumns;
    };

    return {
        templateUrl: 'uiPreviewer-inventario.html',
        scope: {
            description: "@",
            itens: "=",
            display: "=",
            criterio: "@",
        },
        restrict: 'E',
        link: function (scope) {

            let requestModelos = function () {
                $http.get(URL_MODULO + '/index/get-modelos-inventarios-ajax').then(function (response) {
                    scope.modelos = response.data;
                }).then(function () {
                    angular.forEach(scope.modelos, function (obj) {
                        console.log(obj);
                        if (obj.isDefault) {
                            console.log("entrou " + obj.dscModelo);
                            scope.modeloSelecionado = obj.id;
                        }
                    })
                })
            };

            requestModelos();
            scope.close = function () {
                scope.display = false
            };

            scope.gridColumns = configGridHeaders(scope.criterio);

            scope.criarInventario = function () {
                console.log(scope.dscNomeInventario);
                console.log(scope.modeloSelecionado.id);
                // if (scope.itens) {
                //     $http.post(URL_MODULO + '/index/criar-inventario', {
                //         criterio: scope.criterio,
                //         descricao: scope.dscNomeInventario,
                //         selecionados: scope.itens,
                //         modelo: scope.modeloSelecionado.id
                //     }).then(function () {
                //         $window.location.href = URL_MODULO
                //     })
                // } else {
                //     $.wmsDialogAlert({
                //         title: "Atenção!",
                //         msg: "Nenhum elemento foi adicionado na lista"
                //     })
                // }
            };

            scope.drop = function(obj) {
                scope.itens.splice(scope.itens.findIndex(function (el) { return (el === obj) }), 1 );
                if (scope.itens.length === 0) {
                    scope.close();
                }
            };

            scope.ordenarPor = function (campo) {
                scope.direction = (campo !== null && scope.direction === campo) ? !scope.direction : true;
                scope.orderBy = campo;
            };
        }
    }
});