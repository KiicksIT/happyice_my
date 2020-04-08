<div ng-controller="itemController">
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="row">
                <div class="form-group col-md-2 col-sm-4 col-xs-6">
                    {!! Form::label('product_id', 'ID:', ['class'=>'control-label search-title']) !!}
                    {!! Form::text('product_id', null, ['class'=>'form-control input-sm', 'ng-model'=>'search.product_id', 'placeholder'=>'ID']) !!}
                </div>
                <div class="form-group col-md-2 col-sm-4 col-xs-6">
                    {!! Form::label('name', 'Product:', ['class'=>'control-label search-title']) !!}
                    {!! Form::text('name', null, ['class'=>'form-control input-sm', 'ng-model'=>'search.name', 'placeholder'=>'Product']) !!}
                </div>
                <div class="form-group col-md-2 col-sm-4 col-xs-6">
                    {!! Form::label('remark', 'Desc:', ['class'=>'control-label search-title']) !!}
                    {!! Form::text('remark', null, ['class'=>'form-control input-sm', 'ng-model'=>'search.remark', 'placeholder'=>'Desc']) !!}
                </div>
                <div class="form-group col-md-2 col-sm-4 col-xs-6">
                    {!! Form::label('is_active', 'Actve:', ['class'=>'control-label search-title']) !!}
                    {!! Form::select('is_active',
                        ['1'=>'Yes', '0'=>'No'],
                        null,
                        ['class'=>'select form-control',
                        'ng-model'=>'search.is_active',
                        'ng-init'=>'search.is_active = "1"'
                        ])
                    !!}
                </div>
            </div>
        </div>
    </div>
{{--
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12 form-group">
            <div class="pull-left">
                <button class="btn btn-primary" ng-click="exportData()">Export Excel</button>
            </div>
            <div class="pull-right display_panel_title">
                <label for="display_num">Display</label>
                <select ng-model="itemsPerPage" ng-init="itemsPerPage='100'">
                  <option ng-value="100">100</option>
                  <option ng-value="200">200</option>
                  <option ng-value="All">All</option>
                </select>
                <label for="display_num2" style="padding-right: 20px">per Page</label>
            </div>
        </div>
    </div> --}}
    <div class="row">
        <div class="col-md-3 col-sm-4 col-xs-12">
            <button class="btn btn-primary" ng-click="exportData()">Export Excel</button>
        </div>

        <div class="col-md-5 col-sm-8 col-xs-12" style="padding-top:5px;">
            <div class="row">
                <div class="col-md-5 col-sm-5 col-xs-5">
                    Total Available
                </div>
                <div class="col-md-7 col-sm-7 col-xs-7 text-right" style="border: thin black solid">
                    <strong>@{{ total_available ? total_available : 0.00 | currency: "": 4}}</strong>
                </div>
            </div>
            <div class="row">
                <div class="col-md-5 col-sm-5 col-xs-5">
                    Total Booked
                </div>
                <div class="col-md-7 col-sm-7 col-xs-7 text-right" style="border: thin black solid">
                    <strong>@{{ total_booked ? total_booked : 0.00 | currency: "": 4}}</strong>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12 col-xs-12">
            <div class="pull-right display_panel_title">
                <label for="display_num">Display</label>
                <select ng-model="itemsPerPage" ng-init="itemsPerPage='100'">
                  <option ng-value="100">100</option>
                  <option ng-value="200">200</option>
                  <option ng-value="All">All</option>
                </select>
                <label for="display_num2" style="padding-right: 20px">per Page</label>
            </div>
        </div>
    </div>

    <div class="table-responsive" id="exportable" style="padding-top:15px;">
        <table class="table table-list-search table-hover table-bordered">
            <tr style="background-color: #DDFDF8">
                <th class="col-md-1 text-center">
                    #
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortType = 'product_id'; sortReverse = !sortReverse">
                    ID
                    <span ng-if="sortType == 'product_id' && !sortReverse" class="fa fa-caret-down"></span>
                    <span ng-if="sortType == 'product_id' && sortReverse" class="fa fa-caret-up"></span>
                    </a>
                </th>
                <th class="col-md-2 text-center">
                    <a href="" ng-click="sortType = 'name'; sortReverse = !sortReverse">
                    Product
                    <span ng-if="sortType == 'name' && !sortReverse" class="fa fa-caret-down"></span>
                    <span ng-if="sortType == 'name' && sortReverse" class="fa fa-caret-up"></span>
                    </a>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortType = 'unit'; sortReverse = !sortReverse">
                    Unit
                    <span ng-if="sortType == 'unit' && !sortReverse" class="fa fa-caret-down"></span>
                    <span ng-if="sortType == 'unit' && sortReverse" class="fa fa-caret-up"></span>
                    </a>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortType = 'qty_now'; sortReverse = !sortReverse">
                    Available Qty
                    <span ng-if="sortType == 'qty_now' && !sortReverse" class="fa fa-caret-down"></span>
                    <span ng-if="sortType == 'qty_now' && sortReverse" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortType = 'qty_order'; sortReverse = !sortReverse">
                    Booked Qty
                    <span ng-if="sortType == 'qty_order' && !sortReverse" class="fa fa-caret-down"></span>
                    <span ng-if="sortType == 'qty_order' && sortReverse" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortType = 'lowest_limit'; sortReverse = !sortReverse">
                    Threshold Limit
                    <span ng-if="sortType == 'lowest_limit' && !sortReverse" class="fa fa-caret-down"></span>
                    <span ng-if="sortType == 'lowest_limit' && sortReverse" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortType = 'publish'; sortReverse = !sortReverse">
                    E-comm
                    <span ng-if="sortType == 'publish' && !sortReverse" class="fa fa-caret-down"></span>
                    <span ng-if="sortType == 'publish' && sortReverse" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortType = 'is_inventory'; sortReverse = !sortReverse">
                    Is Inventory?
                    <span ng-if="sortType == 'is_inventory' && !sortReverse" class="fa fa-caret-down"></span>
                    <span ng-if="sortType == 'is_inventory' && sortReverse" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortType = 'is_active'; sortReverse = !sortReverse">
                    Is Active?
                    <span ng-if="sortType == 'is_active' && !sortReverse" class="fa fa-caret-down"></span>
                    <span ng-if="sortType == 'is_active' && sortReverse" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    Action
                </th>
            </tr>

            <tbody>
                <tr dir-paginate="item in items | filter:search | orderBy:sortType:sortReverse | itemsPerPage:itemsPerPage"  current-page="currentPage" ng-controller="repeatController" pagination-id="item">
                    <td class="col-md-1 text-center">@{{ number }} </td>
                    <td class="col-md-1 text-center">@{{ item.product_id }}</td>
                    <td class="col-md-2">@{{ item.name }}</td>
                    <td class="col-md-1 text-center">@{{ item.unit }}</td>
                    <td class="col-md-1 text-right">
                        <span ng-if="item.is_inventory === 1">
                            <strong>@{{item.qty_now | currency: "": 4 }}</strong>
                        </span>
                        <span ng-if="item.is_inventory === 0">
                            N/A
                        </span>
                    </td>
                    <td class="col-md-1 text-right">
                        <span ng-if="item.is_inventory === 1">
                            <a href="/item/qtyorder/@{{item.id}}">@{{ item.qty_order ? item.qty_order : 0 | currency: "": 4 }}</a>
                        </span>
                        <span ng-if="item.is_inventory === 0">
                            N/A
                        </span>
                    </td>
                    <td class="col-md-1 text-right">@{{ item.lowest_limit | currency: "": 4 }}</td>
                    <td class="col-md-1 text-center">@{{ item.publish == 1 ? 'Yes':'No'  }}</td>
                    <td class="col-md-1 text-center">@{{ item.is_inventory == 1 ? 'Yes':'No'  }}</td>
                    <td class="col-md-1 text-center">@{{ item.is_active == 1 ? 'Yes':'No'  }}</td>
                    <td class="col-md-1 text-center">
                        @cannot('transaction_view')
                        <a href="/item/@{{ item.id }}/edit" class="btn btn-sm btn-primary">Edit</a>
                        @endcannot
                    </td>
                </tr>
                <tr ng-if="(items | filter:search).length == 0 || ! items.length">
                    <td class="text-center" colspan="14">No Records Found!</td>
                </tr>

            </tbody>
        </table>
    </div>

    <div class="col-md-12 col-sm-12 col-xs-12">
        <dir-pagination-controls max-size="5" direction-links="true" boundary-links="true" class="pull-left" pagination-id="item"> </dir-pagination-controls>
        <label ng-if-"items" class="pull-right totalnum" for="totalnum">Showing @{{(items | filter:search).length}} of @{{items.length}} entries</label>
    </div>
</div>