angular.module("app").controller("InventarioCtrl", function($scope, $http, $filter, $document){
    $scope.maxPerPage = 15;
    $scope.showLoading = false;
    $scope.massActionRoute = null;
    $scope.statusArr = [
        {id: 542, label: "GERADO"},
        {id: 543, label: "LIBERADO"},
        {id: 544, label: "FINALIZADO / CONCLUIDO"},
        {id: 545, label: "CANCELADO"}
    ];
    $scope.criterioForm = {
        rua:undefined,
        ruaFinal:undefined,
        predio:undefined,
        predioFinal:undefined,
        nivel:undefined,
        nivelFinal:undefined,
        apto:undefined,
        aptoFinal:undefined,
        dataInicial1:undefined,
        dataInicial2:undefined,
        dataFinal1:undefined,
        dataFinal2:undefined,
        status:undefined,
        produto:undefined,
        grade:undefined,
        inventario:undefined
    };

    $scope.paginator = {
        pages: [],
        actPage: {},
        size: 0
    };

    $scope.inventarios = [];
    $scope.inventShowed = [];
    $scope.ordenarPor = function (campo) {
        $scope.direction = (campo !== null && $scope.tbOrderBy === campo) ? !$scope.direction : true;
        $scope.tbOrderBy = campo;
    };

    $scope.changePage = function (destination) {
        if ((destination > 0  && ($scope.paginator.actPage.idPage + 1 ) === $scope.paginator.size )
            || (destination < 0 && $scope.paginator.actPage.idPage === 0)) return;

        var page = $scope.paginator.actPage;
        $scope.paginator.actPage = $scope.paginator.pages[ page.idPage + destination ];
    };

    $scope.requestForm = function () {
        var params = {};
        for (var x in $scope.criterioForm){
            var val = $scope.criterioForm[x];
            if (val) params[x] = val;
        }
        getInventarios(params);
    };

    var getInventarios = function (params) {
        $http.post(URL_MODULO + "/index/get-inventarios-ajax", params).then(function (response){
            $scope.inventarios = response.data;
            preparePaginator();
        });
    };

    $scope.massActionRequest = function () {
        var invs = $filter("filter")( $scope.inventarios, {checked: true } );
        if (!invs.length) {
            $.wmsDialogAlert({title: "Alerta!", msg:"Nenhum inventário foi selecionado!"});
            return
        }

        if (!$scope.massActionRoute) {
            $.wmsDialogAlert({title: "Alerta!", msg:"Nenhuma ação foi selecionada!"});
            return
        }
        var params = {"mass-id": []};
        angular.forEach(invs, function (el) {
            params["mass-id"].push(el.id);
        });

        $("#invetGridForm")
            .attr('action', "http://wms.local/inventario/" + $scope.massActionRoute + "?" + $.param(params))
            .attr('target', '_self')
            .submit();

    };

    var preparePaginator = function () {
        var nPages = Math.ceil($scope.inventarios.length / $scope.maxPerPage);
        for (var i = 0; i < nPages; i++) {

            var start = ( i * $scope.maxPerPage );
            var end = ( ( i + 1 ) * $scope.maxPerPage ) - 1 ;

            if (i === nPages) {
                end = $scope.inventarios.length - 1;
            }

            var page = {
                idPage: i,
                label: "Página - " + (i + 1),
                indexStart: start,
                indexEnd: end
            };
            $scope.paginator.pages.push(page);
            if (i === 0 ) $scope.paginator.actPage = page;
        }
        $scope.paginator.size = nPages;
    };

    $scope.typeSensitiveComparator = function(v1, v2) {
        // If we don't get strings, just compare by index
        if (angular.isNumber(Number(v1.value)) && angular.isNumber(Number(v2.value))) {
            return (Number(v1.value) < Number(v2.value)) ? -1 : 1;
        } else if (v1.type !== 'string' || v2.type !== 'string') {
            return (v1.index < v2.index) ? -1 : 1;
        }

        // Compare strings alphabetically, taking locale into account
        return v1.value.localeCompare(v2.value);
    };

    $scope.checkSelected = function (inventario) {
        $scope.inventarios[$scope.inventarios.findIndex(function (el) {
            return (el === inventario)
        })].checked = !inventario.checked;
        var actPag = $scope.paginator.actPage;
        $scope.paginator.actPage.selectedAll = ($filter("filter")(
            $scope.inventarios.slice(actPag.indexStart, actPag.indexEnd ),
            {checked: true }).length === (actPag.indexEnd - actPag.indexStart)) ;
    };
    
    $scope.selectAllPage = function() {
        var page = $scope.paginator.actPage;
        angular.forEach($scope.inventarios, function (inv, k) {
            if ( k >= page.indexStart && k <= page.indexEnd){
                $scope.inventarios[k].checked = page.selectedAll;
            }
        })
    };

    getInventarios([]);
    $scope.ordenarPor("id");
    
}).filter("interval", function () {
    return function (input, interval) {
        if (input.length > 0) {
            var output = [];
            var start = Number(interval.start);
            var end = Number(interval.end);
            $.each( input, function (k, v) {
                if (k >= start && k <= end) {
                    output.push(v);
                }
            });

            return output;
        }
    }
});