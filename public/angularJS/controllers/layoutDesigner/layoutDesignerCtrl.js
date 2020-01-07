angular.module("wms").controller("layoutDesingerCtrl", function($scope, $http, $filter, uiDialogService){

    let millimeterToPx = function(millimeterValue){
        return millimeterValue * 3.7795275590551;
    };

    let pxToMillimeter = function(pxValue) {
        return pxValue / 3.7795275590551;
    };

    let verifyPosItens = function () {
        $.each($scope.componentAdded, function (k, component) {
            let maxX = component.posX + component.width;
            let maxY = component.posX + component.height;
            let areaW = $scope.displayArea.width;
            let areaH = $scope.displayArea.height;
            if (areaW < maxX || areaH < maxY) {
                $scope.displayArea.width = $scope.displayArea.widthBkp;
                $scope.displayArea.height = $scope.displayArea.heightBkp
            }
            uiDialogService.dialogAlert("Existem compontentes posicionados fora da área de impressão! Ajuste antes de prosseguir.");
            return false;
        })
    };

    $scope.componentsList = [];
    $scope.componentAdded = [];
    $scope.displayArea = {
        width: 400,
        height: 200,
        widthBkp: 400,
        heightBkp: 200,
        unit: 'px',
        proportion: 1,
        getCenter: function () {
            return {x: (this.width / 2), y: (this.height / 2)}
        }
    };

    $scope.componentForm = {
        list: [],
        selected: null
    };

    let initComponent = function(component) {
        let centerArea = $scope.displayArea.getCenter();

        component.selected = false;
        component.posX = (centerArea.x - (component.width / 2));
        component.posY = (centerArea.y - (component.height / 2));
        component.posZ = ($scope.componentsList.length + 1);
        component.select = function() { this.selected = true};

        return component
    };

    $scope.prepareInteract = function(element) {
        interact(element)
            .draggable({
                // enable inertial throwing
                inertia: false,
                // keep the element within the area of it's parent
                modifiers: [
                    interact.modifiers.restrictRect({
                        restriction: 'parent',
                        endOnly: false
                    })
                ],
                // enable autoScroll
                autoScroll: false,

                // call this function on every dragmove event
                onmove: dragMoveListener,
                // call this function on every dragend event
                onend: function (event) {
                    let target = event.target;
                    console.log("Pos X:", parseFloat(target.getAttribute('data-x')));
                    console.log("Pos Y:", parseFloat(target.getAttribute('data-y')));
                }
            });

// this is used later in the resizing and gesture demos
        window.dragMoveListener = dragMoveListener;
        dragMoveListener(event)
    };


    $scope.startList = function () {
        $scope.componentForm.list.push({type:'text', name:"codProduto", desc:"Cod. Produto", selected:false, width:80, height: 12});
        $scope.componentForm.list.push({type:'text', name:"grade", desc:"Grade", selected:false, width:80, height: 12});
        $scope.componentForm.list.push({type:'text', name:"codEtiqueta", desc:"Num. Etiqueta", selected:false, width:80, height: 12});
        $scope.componentForm.list.push({type:'text', name:"codPedido", desc:"Pedido", selected:false, width:80, height: 12});
        $scope.componentForm.list.push({type:'img',  name:"logo", desc:"Logomarca", selected:false, src:'/img/layoutDesigner/logo_cliente.jpg', width:120, height: 80});
    };

    $("#ld-drawing-area").change(function () {
        $scope.prepareInteract();
    });

    $scope.addComponent = function () {
        if (isEmpty($scope.componentForm.selected)) {
            uiDialogService.dialogAlert("Selecione o componente que deseja adicionar!");
            return;
        }
        let componentInitialized = initComponent(angular.copy($scope.componentForm.selected));
        $scope.componentAdded.push(componentInitialized);
        $scope.selectComponent(componentInitialized);
    };

    $scope.changeSize = function() {
        verifyPosItens();
    };

    let dragMoveListener = function(event) {
        var target = event.target;
        // keep the dragged position in the data-x/data-y attributes

        let dirX = parseFloat(target.getAttribute('data-x'));
        let dirY = parseFloat(target.getAttribute('data-y'));

        var x = dirX + event.dx;
        var y = dirY + event.dy;

        //translate the element
        target.style.webkitTransform =
            target.style.transform =
                'translate(' + x + 'px, ' + y + 'px)';

        // update the posiion attributes
        target.setAttribute('data-x', x);
        target.setAttribute('data-y', y);
    };

    $scope.selectComponent = function (component) {
        let activeItem = $scope.componentAdded.filter((item) => item.selected === true)[0];
        if (!isEmpty(activeItem)) activeItem.selected = false;
        component.select();
    };
});