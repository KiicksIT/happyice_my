var app = angular.module('app', [   'ui.bootstrap', 'angularUtils.directives.dirPagination', 'ui.select', 'ngSanitize']);

        var $person = $('.person');
        var $item = $('.item');
        var $amount = $('#amount');
        var $trans_id = $('#transaction_id');
        var $person_select = $('.person_select');

        $person.select2();
        $item.select2({
            placeholder: "Select Item...",
        });

    function dealsController($scope, $http){

        $scope.selection = {};
        $scope.Math = window.Math;
        $scope.delivery = '';

            $(document).ready(function () {

               $(".qtyClass").keyup(multInputs);
               $(".quoteClass").keyup(multInputs);

                function multInputs() {
                "use strict";
                    var mult = 0;
                    // for each row:
                    $("tr.txtMult").each(function () {
                        // get the values from this row:
                        var $qty = eval($('.qtyClass', this).val());

                        var $quote = (+$('.quoteClass', this).val());

                        var $retail = (+$('.retailClass', this).val());

                        var $price = 0;

                        if($quote == null || $quote == '' || $quote == 0){

                            $price = 0;

                        }else{

                            $price = $quote;

                        }

                        var $total = (+$qty * +$price);
                        // set total for the row
                        // $('.amountClass', this).text($total);
                        if(isNaN($total)) {
                            var $total = 0;
                        }
                        $('.amountClass', this).val($total.toFixed(2));
                        mult += (+$total);
                   });

                   $('.grandTotal').val(mult.toFixed(2));
               }
            });

            $http.get('/person/data').success(function(people){
            $scope.people = people;
            });

            $http({
                url: '/market/deal/' + $trans_id.val(),
                method: "GET",
            }).success(function(transaction){

                $http({
                    url: '/market/dealData/' + transaction.id,
                    method: "GET",
                }).success(function(deals){
                    $scope.deals = deals;

                    var total = 0;
                    var totalqty = 0;
                    for(var i = 0; i < $scope.deals.length; i++){
                        var deal = $scope.deals[i];
                        total += (deal.amount/100*100);
                        totalqty += (deal.qty/100*100);
                    }

                        $http({
                            url: '/person/profile/' + transaction.person_id,
                            method: "GET",
                        }).success(function(profile){

                                $scope.totalModel = total;
                                $scope.totalqtyModel = totalqty;

                            if(profile.gst){

                                $scope.totalModelStore = (total * 7/100) + total;

                            }else{

                                $scope.totalModelStore = total;

                            }
                        });

                $http({
                    url: '/transaction/person/'+ transaction.person_id,
                    method: "GET",
                }).success(function(person){
                    $scope.personModel = person.id;
                    $scope.nameModel = person.name;
                    $scope.billModel = person.bill_address;
                    $scope.paytermModel = person.payterm;
                    $scope.personcodeModel = person.cust_id;

                    // choose which to display
                    // transremark
                    if(transaction.transremark){

                        $scope.transremarkModel = transaction.transremark;

                    }else{

                        $scope.transremarkModel = person.remark;
                    }

                    // delivery address
                    if(transaction.del_address){

                        $scope.delModel = transaction.del_address;

                    }else{

                        if(person.cust_id[0] == 'H'){

                            $scope.delModel = 'B'+ person.block + ', #'+ person.floor + '-' + person.unit + ', ' + person.del_address + ' ' + person.del_postcode;

                        }else{

                            $scope.delModel = person.del_address + ' ' + person.del_postcode;
                        }
                    }

                    // display default delivery postcode
                    if(transaction.del_postcode){

                        $scope.postcodeModel = transaction.del_postcode;

                    }else{

                        $scope.postcodeModel = person.del_postcode;
                    }

                    // display default delivery name
                    if(transaction.name){

                        $scope.attNameModel = transaction.name;

                    }else{

                        $scope.attNameModel = person.name;
                    }

                    // display default delivery contact
                    if(transaction.contact){

                        $scope.contactModel = transaction.contact;

                    }else{

                        $scope.contactModel = person.contact;
                    }


                    $('.date').datetimepicker({
                        format: 'YYYY-MM-DD',
                    });


                        $('.deldate').datetimepicker({
                            format: 'YYYY-MM-DD',
                        });



                        $http({
                            url: '/transaction/item/'+ person.id,
                            method: "GET",
                        }).success(function(items){
                            $scope.items = items;
                        });
                });

            });

        });

        //delete deals
        $scope.confirmDelete = function($event, id){
            $event.preventDefault();
            var isConfirmDelete = confirm('Are you sure you want to this?');
            if(isConfirmDelete){
                $http({
                    method: 'DELETE',
                    url: '/market/deal/data/' + id ,
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

app.filter('removeZero', ['$filter', function($filter) {
    return function(input) {
        input = parseFloat(input);
        input = input.toFixed(input % 1 === 0 ? 0 : 2);
        return input.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };
}]);

app.controller('dealsController', dealsController);

