angular.module("wms").controller("listGridInventarioCtrl", function($scope, $rootScope, $http, $filter, $window, uiDialogService, shareDataService){

    $scope.typeSensitiveComparator = typeSensitiveComparatorFn();
    $scope.maxPerPage = 15;
    $scope.inventarios = [];
    $scope.showLoading = true;
    $scope.showNoResults = false;
    $scope.showList = false;
    $scope.massActionRoute = null;

    $scope.statusArr = [];

    $scope.clearForm = function() {
        $scope.criterioForm = {
            rua: undefined,
            ruaFinal: undefined,
            predio: undefined,
            predioFinal: undefined,
            nivel: undefined,
            nivelFinal: undefined,
            apto: undefined,
            aptoFinal: undefined,
            dataInicial1: undefined,
            dataInicial2: undefined,
            dataFinal1: undefined,
            dataFinal2: undefined,
            status: undefined,
            produto: undefined,
            grade: undefined,
            inventario: undefined
        };
    };
    $scope.clearForm();

    let newPaginator = function() {
        return {
            pages: [],
            actPage: {},
            size: 0
        };
    };

    $scope.paginator = newPaginator();

    $scope.ordenarPor = function (campo) {
        $scope.direction = (campo !== null && $scope.tbOrderBy === campo) ? !$scope.direction : true;
        $scope.tbOrderBy = campo;
    };

    $scope.changePage = function (destination) {
        if ((destination > 0  && ($scope.paginator.actPage.idPage + 1 ) === $scope.paginator.size )
            || (destination < 0 && $scope.paginator.actPage.idPage === 0)) return;

        let page = $scope.paginator.actPage;
        $scope.paginator.actPage = $scope.paginator.pages[ page.idPage + destination ];
    };

    $scope.requestForm = function () {
        $scope.showLoading = true ;
        $scope.showNoResults = false ;
        $scope.showList = false;
        let params = {};
        for (let x in $scope.criterioForm){
            let val = $scope.criterioForm[x];
            if (val) params[x] = val;
        }
        getInventarios(params);
    };

    let getInventarios = function (params) {
        if (isEmpty($scope.statusArr)) params['getStatusArr'] = true;
        $http.post(URL_MODULO + "/index/get-inventarios-ajax", params).then(function (response){
            $scope.inventarios = response.data.inventarios.reverse();
            if (!isEmpty(response.data.statusArr)) $scope.statusArr = response.data.statusArr;
            preparePaginator();
        }).then(function () {
            $scope.showLoading = false ;
            $scope.showList = ($scope.inventarios.length > 0);
            $scope.showNoResults = ($scope.inventarios.length === 0);
        });
    };

    $scope.massActionRequest = function () {
        let invs = $filter("filter")( $scope.inventarios, {checked: true } );
        if (!invs.length) {
            $.wmsDialogAlert({title: "Alerta!", msg:"Nenhum inventário foi selecionado!"});
            return
        }

        if (!$scope.massActionRoute) {
            $.wmsDialogAlert({title: "Alerta!", msg:"Nenhuma ação foi selecionada!"});
            return
        }
        let params = {"mass-id": []};
        angular.forEach(invs, function (el) {
            params["mass-id"].push(el.id);
        });

        $("#invetGridForm")
            .attr('action', "http://wms.local/inventario/" + $scope.massActionRoute + "?" + $.param(params))
            .attr('target', '_self')
            .submit();

    };

    let preparePaginator = function () {
        $scope.paginator = newPaginator();
        let nPages = Math.ceil($scope.inventarios.length / $scope.maxPerPage);
        for (let i = 0; i < nPages; i++) {

            let start = ( i * $scope.maxPerPage );
            let end = ( ( i + 1 ) * $scope.maxPerPage ) - 1 ;

            if (i === nPages) {
                end = $scope.inventarios.length - 1;
            }

            let page = {
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

    $scope.checkSelected = function (inventario) {
        $scope.inventarios[$scope.inventarios.findIndex(function (el) {
            return (el === inventario)
        })].checked = !inventario.checked;
        let actPag = $scope.paginator.actPage;
        $scope.paginator.actPage.selectedAll = ($filter("filter")(
            $scope.inventarios.slice(actPag.indexStart, actPag.indexEnd ),
            {checked: true }).length === (actPag.indexEnd - actPag.indexStart)) ;
    };
    
    $scope.selectAllPage = function() {
        let page = $scope.paginator.actPage;
        angular.forEach($scope.inventarios, function (inv, k) {
            if ( k >= page.indexStart && k <= page.indexEnd){
                $scope.inventarios[k].checked = page.selectedAll;
            }
        })
    };

    $scope.interromper = function (idInventario) {
        uiDialogService.dialogConfirm("O inventário será INTERROMPIDO, todos os produtos/endereços não inventariados serão desconsiderados. Deseja relamente prosseguir?", "ATENÇÃO - AÇÃO IRREVERSÍVEL", "Sim", "Não", function () {
            $window.location.href = URL_MODULO + "/index/interromper/id/" + idInventario;
        });
    };

    $scope.cancelar = function (idInventario) {
        uiDialogService.dialogConfirm("O inventário será CANCELADO, todo o processo executado será desconsiderado. Deseja relamente prosseguir?", "ATENÇÃO - AÇÃO IRREVERSÍVEL", "Sim", "Não", function () {
            $window.location.href = URL_MODULO + "/index/cancelar/id/" + idInventario;
        });
    };

    $scope.atualizar = function (idInventario) {
        uiDialogService.dialogConfirm("O inventário será APLICADO ao estoque. Deseja relamente prosseguir?", "ATENÇÃO - AÇÃO IRREVERSÍVEL", "Sim", "Não", function () {
            $window.location.href = URL_MODULO + "/index/atualizar/id/" + idInventario;
        });
    };

    $scope.showPreviewerResult = function (id) {
        shareDataService.addNewData("idInventario", id);
        uiDialogService.dialogModal("previewer-result-inventario.html", true, "Resultado do inventário",1080,null,'false',['center', 80]);
    };

    $scope.showDivergencias = function (id) {
        shareDataService.addNewData("idInventario", id);
        uiDialogService.dialogModal("divergencia-grid.html", true, "Divergências do inventário",1200,null,'false',['center', 80]);
    };

    getInventarios({});
    $scope.ordenarPor("id");

    $rootScope.$on("CancelarInventario", function(event, id){
        $scope.cancelar(id);
    });
});