angular.module('uiDialogService', []).service('uiDialogService',
    ['$rootScope', '$q', '$compile', '$templateCache',
        function($rootScope, $q, $compile, $templateCache) {

            let _this = this;
            this.dialogs = {};

            this.dialogAlert = function (msg, title, width, height, resizable, position, modal, confimLbl) {
                let id = "dialogAlert";
                let template = "dialog-template.html";

                let buttons = {};

                buttons[((!!confimLbl)? confimLbl : "Ok")] = function (){
                    _this.close(id)
                };

                let config = {
                    title: (!!title)? title : "--- Sistema ---",
                    width: (!!width)? width : 350,
                    height: (!!height)? height : 'auto',
                    resizable: (!!resizable)? resizable : false,
                    position: (!!position)? position : ['center', 250],
                    modal:  (!!modal)? modal :  true,
                    buttons: buttons
                };

                this.open(id, template, false, { msg: msg }, config)
            };

            this.dialogConfirm = function (msg, title, confirmLbl, rejectLbl, confirmCallback, paramsConfirmCallback, rejectCallback, width, height, resizable, position, modal) {

                let buttons = {};

                let id = "dialogConfirm";
                let template = "dialog-template.html";

                buttons[((!!confirmLbl)? confirmLbl : "Confirmar")] = function (){
                    _this.close(id, true)
                };

                buttons[((!!rejectLbl)? rejectLbl : "Cancelar")] = function (){
                    _this.close(id)
                };

                let config = {
                    title: (!!title)? title : "--- Sistema ---",
                    width: (!!width)? width : 350,
                    height: (!!height)? height : 150,
                    resizable: (!!resizable)? resizable : false,
                    position: (!!position)? position : ['center', 250],
                    modal:  (!!modal)? modal :  true,
                    buttons: buttons
                };

                return this.open(id, template, false, { msg: msg }, config).then(function (result) {
                    if (result) {
                        if (angular.isFunction(confirmCallback)) { confirmCallback.call(); }
                    } else {
                        if (angular.isFunction(rejectCallback)) { rejectCallback.call(); }
                    }
                })
            };

            this.dialogModal = function (template, cached, title, width, height, resizable, position, modal, args) {

                let config = {
                    title: (!!title)? title : "--- Sistema ---",
                    width: (!!width)? width : 'auto',
                    height: (!!height)? height : 'auto',
                    resizable: (!!resizable)? resizable : true,
                    position: (!!position)? position : ['center',80],
                    modal:  (!!modal)? modal :  true,
                };

                this.open(template, template, cached, { args: args }, config)
                
            };

            this.open = function(id, template, cached, model, options) {

                // Check our required arguments
                if (!angular.isDefined(id)) {
                    throw "dialogService requires id in call to open";
                }

                if (!angular.isDefined(template)) {
                    throw "dialogService requires template in call to open";
                }

                // Set the defaults for model
                if (!angular.isDefined(model)) {
                    model = null;
                }

                // Copy options so the change ot close isn't propogated back.
                // Extend is used instead of copy because window references are
                // often used in the options for positioning and they can't be deep
                // copied.
                let dialogOptions = {};
                if (angular.isDefined(options)) {
                    angular.extend(dialogOptions, options);
                }

                // Initialize our dialog structure
                let dialog = { scope: null, ref: null, deferred: null };
                let dialogTemplate;
                if (cached) {
                    // Get the template and trim to make it valid
                    dialogTemplate = $templateCache.get(template);
                    if (!angular.isDefined(dialogTemplate)) {
                        throw "dialogService could not find template " + template;
                    }
                    dialogTemplate = dialogTemplate.trim();
                } else {
                    dialogTemplate = template;
                }

                // Create a new scope, inherited from the parent.
                dialog.scope = $rootScope.$new();
                dialog.scope.model = model;
                let dialogLinker = $compile(dialogTemplate);
                dialog.ref = $(dialogLinker(dialog.scope));

                // Hande the case where the user provides a custom close and also
                // the case where the user clicks the X or ESC and doesn't call
                // close or cancel.
                let customCloseFn = dialogOptions.close;
                let cleanupFn = this.cleanup;
                dialogOptions.close = function(event, ui) {
                    if (customCloseFn) {
                        customCloseFn(event, ui);
                    }
                    cleanupFn(id);
                };

                // Initialize the dialog and open it
                dialog.ref.dialog(dialogOptions);
                dialog.ref.dialog("open");

                // Cache the dialog
                _this.dialogs[id] = dialog;

                // Create our promise, cache it to complete later, and return it
                dialog.deferred = $q.defer();
                return dialog.deferred.promise;
            };

            this.close = function(id, result) {
                // Get the dialog and throw exception if not found
                let dialog = _this.getExistingDialog(id);

                // Notify those waiting for the result
                // This occurs first because the close calls the close handler on the
                // dialog whose default action is to cancel.
                dialog.deferred.resolve(result);

                // Close the dialog (must be last)
                dialog.ref.dialog("close");
            };

            this.cancel = function(id) {
                // Get the dialog and throw exception if not found
                let dialog = _this.getExistingDialog(id);

                // Notify those waiting for the result
                // This occurs first because the cancel calls the close handler on the
                // dialog whose default action is to cancel.
                dialog.deferred.reject();

                // Cancel and close the dialog (must be last)
                dialog.ref.dialog("close");
            };

            /* private */
            this.cleanup = function(id) {
                // Get the dialog and throw exception if not found
                let dialog = _this.getExistingDialog(id);

                // This is only called from the close handler of the dialog
                // in case the x or escape are used to cancel the dialog. Don't
                // call this from close, cancel, or externally.
                dialog.deferred.reject();
                dialog.scope.$destroy();

                // Remove the object from the DOM
                dialog.ref.remove();

                // Delete the dialog from the cache
                delete _this.dialogs[id];
            };

            /* private */
            this.getExistingDialog = function(id) {
                // Get the dialog from the cache
                let dialog = _this.dialogs[id];
                // Throw an exception if the dialog is not found
                if (!angular.isDefined(dialog)) {
                    throw "DialogService does not have a reference to dialog id " + id;
                }
                return dialog;
            };

        }
    ]);