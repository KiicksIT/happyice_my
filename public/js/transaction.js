var app = angular.module('app', [   'ui.bootstrap', 
                                    'angularUtils.directives.dirPagination',
                                    'ui.select', 
                                    'ngSanitize'
                                ]);

        var $person = $('.person');
        var $item = $('.item');
        var $amount = $('#amount');
        var $trans_id = $('#transaction_id'); 
        var $person_select = $('.person_select');

        $person.select2();
        $item.select2({
            placeholder: "Select Item...",
        });        

    function transactionController($scope, $http){

        $scope.selection = {};
        $scope.Math = window.Math;

        $http.get('/person/data').success(function(people){
        $scope.people = people;
        });

        $http({
            url: '/transaction/' + $trans_id.val(),
            method: "GET",
        }).success(function(transaction){

            $http({
                url: '/deal/data/' + transaction.id,
                method: "GET",
            }).success(function(deals){ 
                $scope.deals = deals;

                    var total = 0;
                    for(var i = 0; i < $scope.deals.length; i++){
                        var deal = $scope.deals[i];
                        total += (deal.amount/100*100);
                    }
                    $scope.totalModel = total.toFixed(2);

                    $http.put('total', $scope.totalModel)
                                .success(function(){
                    });                    
            });

            $http({
                url: '/transaction/person/'+ transaction.person_id,
                method: "GET",
            }).success(function(person){
                $scope.personModel = person.id;
                $scope.billModel = person.bill_address;
                $scope.delModel = person.del_address + ' ' + person.del_postcode;
                $scope.paytermModel = person.payterm;
                $scope.personcodeModel = person.cust_id;
                $('.date').datetimepicker({
                    format: 'DD-MMMM-YYYY'
                });

                    $http({
                        url: '/transaction/item/'+ person.id,
                        method: "GET",
                    }).success(function(items){
                        $scope.items = items;
                        /*$item.empty();
                        $.each(item, function(value, key) {
                            $item.append($("<option></option>").attr("value", value).text(key)); 
                        });*/

                        $scope.onItemSelected = function (item_id){

                            $http({
                                url: '/transaction/person/'+ person.id + '/item/' + item_id,
                                method: "GET",

                            }).success(function(prices){
                                $scope.prices = prices;
                                $scope.qtyModel = 1;
                                $scope.unitModel = prices.item.unit;
                                $scope.amountModel = prices.quote_price;

                                $scope.onQtyChange = function(){
                                    console.log(eval($scope.qtyModel));
                                    $scope.amountModel = prices.quote_price * eval($scope.qtyModel);
                                }
                            });                    

                        }            
                    });            
            });             

        });



    $scope.onPersonSelected = function (person){

        $http({
            url: '/transaction/person/'+ person,
            method: "GET",
        
        }).success(function(person){ 
            $scope.billModel = person.bill_address + ' ' + person.bill_postcode;
            $scope.delModel = person.del_address + ' ' + person.del_postcode;
            $scope.paytermModel = person.payterm;
            $scope.personcodeModel = person.cust_id;
            $('.date').datetimepicker({
            format: 'DD-MMMM-YYYY'
            });
            $('.date').val('');

            $http({
                url: '/transaction/item/'+ person.id,
                method: "GET",
            
            }).success(function(items){
                $scope.items = items;             
                $scope.qtyModel = [];
                $scope.amountModel = [];
                $scope.unitModel = [];

                $http.put('editperson', $scope.personModel)
                            .success(function(){
                            });

                /*$http.put('editpersoncode', $scope.personModel)
                            .success(function(){
                            }); */
                $http({
                    url: '/transaction/' + $trans_id.val() + '/editpersoncode' ,
                    method: "POST",
                    data: {person_code: $scope.personcodeModel},
                    }).success(function(response){
                    });
                                                                      

                $scope.onItemSelected = function (item_id){

                    $http({
                        url: '/transaction/person/'+ person.id + '/item/' + item_id,
                        method: "GET",

                    }).success(function(prices){
                        $scope.prices = prices;
                        $scope.qtyModel = 1;
                        $scope.unitModel = prices.item.unit;
                        $scope.amountModel = prices.quote_price;

                        $scope.onQtyChange = function(){
                            $scope.amountModel = prices.quote_price * eval($scope.qtyModel);
                        }
                    });                    

                }

            });
        });                                     
    }          

    $scope.currentPage = 1;
    $scope.itemsPerPage = 10;  

        //delete deals
        $scope.confirmDelete = function(id){
            var isConfirmDelete = confirm('Are you sure you want to this?');
            if(isConfirmDelete){
                $http({
                    method: 'DELETE',
                    url: '/deal/data/' + id
                })
                .success(function(data){
                    location.reload();
                })
                .error(function(data){
                    alert('Unable to delete');
                })
            }else{
                return false;
            }
        } 
    }  

function repeatController($scope) {
    $scope.$watch('$index', function(index) {
        $scope.number = ($scope.$index + 1) + ($scope.currentPage - 1) * $scope.itemsPerPage;
    })
}    

app.controller('transactionController', transactionController);
app.controller('repeatController', repeatController);
