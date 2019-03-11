var app = angular.module('app', [
                                    'angularUtils.directives.dirPagination',
                                    'ui.select',
                                    'ngSanitize',
                                    '720kb.datepicker',
                                    'datePicker'
                                ]);

    function personEditController($scope, $http){

        // init the variables
        $scope.alldata = [];
        $scope.totalCount = 0;
        $scope.totalPages = 0;
        $scope.currentPage = 1;
        $scope.itemsPerPage = 20;
        $scope.indexFrom = 0;
        $scope.indexTo = 0;
        $scope.sortBy = true;
        $scope.sortName = '';
        $scope.today = moment().format("YYYY-MM-DD");
        $scope.search = {
            id: '',
            status: '',
            pay_status: '',
            delivery_from: '',
            delivery_to: '',
            driver: '',
            pageNum: 20,
        }

        $scope.allVendData = [];
        $scope.totalVendCount = 0;
        $scope.totalVendPages = 0;
        $scope.currentVendPage = 1;
        $scope.indexVendFrom = 0;
        $scope.indexVendTo = 0;
        $scope.vendItemsPerPage = 20;
        $scope.searchvend = {
            id: '',
            collection_from: '',
            collection_to: '',
            itemsPerPage: 20,
            sortName: '',
            person_id: $('#person_id').val(),
            sortBy: true
        }

        $scope.total_amount = 0.00;
        $scope.total_paid = 0.00;
        $scope.total_owe = 0.00;

        // init page load
        getPage(1, true);
        loadFiles();

        angular.element(document).ready(function () {
            $('.select').select2({
                placeholder: 'Select..',
                allowClear: true
            });
        });
        $scope.onDeliveryFromChanged = function(date){
            if(date){
                $scope.search.delivery_from = moment(new Date(date)).format('YYYY-MM-DD');
            }
            $scope.searchDB();
        }
        $scope.onDeliveryToChanged = function(date){
            if(date){
                $scope.search.delivery_to = moment(new Date(date)).format('YYYY-MM-DD');
            }
            $scope.searchDB();
        }
        $scope.exportDataTransRpt = function () {
            var blob = new Blob(["\ufeff", document.getElementById('exportable_trans').innerHTML], {
                type: "application/vnd.ms-excel;charset=charset=utf-8"
            });
            var now = Date.now();
            saveAs(blob, "TransactionRpt"+ now + ".xls");
        };

        // switching page
        $scope.pageChanged = function(newPage){
            getPage(newPage, false);
        };

        $scope.pageNumChanged = function(){
            $scope.search['pageNum'] = $scope.itemsPerPage
            $scope.currentPage = 1
            getPage(1, false)
        };

          // when hitting search button
        $scope.searchDB = function(){
            $scope.sortName = '';
            $scope.sortBy = '';
            getPage(1, false);
        }

        // retrieve page w/wo search
        function getPage(pageNumber, first){
            $scope.spinner = true;
            $http.post('/person/transac/' + $('#person_id').val() +'?page=' + pageNumber + '&init=' + first, $scope.search).success(function(data){
                if(data.transactions.data){
                    $scope.alldata = data.transactions.data;
                    $scope.totalCount = data.transactions.total;
                    $scope.currentPage = data.transactions.current_page;
                    $scope.indexFrom = data.transactions.from;
                    $scope.indexTo = data.transactions.to;
                }else{
                    $scope.alldata = data.transactions;
                    $scope.totalCount = data.transactions.length;
                    $scope.currentPage = 1;
                    $scope.indexFrom = 1;
                    $scope.indexTo = data.transactions.length;
                }
                // get total count
                $scope.All = data.transactions.length;

                // return total amount
                $scope.total_amount = data.total_amount;
                $scope.total_paid = data.total_paid;
                $scope.total_owe = data.total_owe;
                $scope.profileDealsGrossProfit = data.profileDealsGrossProfit;
                $scope.spinner = false;
            });
        }

        // price management
        initPrice();

        function initPrice() {
            $http.get('/person/price/'+ $('#person_id').val()).success(function(items){
                $scope.items = items;
            });
            $http.get('/person/costrate/'+ $('#person_id').val()).success(function(data){
                $scope.costrate = data;
            });
        }

        $scope.calQuotePrice = function(index, item) {
            if(!isNaN(item.retail_price)) {
                $scope.items[index]['quote_price'] = item.retail_price * $scope.costrate/ 100;
            }else {
                initPrice();
            }
        }

        $http.get('/person/specific/data/'+ $('#person_id').val()).success(function(person){
            $scope.personData = person;
            $scope.noteModel = person.note;

            $scope.getRetailChange = function(retailModel){
                $scope.afterChange = (retailModel * person.cost_rate/100).toFixed(2);
            }
/*
            $scope.noteSave = function(note){
                console.log(note);
                $http({
                    method: 'POST',
                    url: '/person/' + person.id + '/note',
                    data: $.param(note: 'note'),
                }).success(function(){
                    });

            }   */
/*            $scope.noteSave = function(note){
                $http.post({'/note', note})
                        .success(function(){
                        });
            }*/

        });

        // loading files from person
        function loadFiles() {
            $http.get('/api/person/files/' + $('#person_id').val()).success(function(data) {
                $scope.files = data;
            });
        }

          // removing file
        $scope.removeFile = function(file_id){
            $http.post('/api/person/file/remove', {'file_id': file_id}).success(function(data) {
                loadFiles();
            });
        }

        $scope.exportData = function () {
            var blob = new Blob(["\ufeff", document.getElementById('exportableVend').innerHTML], {
                type: "application/vnd.ms-excel;charset=charset=utf-8"
            });
            var now = Date.now();
            saveAs(blob, "FVendCash"+ now + ".xls");
        };

        $scope.collectionFromChanged = function(date){
            if(date){
                $scope.searchvend.collection_from = moment(new Date(date)).format('YYYY-MM-DD');
            }
            $scope.searchVendDB();
        }

        $scope.collectionToChanged = function(date){
            if(date){
                $scope.searchvend.collection_to = moment(new Date(date)).format('YYYY-MM-DD');
            }
            $scope.searchVendDB();
        }

        $scope.onPrevDateClicked = function() {
            $scope.searchvend.collection_from = moment(new Date($scope.searchvend.collection_from)).subtract(1, 'days').format('YYYY-MM-DD');
            $scope.searchvend.collection_to = moment(new Date($scope.searchvend.collection_to)).subtract(1, 'days').format('YYYY-MM-DD');
            $scope.searchVendDB();
        }

        $scope.onTodayDateClicked = function() {
            $scope.searchvend.collection_from = moment().format('YYYY-MM-DD');
            $scope.searchvend.collection_to = moment().format('YYYY-MM-DD');
            $scope.searchVendDB();
        }

        $scope.onNextDateClicked = function() {
            $scope.searchvend.collection_from = moment(new Date($scope.searchvend.collection_from)).add(1, 'days').format('YYYY-MM-DD');
            $scope.searchvend.collection_to = moment(new Date($scope.searchvend.collection_to)).add(1, 'days').format('YYYY-MM-DD');
            $scope.searchVendDB();
        }

        $scope.onPrevSingleClicked = function(scope_name, date) {
            $scope.searchvend[scope_name] = date ? moment(new Date(date)).subtract(1, 'days').format('YYYY-MM-DD') : moment().format('YYYY-MM-DD');
            $scope.searchVendDB();
        }

        $scope.onNextSingleClicked = function(scope_name, date) {
            $scope.searchvend[scope_name] = date ? moment(new Date(date)).add(1, 'days').format('YYYY-MM-DD') : moment().format('YYYY-MM-DD');
            $scope.searchVendDB();
        }

        // switching page
        $scope.vendPageChanged = function(newPage){
            getVendPage(newPage);
        };

        $scope.vendPageNumChanged = function(){
            $scope.searchvend['pageNum'] = $scope.vendItemsPerPage
            $scope.currentVendPage = 1
            getVendPage(1);
        };

        $scope.sortVendTable = function(sortName) {
            $scope.searchvend.sortName = sortName;
            $scope.searchvend.sortBy = ! $scope.searchvend.sortBy;
            getVendPage(1);
        }

          // when hitting search button
        $scope.searchVendDB = function(){
            $scope.searchvend.sortName = '';
            $scope.searchvend.sortBy = true;
            getVendPage(1);
        }

        $scope.changeRemarks = function(id, remarks) {
            $http.post('/api/franchisee/remarks/' + id, {'remarks': remarks}).success(function(data) {
            });
        }

        getVendPage(1);

        // retrieve page w/wo search
        function getVendPage(pageNumber){
            $scope.spinner = true;
            $http.post('/api/franchisee?page=' + pageNumber, $scope.searchvend).success(function(data){
                if(data.ftransactions.data){
                    $scope.allVendData = data.ftransactions.data;
                    $scope.totalVendCount = data.ftransactions.total;
                    $scope.currentVendPage = data.ftransactions.current_page;
                    $scope.indexVendFrom = data.ftransactions.from;
                    $scope.indexVendTo = data.ftransactions.to;
                }else{
                    $scope.allVendData = data.ftransactions;
                    $scope.totalVendCount = data.ftransactions.length;
                    $scope.currentVendPage = 1;
                    $scope.indexVendFrom = 1;
                    $scope.indexVendTo = data.ftransactions.length;
                }
                // get total count
                $scope.VendAll = data.ftransactions.length;

                // return total amount
                $scope.total_vend_amount = data.totals.total_vend_amount;
                $scope.total_sales_pieces = data.totals.total_sales_pieces;
                $scope.avg_pieces_day = data.totals.avg_pieces_day;
                $scope.total_stock_in = data.totals.total_stock_in;
                $scope.total_sold_qty = data.totals.total_sold_qty;
                $scope.difference_stock_sold = data.totals.difference_stock_sold;
                $scope.transactions_total = data.totals.transactions_total;
                $scope.transactions_owe = data.totals.transactions_owe;
                $scope.transactions_paid = data.totals.transactions_paid;
                $scope.spinner = false;
            }).error(function(data){

            });
        }

    }

app.filter('delDate', [
    '$filter', function($filter) {
        return function(input, format) {
            return $filter('date')(new Date(input), format);
        };
    }
]);

app.filter('capitalize', function() {
  return function(input, scope) {
    if (input!=null) {
        input = input.toLowerCase();
        return input.substring(0,1).toUpperCase()+input.substring(1);
    }else {
        return null;
    }
  }
});

app.directive('ngConfirmClick', [
    function(){
        return {
            link: function (scope, element, attr) {
                var msg = attr.ngConfirmClick || "Are you sure?";
                var clickAction = attr.confirmedClick;
                element.bind('click',function (event) {
                    if ( window.confirm(msg) ) {
                        scope.$eval(clickAction)
                    }
                });
            }
        };
}])

function repeatController($scope) {
    $scope.$watch('$index', function(index) {
        $scope.number = ($scope.$index + 1) + ($scope.currentPage - 1) * $scope.itemsPerPage;
    })
}

app.controller('personEditController', personEditController);
app.controller('repeatController', repeatController);
