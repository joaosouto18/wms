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

    let labelsCriterio = {
        endereco: "Endereço",
        produto: "Produto",
    };

    let postInventario = function () {
        $http.post(URL_MODULO + '/index/criar-inventario', {
            criterio: scope.criterio,
            descricao: scope.dscInventario,
            selecionados: scope.itens,
            modelo: scope.modSel
        }).then(function (response) {
            console.log(response);
            //$window.location.href = URL_MODULO
        })
    };

    let checkNomeInventario = function ( nome ) {
        if (isEmpty(nome)) {
            let test = $.wmsDialogConfirm({
                title: "Atenção!",
                msg: "Não foi definido um nome para o inventário, deseja prosseguir?",
                width: "auto",
                height: "auto",
                buttons: {confirm:'Sim', reject:'Não'}
            });

            console.log(test);
        }
    };

    return {
        templateUrl: function(elem,attrs) {
            return attrs.templateUrl || 'default.html'
        },
        scope: {
            description: "@",
            itens: "=",
            display: "=",
            criterio: "@",
        },
        restrict: 'E',
        link: function (scope) {

            scope.modelos = [];
            let requestModelos = function () {
                $http.get(URL_MODULO + '/index/get-modelos-inventarios-ajax').then(function (response) {
                    scope.modelos = response.data;
                }).then(function () {
                    angular.forEach(scope.modelos, function (obj) {
                        if (obj.isDefault) {
                            obj.dscModelo += " (Default)";
                            scope.modSel = obj;
                        }
                    })
                })
            };

            scope.lblCriterio = labelsCriterio[scope.criterio];

            requestModelos();
            scope.close = function () {
                scope.display = false
            };

            scope.gridColumns = configGridHeaders(scope.criterio);

            scope.criarInventario = function () {
                checkNomeInventario(scope.dscInventario)
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